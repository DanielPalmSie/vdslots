<?php

namespace IT\Services;

use DBUser;
use IT\Pacg\PacgTrait;
use IT\Pacg\Codes\ReturnCode as PacgReturnCode;
use IT\Pacg\Tables\BonusCancellationType;
use IT\Pacg\Tables\BonusOperationReasonCode;


class BonusCancellationService implements PayloadInterface
{
    use PacgTrait;

    const DEFAULT_CANCELLATION_TYPE = "conversion";
    const DEFAULT_CANCELLATION_REASON = "bonus_reversal";

    private array $bonus;
    private DBUser $user;
    /**
     * @var \Bonuses|bool|object|\Phive
     */
    private $bonuses;
    private string $cancellation_type;
    private string $transaction_reason;

    /**
     * @param DBUser|int $user
     * @param array $bonus_entry From table bonus_entries
     */
    public function __construct($user, array $bonus_entry)
    {
        $this->user = cu($user);
        $this->bonus = $bonus_entry;
        $this->bonuses = phive('CasinoBonuses');

        $this->setDefaults();
    }

    /**
     * Report The transaction to ADM
     *
     * @param $attempt
     * @return array|null
     * @throws \Exception
     */
    public function reportTransaction($attempt): ?array
    {
        $payload = $this->getPayload();

        $response = $this->bonusReversalAccountTransaction($payload);

        if ($response['code'] != PacgReturnCode::SUCCESS_CODE) {
            $logMessage = "An error has occurred during the bonusReversalAccountTransaction (PACG) of bonus entry {$this->bonus['id']}. Error code: '{$response['code']}', message: '{$response['message']}', attempt: {$attempt}";

            // store the error inside actions
            phive('DBUserHandler')->logAction($this->user, $logMessage, 'pacg-bonus-transaction-error', false, $this->user);
            if (in_array($response['code'], [1025, 1026, 1029]) and $attempt < 2) {
                phive('Site/Publisher')->single('pacg', 'Licensed', 'doLicense', ['IT', 'cancelBonus', [uid($this->user), $this->bonus, ($attempt + 1)]]);
            }
        }

        return $response;
    }


    /**
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'account_code' => $this->user->data['id'],
            'bonus_receipt_id' => $this->bonus['id'],
            'transaction_reason' => $this->getTransactionReason(),
            'bonus_cancelation_type' => $this->getCancellationType(),
            'bonus_cancelation_amount' => $this->getCancellationAmount(),
            'bonus_details' => $this->getBonusDetails(),
            'balance_amount' => $this->getBalanceAmount(),
            'bonus_balance_amount' => $this->getBonusBalanceAmount(),
            'bonus_balance_details' => $this->getBonusBalanceDetails(),
        ];
    }

    /**
     * Total amount of the bonus (in euro cents).
     * @return int
     */
    private function getCancellationAmount(): int
    {
        return $this->bonus['reward'];
    }

    /**
     * @return int
     */
    private function getTransactionReason(): int
    {
        return BonusOperationReasonCode::getStaticProperties()[$this->transaction_reason];
    }

    /**
     * The type of the cancelation of the bonus
     *
     * Using "conversion" since we are not removing real player balance, only unused bonus balance
     *
     * @return mixed
     */
    private function getCancellationType()
    {
        return BonusCancellationType::getStaticProperties()[$this->cancellation_type];
    }

    /**
     * Get the amount reversed + the family and game type of the bonus
     *
     * @return array
     */
    private function getBonusDetails(): array
    {
        // TODO I'm assuming skill games and fixed odds exchange -> find a way to get bonus gaming_family and gaming_type

        return [
            [
                // @see IT\Pacg\Tables\GamingFamily
                'gaming_family' => '6', // skill games

                // @see IT\Pacg\Tables\GamingTypeSkillGames
                'gaming_type' => '2', // fixed_odds_chance_games

                // This is the amount that we have not yet given to the user, the one that we are reverting
                // If we remove also the already given balance to the user, make sure to send the correct cancelation type/reason
                'bonus_amount' => (int)($this->bonus['reward'] - $this->bonuses->getStaggerPaid($this->bonus)),  // Bonus being reversed
            ]
        ];
    }

    /**
     * Gaming Account Balance inclusive of movement carried out and bonuses existing on the Gaming Account (in euro cents)
     *
     * @return int
     */
    private function getBalanceAmount(): int
    {
        return $this->user->getPlayBalance(true) + $this->bonuses->getBalanceByUser($this->user);
    }

    /**
     * Share part of the 'Amount Balance relating to bonuses existing on the Gaming Account (in euro cents)
     *
     * @return int
     */
    public function getBonusBalanceAmount(): int
    {
        /**
         * 'bonus_balance' => (int)phive('Bonuses')->getBalanceByUser($uid)
         * 'casino_wager' => (int)phive("Bonuses")->getRewards($uid, 'casinowager'),
         */
        return $this->bonuses->getBalanceByUser($this->user);
    }

    /**
     *  All the bonuses present on the account, including this transaction
     *
     * @return array
     */
    private function getBonusBalanceDetails(): array
    {
        $bonus_entries = $this->bonuses->getActiveBonusEntries($this->user->getId());
        $balance_details = [];

        foreach ($bonus_entries as $bonus_entry) {
            $balance_details[] = [
                // @see IT\Pacg\Tables\GamingFamily
                'gaming_family' => '6', // skill games

                // @see IT\Pacg\Tables\GamingTypeSkillGames
                'gaming_type' => '2', // fixed_odds_chance_games
                'bonus_amount' => $bonus_entry['balance'],  // Bonus balance from this entry
            ];
        }

        return $balance_details;
    }

    /**
     * @return void
     */
    private function setDefaults()
    {
        $this->setCancellationType(self::DEFAULT_CANCELLATION_TYPE);
        $this->setTransactionReason(self::DEFAULT_CANCELLATION_REASON);
    }

    /**
     * @param string $cancellation_type
     * @return BonusCancellationService
     * @see BonusCancellationType
     */
    public function setCancellationType(string $cancellation_type): BonusCancellationService
    {
        $this->cancellation_type = $cancellation_type;
        return $this;
    }

    /**
     * @param string $transaction_reason
     * @return BonusCancellationService
     * @see BonusOperationReasonCode
     */
    public function setTransactionReason(string $transaction_reason): BonusCancellationService
    {
        $this->transaction_reason = $transaction_reason;
        return $this;
    }

    /**
     * @param array $bonus
     * @return BonusCancellationService
     */
    public function setBonus(array $bonus): BonusCancellationService
    {
        $this->bonus = $bonus;
        return $this;
    }

    /**
     * @param DBUser $user
     * @return BonusCancellationService
     */
    public function setUser(DBUser $user): BonusCancellationService
    {
        $this->user = $user;
        return $this;
    }




}