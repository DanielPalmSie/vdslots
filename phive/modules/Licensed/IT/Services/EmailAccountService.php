<?php
namespace IT\Services;

use DBUser;

/**
 * Class EmailAccountService
 * @package IT\Services
 */
class EmailAccountService
{
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
     * @param string $email
     * @return array
     */
    public function getPayload(string $email): array
    {
        return [
            'account_code' => $this->user->getData('id'),
            'email' => $email,
            'transaction_id' => time()
        ];
    }
}