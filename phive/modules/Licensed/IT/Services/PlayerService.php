<?php

namespace IT\Services;

use IT\Services\Traits\PlayerPermissionsTrait;

class PlayerService
{
    use PlayerPermissionsTrait;

    const NO_VERIFIED_STAGE_1 = 0;
    const NO_VERIFIED_STAGE_2 = 1;
    const NO_VERIFIED_LONGER = 2;
    const VERIFIED = 10;
    const DAYS_LEFT_MAX = 90;
    const DAYS_LEFT_TO_CLOSE = 15;

    const CLOSE_STATUS = 3;

    const PAYMENT_REQUEST_VALID_REGISTRATION = [
        'pcard'
    ];

    const VERIFIED_DESCRIPTION = [
        self::VERIFIED => [
            'description' => '',
            'status' => self::VERIFIED,
            'days_left' => 0, // days to complete registration without interrupt playing functionality
            'blocked' => 0,
            'paragraphs' => []
        ],
        self::NO_VERIFIED_LONGER => [
            'description' => 'Blocked (elapsed 90 days)',
            'status' => self::NO_VERIFIED_LONGER,
            'days_left' => self::DAYS_LEFT_MAX, // days to complete registration without interrupt playing functionality
            'blocked' => 1,
            'paragraphs' => []
        ],
        self::NO_VERIFIED_STAGE_1 => [
            'description' => 'Unverified (under 30 days)',
            'status' => self::NO_VERIFIED_STAGE_1,
            'days_left' => 30, // days to complete registration without interrupt playing functionality
            'blocked' => 0,
            'paragraphs' => [
                'acc.verification.first-reminder.p1',
                'acc.verification.first-reminder.p2'
            ]
        ],
        self::NO_VERIFIED_STAGE_2 => [
            'description' => "Unverified 31-90 days",
            'status' => self::NO_VERIFIED_STAGE_2,
            'days_left' => self::DAYS_LEFT_MAX,// max total days to complete verification since registration
            'blocked' => 0,
            'paragraphs' => [
                'acc.verification.second-reminder.p1',
                'acc.verification.second-reminder.p2'
            ],
        ]
    ];

    /**
     * If the user is verified after submitting all docs
     *
     * @param \DbUser $user
     * @return bool
     */
    public function isVerified($user): bool
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        if ($user->hasSetting('poi_approved')) {
            return true;
        }

        $documents = licSetting('required_documents_types', $user);
        $document_status = phive('Dmapi')->getUserDocumentsGroupedByTagStatus($user->getId());

