<?php
namespace IT\Services;

use DBUser;

/**
 * Class AccountLimitService
 * @package IT\Services
 */
class AccountLimitService
{
    /**
     * Allowed limit type
     */
    const LIMIT_ALLOWED = 'deposit';

    /**
     * Time span
     */
    const TIME_SPAN = [
        'day' => 1,
        'week' => 2,
        'month' => 3,
    ];

    /**
     * @var DBUser
     */
    private DBUser $user;

    /**
     * EmailAccountService constructor.
     * @param DBUser $user
     */
    public function __construct(DBUser $user)
    {
        $this->user = $user;
    }

    /**
     * Check if limit type is allowed
     * @param array $limit
     * @return bool
     */
    private function isAllowed(array $limit): bool
    {
        return isset($limit['type']) && $limit['type'] == self::LIMIT_ALLOWED;
    }

    /**
     * @param array $limit
     * @param int $value
     * @return array
     */
    public function getPayload(array $limit, int $value): array
    {
        if ($this->isAllowed($limit)) {
            return $data = [
                'account_code' => $this->user->getData('id'),
                'limit_management' => 1,
                'limit' => [
                    'limit_type' => self::TIME_SPAN[$limit['time_span']],
                    'amount' => $value,

                ],
                'transaction_id' => time()
            ];
        }
        return [];
    }
}