<?php

/**
 * Class PrivacyHandler
 * A helper class to manage privacy settings for users
 */
class PrivacyHandler extends PhModule {

    const CHANNEL_EMAIL     = 'email';
    const CHANNEL_SMS       = 'sms';
    const CHANNEL_APP       = 'app';
    const CHANNEL_DIRECT    = 'direct_mail';
    const CHANNEL_VOICE     = 'voice';
    const CHANNEL_CALLS     = 'calls';

    const CHANNELS = [
        self::CHANNEL_EMAIL, self::CHANNEL_SMS, self::CHANNEL_APP,
        self::CHANNEL_DIRECT, self::CHANNEL_VOICE, self::CHANNEL_CALLS
    ];

    const TYPE_NEW          = 'new';
    const TYPE_PROMOTIONS   = 'promotions';
    const TYPE_UPDATES      = 'updates';
    const TYPE_OFFERS       = 'offers';

    const TYPES = [
        self::TYPE_NEW, self::TYPE_PROMOTIONS, self::TYPE_UPDATES, self::TYPE_OFFERS
    ];

    const PRODUCT_CASINO    = 'casino';
    const PRODUCT_SPORTS    = 'sports';
    const PRODUCT_BINGO     = 'bingo';
    const PRODUCT_POKER     = 'poker';

    const PRODUCTS = [
        self::PRODUCT_CASINO,
        self::PRODUCT_SPORTS,
        self::PRODUCT_BINGO,
        self::PRODUCT_POKER
    ];

    const TRANSACTIONAL_TYPE = 'transactional';

    const PRIVACY_TABLE = 'users_privacy_settings';

    const CHANNEL   = 'channel';
    const TYPE      = 'type';
    const PRODUCT   = 'product';

    private array $trigger_map = [];
    private array $privacySections = [];

    /** @var DBUserHandler $userHandler */
    private $userHandler;

    public function __construct()
    {
        $this->userHandler  = phive('DBUserHandler');
    }

    /**
     * Check if this combination of $channel, $type & $trigger
     * require consent
     *
     * @param string $channel
     * @param string $trigger
     * @return bool
     */
    public function requiresConsent(string $channel, string $trigger): bool
    {
        $settings = $this->getTriggerSettings($channel, $trigger);
        return $settings !== null && $settings[self::TYPE] !== self::TRANSACTIONAL_TYPE;
    }

    /**
     * Try to get the trigger type from the configs
     *
     * @param string $channel
     * @param string $trigger
     * @return array|null
     */
    public function getTriggerSettings(string $channel, string $trigger): ?array
    {
        $channel = strtolower($channel);
        if (!in_array($channel, self::CHANNELS)) return null;

        $trigger = strtolower($trigger);

        $map = $this->getTriggerMap($channel);
        if (isset($map[$trigger])) return $this->parseSettingString("{$channel}.{$map[$trigger]}");
        else phive('Logger')->warning("Trigger '{$trigger}' has not been configured for the '{$channel}' channel");

        return null;
    }

    /**
     * Checks if the specified user can receive
     * a specific trigger via a channel
     *
     * @param User|int|null $user
     * @param string $channel
     * @param string $trigger
     * @return bool
     */
    public function canReceiveTrigger($user, string $channel, string $trigger): bool
    {
        $settings = $this->getTriggerSettings($channel, $trigger);
        if ($settings === null) return false;

        if ($settings[self::TYPE] === self::TRANSACTIONAL_TYPE) return true;

        return $this->getPrivacySetting($user, $settings);
    }

    /**
     * Checks if the provided $user has any privacy settings
     *
     * @param User|int|null $user
     * @return bool
     */
    public function hasPrivacySettings($user = null): bool
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return false;

