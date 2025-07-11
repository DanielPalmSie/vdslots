<?php
namespace IT\Services;

/**
 * Class SubregistrationService
 * @package IT\Services
 */
class SubregistrationService implements PayloadInterface
{
    /**
     * @var object
     */
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    protected function getBonuses(): int
    {
        return phive('Bonuses')->getBalanceByUser($this->user);
    }

    /**
     * @return int
     */
    protected function getBalance(): int
    {
        return $this->user->getBalance();
    }


    /**
     * @inheritDoc
     */
    public function getPayload(): array
    {
        return [
            'account_code' => $this->user->data['id'],
            'balance_amount' => $this->getBalance(),
            'balance_bonus_amount' => $this->getBonuses()
        ];
    }
}