        if (phive('Dmapi')->documentsHaveStatus($document_status, $documents, 'approved')) {
            $user->setSetting('poi_approved', 1);
            return true;
        }
        return false;
    }

    /**
     * Returns the Current Player status
     * @param $user
     * @return int
     */
    public function getPlayerStatus($user)
    {
        $user = cu($user);
        if (empty($user)) {
            return self::NO_VERIFIED_LONGER;
        }
        if ($this->isVerified($user)) {
            return self::VERIFIED;
        }
        $days = $this->elapsedDaysSinceRegistration($user);
        if ($days <= 30) {
            return self::NO_VERIFIED_STAGE_1;
        } else if ($days <= self::DAYS_LEFT_MAX) {
            return self::NO_VERIFIED_STAGE_2;
        }

        return self::NO_VERIFIED_LONGER;
    }

    /**
     * Player permissions
     *
     * @return array[]
     */
    public function getPlayerPermissions()
    {
        return [
            'login' => [
                self::NO_VERIFIED_STAGE_1 => true,
                self::NO_VERIFIED_STAGE_2 => true,
                self::NO_VERIFIED_LONGER => false,
                self::VERIFIED => true
            ],
            'play4fun' => [
                self::NO_VERIFIED_STAGE_1 => true,
                self::NO_VERIFIED_STAGE_2 => true,
                self::NO_VERIFIED_LONGER => false,
                self::VERIFIED => true
            ],
            'play4real' => [
                self::NO_VERIFIED_STAGE_1 => false,
                self::NO_VERIFIED_STAGE_2 => false,
                self::NO_VERIFIED_LONGER => false,
                self::VERIFIED => true
            ],
            'deposit' => [
                self::NO_VERIFIED_STAGE_1 => true,
                self::NO_VERIFIED_STAGE_2 => false,
                self::NO_VERIFIED_LONGER => false,
                self::VERIFIED => true
            ],
            'prepaid_deposit' => [
                self::NO_VERIFIED_STAGE_1 => false,
                self::NO_VERIFIED_STAGE_2 => false,
                self::NO_VERIFIED_LONGER => false,
                self::VERIFIED => true
            ],
            'withdraw' => [
                self::NO_VERIFIED_STAGE_1 => false,
                self::NO_VERIFIED_STAGE_2 => false,
                self::NO_VERIFIED_LONGER => false,
                self::VERIFIED => true
            ],
        ];
    }

    /**
     * @param $user
     * @return int
     */
    public function getDaysOverLimit($user): int
    {
        $days_over = $this->elapsedDaysSinceRegistration($user);
        if ($days_over > self::DAYS_LEFT_MAX) {
            return $days_over - self::DAYS_LEFT_MAX;
        }

        return 0;
    }

    /**
     * Helper function to find the days elapsed since the player registered in the app
     *
     * @param $user
     * @return float
     */
    private function elapsedDaysSinceRegistration($user)
    {
        $now = time();
        $registration_date = strtotime($user->data['register_date']);
        $date_diff = $now - $registration_date;

        return round($date_diff / (60 * 60 * 24));
    }

    /**
     * @param int $days_over
     * @return array
     */
    private function getNotValidatedUserAccounts(int $days_over): array
    {
        $select = "
                    SELECT
                        u.*
                    FROM users u
                      LEFT JOIN users_settings uset ON uset.user_id = u.id and uset.setting = 'poi_approved'
                    WHERE u.country = 'IT'
                    AND (uset.value IS NULL OR uset.value = 0)
                    AND active = 1
                    AND DATEDIFF(NOW(), u.register_date) > {$days_over}";
        return phive('SQL')->shs()->loadArray($select);
    }

    /**
     * @param int $days_over
     * @return array
     */
    private function getBlockedAccountWithoutBalance(int $days_over): array
    {
        $select = "
                    SELECT
                        u.id,
                        u.nid,
                        u.email,
                        u.username
                    FROM users u
                      LEFT JOIN users_settings uset_close ON uset_close.user_id = u.id and uset_close.setting = 'closed_account'
                      JOIN users_settings uset_block ON uset_block.user_id = u.id and uset_block.setting = 'super-blocked'
                      LEFT JOIN users_settings uset ON uset.user_id = u.id and uset.setting = 'poi_approved'
                    WHERE u.country = 'IT'
                    AND (uset.value IS NULL OR uset.value = 0)
                    AND (uset_close.value IS NULL OR uset_close.value = 0)
                    AND uset_block.value = 1
                    AND active = 0
                    AND u.cash_balance = 0
                    AND DATEDIFF(NOW(), u.register_date) > {$days_over}
                    group by  u.id, u.nid, u.email, u.username";
        return phive('SQL')->shs()->loadArray($select);
    }

    /**
     * Super-blocking of player accounts not validated for over 90 days
     * @param \IT $italian_license
     * @throws \Exception
     */
    public function blockUserAccount(\IT $italian_license)
    {
        $users = $this->getNotValidatedUserAccounts(self::DAYS_LEFT_MAX);
        foreach ($users as $user) {
            try {
                $this->externalCloseUserAccount($user, $italian_license);
            } catch (\Exception $exception) {
                print_r($exception->getMessage());
                echo PHP_EOL;
                continue;
            }

            $user = cu($user);
            $user->superBlock(false);
        }
    }

    /**
     * Close a account and change email, username
     */
    public function closeUserAccount()
    {
        $users = $this->getBlockedAccountWithoutBalance(self::DAYS_LEFT_MAX + self::DAYS_LEFT_TO_CLOSE);
        foreach ($users as $user) {
            $user = cu($user);
            phive('UserHandler')->closeAccount($user);
        }
    }

    /**
     * @param $user
     * @param \IT $italian_license
     * @return bool|mixed
     * @throws \Exception
     */
    public function externalCloseUserAccount($user, \IT $italian_license)
    {
        return $italian_license->changeAccountStatus($this->getPayloadChangeAccountStatus($user));
    }

    /**
     * @param $user
     * @param int $status
     * @return array
     */
    private function getPayloadChangeAccountStatus($user, $status = self::CLOSE_STATUS)
    {
        return [
            'account_code' => $user->data['id'],
            'status' => $status,
            'reason' => '1', // ADM
            'transaction_id' => time(),
        ];
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isAllowedPaymentMethod(string $type)
    {
        $user = cu();
        if (in_array($type, self::PAYMENT_REQUEST_VALID_REGISTRATION) && !$this->isVerified($user)) {
            return false;
        }

        return true;
    }
}