        return (int) $this->getSQLHandler($user)
                ->getValue(sprintf(
                    "SELECT COUNT(*) FROM %s WHERE user_id = '%d'",
                    self::PRIVACY_TABLE, $user->getId()
                )) > 0;
    }

    /**
     * Checks if user has a particular privacy setting configured
     *
     * @param User|int|null $user
     * @param array|string $setting
     * @return bool
     */
    public function hasPrivacySetting($user, $setting): bool
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return false;

        if (!is_array($setting)) $setting = $this->parseSettingString($setting);

        $tbl = self::PRIVACY_TABLE;
        $where = $this->getSettingWhereQuery($setting, $user->getId());
        return (int) $this->getSQLHandler($user)->getValue("SELECT COUNT(*) FROM {$tbl} WHERE {$where}") > 0;
    }

    /**
     * Get a privacy setting
     *
     * @param User|int|null $user
     * @param array|string $setting
     * @return bool
     */
    public function getPrivacySetting($user, $setting): bool
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return false;

        if (!is_array($setting)) $setting = $this->parseSettingString($setting);

        $tbl = self::PRIVACY_TABLE;
        $where = $this->getSettingWhereQuery($setting, $user->getId());
        return (int) $this->getSQLHandler($user)->getValue("SELECT opt_in FROM {$tbl} WHERE {$where}") === 1;
    }

    /**
     * Set a privacy setting
     *
     * @param User|int|null $user
     * @param string|array $setting - ['channel' => '', 'type' => '', 'product' => '']
     * @param bool $opt
     * @return void
     */
    public function setPrivacySetting($user, $setting, bool $opt = true): void
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return;

        if (!is_array($setting)) $setting = $this->parseSettingString($setting);

        if (empty($setting[self::CHANNEL]) || empty($setting[self::TYPE])) {
            phive('Logger')->error('Cannot set user privacy setting without channel or type', [
                'user' => $user, 'setting' => $setting, 'opt' => $opt
            ]);
            return;
        }

        $setting_string = $this->parseSettingArray($setting);
        if (!in_array($setting_string, $this->getLicensedConsentMap($user))) {
            phive('Logger')->error("Setting {$setting_string} is not allowed for user '{$user->getId()}'", [
                'user' => $user, 'setting' => $setting
            ]);
            return;
        }

        $data = [
            'user_id' => (int)$user->getId(),
            'channel' => $setting[self::CHANNEL],
            'type' => $setting[self::TYPE],
            'product' => (empty($setting[self::PRODUCT]) ? null : $setting[self::PRODUCT]),
            'opt_in' => $opt ? 1 : 0
        ];

        $exists = $this->getSettingID($setting, $user->getId());
        phive('SQL')->sh($user->getId())->insertArray(self::PRIVACY_TABLE, $data, ($exists) ? ['id' => $exists] : null);
    }

    /**
     * Delete all privacy setting for a specific user
     *
     * @param DBUser|int|null $user
     * @param bool $logAction
     * @return PrivacyHandler
     */
    public function clearAll($user, bool $logAction = true)
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return $this;

        $this->getSQLHandler($user)
            ->query('DELETE FROM ' . self::PRIVACY_TABLE . " WHERE user_id = '{$user->getId()}'");

        if ($logAction) {
            phive('DBUserHandler')->logAction($user, "Deleted all privacy settings. Will be shown privacy popup on login");
        }

        return $this;
    }

    /**
     * A helper function to set multiple settings in 1 go
     *
     * The $data array should follow the structure;
     * [
     *      ['<channel.type.product>' => bool],
     *      [[<setting key map>] => bool],
     *      ...,
     * ]
     *
     * @param User|int|null $user
     * @param array $data
     * @return void
     */
    public function setPrivacySettings($user, array $data): void
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return;

        foreach ($data as $key => $opt) $this->setPrivacySetting($user, $key, (bool)$opt);
    }

    /**
     * Set all privacy setting to true|false
     *
     * @param User|int|null $user
     * @param bool $opt
     * @return void
     */
    public function setAllPrivacySettings($user, bool $opt = true): void
    {
        foreach ($this->getAllConsentOptions($user) as $option) {
            $this->setPrivacySetting($user, $option, $opt);
        }
    }

    /**
     * Get the trigger map for $channel, or all if not specified
     *
     * @param string|null $channel
     * @return array
     */
    public function getTriggerMap(string $channel = null): array
    {

        // Cache this config to avoid reevaluating every time
        if (empty($this->trigger_map)) {
            foreach (self::CHANNELS as $_channel) {
                $settings = $this->getSetting("{$_channel}_triggers", []);
                if (empty($settings) || !is_array($settings)) continue;

                $map = [];

                foreach ($settings as $type => $list) {
                    if (empty($list) || !is_array($list)) continue;
                    $hasProducts = count(array_filter(array_keys($list), 'is_string')) > 0;

                    if ($hasProducts) {
                        foreach ($list as $product => $triggers) {
                            foreach ($triggers as $trigger) $map[$trigger] = "{$type}.{$product}";
                        }
                    } else {
                        foreach ($list as $trigger) $map[$trigger] = $type;
                    }
                }

                $this->trigger_map[$_channel] = $map;
            }
        }

        if (!empty($channel)) {
            return (isset($this->trigger_map[$channel])) ? $this->trigger_map[$channel] : [];
        }

        return $this->trigger_map;
    }

    /**
     * @param array $fdata
     * @param User|int|null $user
     * @param array $setOnly
     * @return void
     */
    public function saveFormData(array $fdata, $user = null, array $setOnly = []): void
    {
        if (empty($user = $this->userHandler->getCuOrReg($user))) return;

        // Save privacy settings
        foreach ($this->getAllConsentOptions($user) as $option) {
            $key = $this->parseSettingArray($option);
            if (!empty($setOnly) && !in_array($key, $setOnly)) continue;

            $this->setPrivacySetting(
                $user,
                $option,
                isset($fdata[$key]) && ($fdata[$key] == 'on' || $fdata[$key] == '1' || $fdata[$key] === true)
            );
        }

        // Save user settings
        foreach ($this->getUserSettingFields($user) as $field) {
            if (!empty($setOnly) && !in_array($field, $setOnly)) continue;

            $user->setSetting(
                $field,
                (isset($fdata[$field]) && ($fdata[$field] == 'on' || $fdata[$field] == '1' || $fdata[$field] === true)) ? '1' : '0'
            );
        }
    }

    /**
     * @param string $setting
     * @return array
     */
    public function parseSettingString(string $setting): array
    {
        $parts = explode('.', $setting);
        return [
            self::CHANNEL => empty($parts[0]) ? null : strtolower($parts[0]),
            self::TYPE => empty($parts[1]) ? null : strtolower($parts[1]),
            self::PRODUCT => empty($parts[2]) ? null : strtolower($parts[2])
        ];
    }

    /**
     * @param array $setting
     * @return string
     */
    public function parseSettingArray(array $setting): string
    {
        return implode('.', array_filter($setting));
    }

    /**
     * Get the search query for a privacy setting
     *
     * @param array $setting
     * @param int|null $user_id
     * @return string
     */
    public function getSettingWhereQuery(array $setting, int $user_id = null): string
    {
        $query[] = "channel = '" . strtolower($setting[self::CHANNEL]) . "'";
        $query[] = "type = '" . strtolower($setting[self::TYPE]) . "'";
        $query[] = empty($setting[self::PRODUCT])
            ? "(product IS NULL OR product = '')"
            : "product = '" . strtolower($setting[self::PRODUCT]) . "'";

        if (!empty($user_id)) $query[] = "user_id = {$user_id}";

        return implode(' AND ', $query);
    }

    /**
     * Get all consent options
     *
     * @param DBUser|int|null $user
     *
     * @return array
     */
    public function getAllConsentOptions($user = null): array
    {
        $options = [];
        foreach ($this->getLicensedConsentMap($user) as $key) $options[] = $this->parseSettingString($key);
        return $options;
    }

    /**
     * Get the main section for the privacy dashboard in the profile page and API
     *
     * @param User|int|null $user
     * @return array
     */
    public function getMainPrivacySections($user = null): array
    {
        return $this->getPrivacySections($user)['main'] ?: [];
    }

    /**
     * Get the secondary sections for the privacy dashboard in the profile page and API
     *
     * @param User|int|null $user
     * @return array
     */
    public function getSecondaryPrivacySections($user = null): array
    {
        $sections = $this->getPrivacySections($user);
        unset($sections['main']);
        return $sections;
    }

    /**
     * Get the all the sections for the privacy dashboard in the profile page and API
     *
     * @param User|int|null $user
     * @return array
     */
    public function getPrivacySections($user = null): array
    {
        // If we have loaded this already return from local cache
        if (!empty($this->privacySections)) return $this->privacySections;

        $this->privacySections = [];

        foreach ($this->getSetting('dashboard_layout', []) as $name => $section) {
            $this->privacySections[$name] = [];

            foreach ($section as $config) {
                $config['opt_out_all'] = true;
                $rows = [];

                if (!empty($config['options'])) {
                    $rows[] = ['options' => $this->buildConfiguredOptions($config['options'], $user, $config)];
                } elseif (!empty($config['type']) && in_array($config['type'], self::TYPES)) {
                    $rows = $this->buildDynamicRows($config['type'], $user, $config);
                }

                $this->privacySections[$name][] = ['config' => $config, 'rows' => $rows];
            }
        }

        return $this->privacySections;
    }

    /**
     * Checks if a user is opted-out a specific channel
     *
     * @param User|int|null $user
     * @param string $channel
     * @return bool
     */
    public function isOptedOutOfChannel($user, string $channel): bool
    {
        // If there is no user, they are opted-out by default
        if (empty($user = $this->userHandler->getCuOrReg($user))) return true;

        if (!in_array($channel, self::CHANNELS)) {
            throw new InvalidArgumentException("Channel {$channel} is not a valid channel");
        }

        return (int) $this->getSQLHandler($user)
                ->getValue(sprintf(
                    "SELECT COUNT(*) FROM %s WHERE channel = '%s' AND user_id = %d AND opt_in = 1",
                    self::PRIVACY_TABLE,
                    $channel,
                    $user->getId()
                )) < 1;
    }

    public function getPrivacySettings($user = null): array
    {
        $user = $user ?: cu($user);
        if (empty($user)) return [];

        $data = [];

        foreach ($this->getLicensedConsentMap($user) as $key) {
            $label = implode(' ', array_map('ucfirst', explode('_', $key)));
            $label = implode(' | ', array_map('ucfirst', explode('.', $label)));

            $data[$key] = ['value' => $this->getPrivacySetting($user, $key), 'label' => $label];
        }

        return $data;
    }

    /**
     * @param array $options
     * @param DBUser|int $user
     * @param array $config
     * @return array
     */
    private function buildConfiguredOptions(array $options, $user, array &$config): array
    {
        $result = [];
        $consentMap = $this->getLicensedConsentMap($user);

        foreach ($options as $opt) {
            if (empty($opt['setting'])) continue;

            if (empty($opt['is_user_setting'])) {
                if (!in_array($s = $opt['setting'], $consentMap)) continue;
                $opt['checked'] =
                    (empty($opt['off_by_default']) && !$this->hasPrivacySetting($user, $s))
                    || $this->getPrivacySetting($user, $s);
            } else {
                $opt['checked'] =
                    ((empty($opt['off_by_default'])) && !$user->hasSetting($opt['setting']))
                    || $user->getSetting($opt['setting']);
            }

            if ($opt['checked']) $config['opt_out_all'] = false;

            $result[] = $opt;
        }

        return $result;
    }

    /**
     * @param string $type
     * @param DBUser|int $user
     * @param array $config
     * @return array
     */
    private function buildDynamicRows(string $type, $user, array &$config): array
    {
        $rows = [];

        foreach (self::PRODUCTS as $product) {
            $options = [];
            foreach (self::CHANNELS as $channel) {
                $setting = "{$channel}.{$type}.{$product}";
                $options[] = ['setting' => $setting];
            }

            $options = $this->buildConfiguredOptions($options, $user, $config);

            if (!empty($options)) {
                $rows[] = [
                    'label_alias' => 'privacy.confirmation.' . $product,
                    'options' => $options
                ];
            }
        }

        return $rows;
    }

    /**
     * Perform actions on logIn/Out
     *
     * @param User|int|null $user
     * @param bool $isLogOut
     * @return $this
     */
    public function onLogInOut($user, bool $isLogOut): PrivacyHandler
    {
        $user = cu($user);
        if (empty($user)) return $this;

        if ($user->isBlocked() && $this->hasPrivacySettings($user)) {
            phive('DBUserHandler')->logAction(
                $user,
                "Deleting user privacy settings because users is blocked, to force a reconfirmation on return",
                'privacy-settings'
            );

            $this->clearAll($user, false);
        }

        return $this;
    }

    /**
     * Get the setting id if it exists else return 0
     *
     * @param array $setting
     * @param int $user_id
     * @return int
     */
    private function getSettingID(array $setting, int $user_id): int
    {
        $query = $this->getSettingWhereQuery($setting, $user_id);
        $tbl = self::PRIVACY_TABLE;
        return (int) $this->getSQLHandler($user_id)->getValue("SELECT id FROM {$tbl} WHERE {$query}");
    }

    /**
     * Get the consent map for the provided or logged-in user
     *
     * @param DBUser|int|null $user
     * @return array
     */
    private function getLicensedConsentMap($user): array
    {
        $user = $this->userHandler->getCuOrReg($user);

        if ($user instanceof DBUser) {
            /** @var Licensed $licensed */
            $licensed = phive('Licensed');
            $map = $licensed->getLicSetting('consent_map', $licensed->getLicCountry($user));
        }

        // If we don't have a set of options for a specific license default to a generic map
        return (!empty($map)) ? $map : $this->getSetting('consent_map');
    }

    /**
     * @param DBUser|int|null $user
     * @return array
     */
    private function getUserSettingFields($user): array
    {
        $opts = [];

        foreach ($this->getPrivacySections($user) as $section) {
            foreach ($section as $subSection) {
                foreach ($subSection['rows'] as $row) {
                    foreach ($row['options'] as $option) {
                        if (!empty($option['is_user_setting'])) {
                            $opts[] = $option['setting'];
                        }
                    }
                }
            }
        }

        return $opts;
    }

    /**
     * Get a SQL connection for the shard where $user exists
     *
     * @param User|int|null $user
     * @return SQL
     */
    private function getSQLHandler($user): SQL
    {
        /** @var SQL $sql */
        $sql = phive('SQL');

        $user = $this->userHandler->getCuOrReg($user);
        return $sql->sh($user->getId());
    }
}
