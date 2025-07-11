<?php

use CA\Traits\ServicesTrait;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\InstadebitWithdrawalFlag;
use Videoslots\FraudDetection\FraudFlags\InteracWithdrawalFlag;

require_once __DIR__ .'/../Traits/RealityCheckTrait.php';
require_once __DIR__ .'/Traits/ServicesTrait.php';

class CA extends Licensed
{
    use RealityCheckTrait;
    use ServicesTrait;

    public const OVER_AGE_IMG = "19+W.png";

    public const WITHDRAWAL_FRAUD_FLAGS = [
        'instadebit-withdrawal-fraud-flag',
        'interac-withdrawal-fraud-flag'
    ];

    /**
     * @var array
     */
    protected $extra_registration_fields = [
            'step1' => [],
            'step2' => [],
    ];

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'main_province',
        'place_of_birth',
        'nationality'
    ];

    /**
     * Check if user blocked to play game or not
     *
     * @return boolean
     */
    public function isUserPlayBlocked()
    {
        $user = cu();
        if (empty($user))
        {
            return false;
        }

        $alias = phive('Pager')->getCurrentPageAlias();
        $block_play = in_array($alias, ['play-block']) && $this->isFirstDepositType($user->data['id'], ['instadebit']) && count($this->getUserDocumentsWithTagsAndStatus($user->data['id'], ['idcard-pic', 'addresspic'], ['requested', 'rejected']));

        if ($block_play)
        {
            $msg= t2('game_play.account_verification.play_block_popup_message', ['supportemail' => t('actual.support.email')], 'en');
            $verify_link = phive('UserHandler')->getUserAccountUrl('documents');
            $this->userPlayBlockedPopup ($msg, $verify_link);
        }

        return $block_play;
    }

    /**
     * Check if the user have first deposit in database, if yes than check dep_type with specific one!
     *
     * @param integer $user_id
     * @param array $dep_type
     *
     * @return boolean
     */
    public function isFirstDepositType($user_id, $dep_type)
    {
        $first_deposit = phive('SQL')->sh($user_id)->loadAssoc("SELECT dep_type FROM first_deposits WHERE user_id = {$user_id}");
        return isset($first_deposit['dep_type']) && in_array($first_deposit['dep_type'], $dep_type);
    }

    /**
     * Get the user documents with specific status and document_tags e.g. (addresspic, creditcardpic, bankpic)
     *
     * @param integer $user_id
     * @param array $tags
     * @param array $status
     *
     * @return array
     */
    public function getUserDocumentsWithTagsAndStatus ($user_id, $tags = [], $status = [])
    {
        $documents =  phive('Dmapi')->getDocuments($user_id);
        return array_filter($documents, function($k)  use ($tags, $status){
            return in_array($k['tag'], $tags) && in_array($k['status'], $status);
        });
    }

    /**
     * Make and show popup via JS
     *
     * @param string $msg
     * @param string $redirect_url
     * @param string $btn_label
     *
     */
    public function userPlayBlockedPopup ($msg, $redirect_url, $btn_label = 'verify')
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                mboxDialog('<?php echo $msg; ?>', "", "", "window.top.location='<?php echo $redirect_url; ?>'", '<?php echo $btn_label; ?>', "", "", true, "hidden", "Message", "play-block-verify-btn", "");
            });
        </script>
        <?php
    }

    /**
     * Returns the background color given in the configuration, if the pending withdrawal has been flagged
     *
     * @param int $userId The id of the user belonging to the transaction
     *
     * @return string background color in case of matching the criteria, otherwise returns ""
     */
    public function getWithdrawalFraudFlagColor(int $userId): string
    {
        $fraudFlag = phive('UserHandler')->rawSettingsWhere(
            "user_id = {$userId} AND setting IN ('" . implode("','", self::WITHDRAWAL_FRAUD_FLAGS) . "')"
        );

        if ($fraudFlag) {
            $licensedSettings = phive('Licensed')->getSetting('CA');

            return $licensedSettings['fraud_check']['consequences']['pending_withdrawals_rgb'] ?? "#008000";
        }

        return '';
    }

    /**
     ** This method is used to set and removed the 'X-fraud-flag' in users' settings by checking the
     * 'getMaxDepFraudFlag' logic.
     */
    public function manipulateFraudFlag(
        DBUser $user,
        string $dep_type,
        string $dep_type_scheme,
               $transactionId,
        int    $event
    ): void
    {
        $properties = ['dep_type' => $dep_type, 'dep_type_scheme' => $dep_type_scheme];
        InstadebitWithdrawalFlag::create($transactionId)->assign($user, $event, $properties);
        InteracWithdrawalFlag::create($transactionId)->assign($user, $event, $properties);
    }

    /**
     * Invoked after a deposit is cancelled.
     *
     * @param $user
     * @param array|null $args
     */
    public function onCancelledDeposit($user, ?array $args)
    {
        $deposit = phive('CasinoCashier')->getUserDepositByMtsTransactionId(
            $user->getId(),
            $args['transaction_id']
        );

        $this->manipulateFraudFlag(
            $user,
            $args['supplier'],
            $args['sub_supplier'],
            !empty($deposit) ? $deposit['id'] : null,
            AssignEvent::ON_DEPOSIT_CANCELLED
        );
    }

    /**
     * Invoked after a deposit successful.
     *
     * @param $user
     * @param array|null $args
     */
    public function onSuccessfulDeposit($user, ?array $args)
    {
        $deposit = phive('CasinoCashier')->getUserDepositByMtsTransactionId(
            $user->getId(),
            $args['transaction_id']
        );

        $this->manipulateFraudFlag(
            $user,
            $args['supplier'],
            $args['sub_supplier'],
            !empty($deposit) ? $deposit['id'] : null,
            AssignEvent::ON_DEPOSIT_SUCCESS
        );

        $this->addNationalityBirthCountrySetting($user);

        lic('showOccupationalPopupOnDeposit', [$user], $user);
    }

    /**
     * Optionally returns a jurisdiction override indicating whether this document tag should be displayed or not.
     * This is a temporary hack to override 'do_kyc' for a specific psp based on jurisdiction. At some point we need a better solution.
     *
     * @param $user
     * @param string|null $document_tag
     * @return bool|null NULL if there is no override, TRUE if it MUST be shown, FALSE if it must NOT be shown.
     */
    public function getDisplayDocumentOverride($user, ?string $document_tag): ?bool
    {
        $config = $this->getLicSetting('fraud_check');
        if (!$config || !($config['psps'] ?? false)) {
            return null;
        }

        foreach ($config['psps'] as $psp) {
            if (($tag = (phive('Dmapi')->map[$psp] ?? null)) != null) {
                if (in_array($document_tag, [$psp, $tag])) {
                    return true;
                }
            }
        }
        return null;
    }

    /**
     * Invoked when a player makes a deposit.
     *
     * @param $user
     * @param int $deposit_id
     * @param array $extraParams ExtraParams is an array in which we can have any type of key => value pairs,
     * for example ['user_id' => 123, 'psp' => 'instadebit']. If we need some extra parameters in this method we can
     * just pass them into this array instead of modifying the method's arguments everywhere.
     *
     */
    public function onDeposit($user, int $deposit_id, array $extraParams)
    {
        if (empty($user = cu($user))) {
            return;
        }
        lic('showOccupationalPopupOnDeposit', [$user], $user);
        $config = $this->getLicSetting('fraud_check');
        if (!$config || !($config['psps'] ?? false)) {
            return;
        }

        $deposit = phive('Cashier')->getDeposit($deposit_id, $user->getId());
        if (!$deposit || !in_array($deposit['dep_type'], $config['psps'])) {
            phive()->dumpTbl("Info. {$deposit['dep_type']} deposit. No fraud verifications required.", [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'deposit' => $deposit['id'],
                'fraud_config' => $config['psps'],
            ], $user->getId());
            return;
        }

        $this->manipulateFraudFlag(
            $user,
            $deposit['dep_type'],
            $deposit['scheme'],
            $deposit['id'],
            AssignEvent::ON_DEPOSIT_START
        );

        $deposit_counts = phive('Cashier')->countInByType('2000-01-01', '2100-01-01', $user->getId());

        if (!$this->handleMultipleInstadebitAccounts($user, $deposit, $deposit_counts, $extraParams)) {
            return;
        }

        if ($this->skipDepositFraudCheck($user, $deposit, $deposit_counts)) {
            return;
        }

        if ($deposit['dep_type'] == 'instadebit') {
            $this->playBlockIfFirstDeposit($user, $deposit, $deposit_counts);
        }

        $this->createDocumentsAfterDeposit($user, $deposit, $deposit_counts);

        $user->deleteSetting('verified');
        $user->setSetting('withdrawal_block', 1);

        phive()->dumpTbl("Info. {$deposit['dep_type']} deposit. Unverifying account and setting withdrawal_block.", [
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'file' => __METHOD__ . '::' . __LINE__,
            'deposit' => $deposit['id'],
        ], $user->getId());
    }

    /**
     * This method is used to handle the player's multiple instadebit accounts. If the instadebit account has been changed,
     * trigger an appropriate approval flow.
     *
     * @param DBUser $user
     * @param array $deposit
     * @param array $deposit_counts
     * @param array $extraParams ExtraParams is an array in which we can have any type of key => value pairs,
     * for example ['user_id' => 123, 'psp' => 'instadebit']. If we need some extra parameters in this method we can
     * just pass them into this array instead of modifying the method's arguments everywhere.
     *
     * @return bool Returns FALSE if the player's instadebit account has been changed.
     */
    private function handleMultipleInstadebitAccounts(DBUser $user, array $deposit, array $deposit_counts, array $extraParams): bool
    {
        //  We restrict/block/set the user settings if it is 'instadebit' deposit and it's not the player's first instadebit-deposit.
        if ($deposit['dep_type'] === 'instadebit' && !$this->isFirstAndOnlyDeposit($deposit['dep_type'], $deposit_counts))
        {
            $instadebit_user_id = $user->getSetting('instadebit_user_id') ?? '';
            $ext_instadebit_user_id = $extraParams['instadebit_user_id'] ?? '';
            if ($instadebit_user_id && $ext_instadebit_user_id && $instadebit_user_id !== $ext_instadebit_user_id) {
                $user->setSetting('play_block', 1);
                $user->setSetting('withdrawal_block', 1);
                $user->deleteSetting('verified');
                $user->refreshSetting('instadebit_user_id', $ext_instadebit_user_id);
                // send a call to Dmapi to reject the file(s), and set the document to 'requested'
                phive('Dmapi')->rejectAllFilesFromDocument($user->getId(), 'instadebitpic');
                $user->addComment('Instadebit account changed. The customer needs to upload proof of a new Instadebit account.', 0, 'amlfraud');
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if the fraud check can be skipped for this deposit.
     *
     * @param DBUser $user
     * @param array $deposit
     * @param array $deposit_counts
     * @return bool Returns TRUE if the player's account is verified and has already made other deposits with the same psp.
     */
    private function skipDepositFraudCheck(DBUser $user, array $deposit, array $deposit_counts): bool
    {
        $count = ($deposit_counts[$deposit['dep_type']]['total'] ?? 0);
        if (($count <= 1) || !$user->getSetting('verified')) {
            return false;
        }

        phive()->dumpTbl("Info. {$deposit['dep_type']} deposit. Skipping fraud checks.", [
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'file' => __METHOD__ . '::' . __LINE__,
            'account_verified' => true,
            'deposit' => $deposit['id'],
            "count_{$deposit['dep_type']}_deposits" => $count,
        ], $user->getId());
        return true;
    }

    /**
     * Sets `play_block` if this is the player's 1st deposit with Instadebit,
     * ignoring previous deposits with other psps.
     *
     * @param DBUser $user
     * @param array $deposit
     * @param array $deposit_counts
     */
    private function playBlockIfFirstDeposit(DBUser $user, array $deposit, array $deposit_counts)
    {
        $count = ($deposit_counts[$deposit['dep_type']]['total'] ?? 0);
        if ($count == 1) {
            $user->setSetting('play_block', 1);

            phive()->dumpTbl("Info. {$deposit['dep_type']} play_block for 1st deposit.", [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'deposit' => $deposit['id'],
            ], $user->getId());
        }
    }

    /**
     * Creates the documents required after an Instadebit deposit, if they do not already exist.
     *
     * @param DBUser $user
     * @param array $deposit
     * @param array $deposit_counts
     */
    private function createDocumentsAfterDeposit(DBUser $user, array $deposit, array $deposit_counts)
    {
        if (!$this->isFirstAndOnlyDeposit($deposit['dep_type'], $deposit_counts)) {
            return;
        }

        $requiredDocuments = [
            'idcard-pic',
            'addresspic'
        ];

        foreach ($requiredDocuments as $tag) {
            $document = phive('Dmapi')->getDocumentByTag(phive('Dmapi')->map[$tag], $user->getId());
            if (!$document) {
                phive('Dmapi')->createEmptyDocument($user->getId(), $tag);
                phive()->dumpTbl("Info. {$deposit['dep_type']} deposit. Creating {$tag} document request.", [
                    'url' => $_SERVER['REQUEST_URI'] ?? null,
                    'file' => __METHOD__ . '::' . __LINE__,
                    'deposit' => $deposit['id'],
                ], $user->getId());
            }
        }
    }

    /**
     * Returns TRUE if the player has made just 1 deposit and with the specified PSP.
     *
     * @param string $psp
     * @param array $deposit_counts
     * @return bool
     */
    private function isFirstAndOnlyDeposit(string $psp, array $deposit_counts): bool
    {
        $count_all_deposits = 0;
        foreach ($deposit_counts as $psp2 => $v) {
            $count_all_deposits += (int)$v['total'];
        }
        return ($count_all_deposits == 1) && (($deposit_counts[$psp]['total'] ?? 0) == 1);
    }

    /**
     * Invoked when the user's 'verified' status changes.
     *
     * @param $user_id
     * @param string $status
     */
    public function trackUserStatusChanges($user_id, string $status)
    {
        parent::trackUserStatusChanges($user_id, $status);

        $config = $this->getLicSetting('fraud_check');
        if (!$config || !($config['psps'] ?? false)) {
            return;
        }

        if (empty($user = cu($user_id))) {
            return;
        }
        $tags = $this->getPspDocumentTagsForFraudCheck($user);
        if (!in_array('instadebit', $tags) && !in_array('muchbetter', $tags)) {
            phive()->dumpTbl("Info. Fraud check is ignoring user status change.", [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'user_status' => $status,
                'player_psp_document_tags' => $tags,
            ], $user->getId());

            if ($status == UserStatus::STATUS_ACTIVE && $user->getSetting('verified') && $user->hasSetting('withdrawal_block')) {
                $user->deleteSetting('withdrawal_block');

                phive('Logger')->getLogger('payments')->info('Removing withdrawal_block.',
                    [
                        'user_id' => $user->getId(),
                        'url' => $_SERVER['REQUEST_URI'] ?? null,
                        'file' => __METHOD__ . '::' . __LINE__,
                        'user_status' => $status,
                        'player_psp_document_tags' => $tags,
                    ]);
            }
            return;
        }

        if ($status == UserStatus::STATUS_ACTIVE && $user->getSetting('verified')) {
            $user->deleteSetting('withdrawal_block');
            phive()->dumpTbl("Info. Removing withdrawal_block.", [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'user_status' => $status,
                'player_psp_document_tags' => $tags,
            ], $user->getId());

            if (in_array('instadebit', $tags)) {
                $user->deleteSetting('play_block');
            }
        }

        if ($status == UserStatus::STATUS_PENDING_VERIFICATION) {
            $user->setSetting('withdrawal_block', 1);
            phive()->dumpTbl("Info. Setting withdrawal_block.", [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'user_status' => $status,
                'player_psp_document_tags' => $tags,
            ], $user->getId());
        }
    }

    /**
     * Returns the tags for the player's PSP documents which are being monitored for fraud check,
     * e.g. 'Verify Instadebit Account' and/or 'Verify MuchBetter Account' document.
     * These documents were created when making a deposit with the corresponding psp.
     *
     * @param DBUser $user
     * @return array The PSP document tags which exist for this player and which should be monitored for fraud checks.
     */
    private function getPspDocumentTagsForFraudCheck(DBUser $user): array
    {
        $psp_documents = [];

        $config = $this->getLicSetting('fraud_check');
        if (!$config || !($config['psps'] ?? false)) {
            return $psp_documents;
        }

        foreach ($config['psps'] as $psp) {
            $document = phive('Dmapi')->getDocumentByTag(phive('Dmapi')->map[$psp], $user->getId());
            if ($document) {
                $psp_documents []= $psp;
            }
        }
        return $psp_documents;
    }

    /**
     * Registration step 2 setup
     *
     * @return array
     */
    public function registrationStep2Fields(): array
    {
        if(!licSetting('require_main_province')) {
            return parent::registrationStep2Fields();
        }

        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'bonus_code', 'nationality', 'place_of_birth', 'main_province', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'email_code', 'eighteen']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'place_of_birth', 'main_province', 'preferred_lang', 'bonus_code'],
            'right' => ['birthdate', 'currency', 'sex', 'email_code', 'eighteen']
        ];
    }

    /**
     * @param bool $without_licensed_province
     * @return array
     * @throws Exception
     */
    public function getProvinces(bool $without_licensed_province = false): array
    {
        $provinces = ($this->getProvinceService())->getProvinceList();
        if ($without_licensed_province && ($key = array_search(licSetting('removed_province'), $provinces)) !== false) {
            unset($provinces[$key]);
        }

        return $provinces;
    }

    /**
     * @param DBUser $u_obj
     * @param bool $is_api
     */
    public function onLogin(DBUser $u_obj, bool $is_api = false)
    {
        parent::onLogin($u_obj);

        if ($is_api) {
            return;
        }

        if (licSetting('require_main_province', $u_obj) && !$u_obj->hasSetting('main_province')) {
            $_SESSION['show_add_province_popup'] = true;
        }

        if ($u_obj->getSetting('nationality_birth_country_required')) {
            $_SESSION['show_add_nationalityandpob_popup'] = true;
        }
    }

    /**
     * Get list of fields to display on user profile page
     *
     * @return string[]
     */
    public function getPrintDetailsFields(): array
    {
        return ['firstname', 'lastname', 'address', 'province', 'zipcode', 'city', 'country', 'dob', 'mobile', 'email', 'last_login', 'register_date'];
    }

    /**
     * Get needed data to display details on user profile page
     *
     * @param DBUser $user
     *
     * @return array
     * @throws Exception
     */
    public function getPrintDetailsData(DBUser $user): array
    {
        return array_merge(parent::getPrintDetailsData($user), [
            'province' => $this->getProvinces()[$user->getSetting('main_province')] ?? ''
        ]);
    }


    /**
     * @return array
     */
    public function getSelfExclusionTimeOptions()
    {
        return [183, 365, 730, 1095, 1825];
    }

    /**
     *
     * @param $step
     * @param array $request
     *
     * @return array
     */
    public function extraRegistrationValidations($step, array $request, $user_id = null)
    {
        $errors = parent::extraRegistrationValidations($step, $request);
        $user = cu($user_id);

        if (
            ($user->getSetting('migrated') === '0' &&
            $request['main_province'] === CAON::FORCED_PROVINCE) &&
            ($request['currency'] !== $this->getLicSetting('forced_currency', 'CAON') ||
            $user->getCurrency() !== $this->getLicSetting('forced_currency', 'CAON'))
        ) {
            $errors['currency'] = 'wrong.currency';
        }

        return $errors;
    }

    /**
     * Makes possible to disable prefilling of a Country Value
     * @return string
     */
    public function getBirthCountryValue(): string
    {
        return '';
    }

    /**
     * Makes possible to disable prefilling of a Nationality Value
     * @return string
     */
    public function getNationalityValue(): string
    {
        return '';
    }

    /**
     * Skips creating a Label for a Dropdown Select
     * @param string|null $key
     *
     * @return bool
     */
    public function shouldDisableLabel(?string $key): bool
    {
        if($key == 'birth_country'){
            return true;
        }

        return false;
    }

    /**
     * Gets List of countries
     *
     * @return array
     */
    public function getBirthCountryList(): array
    {
        return lic('getNationalities') ?? [];
    }

    /**
     * @param string|null $main_country
     * @return bool
     */
    public function shouldValidateMainProvince(?string $main_country): bool
    {
        return true;
    }
}
