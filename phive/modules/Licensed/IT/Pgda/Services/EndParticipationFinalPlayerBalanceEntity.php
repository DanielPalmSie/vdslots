<?php

namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\DateTimeType;

/**
 * End of participation and transfer of final player balance (430)
 * Class EndParticipationFinalPlayerBalanceEntity
 * @package IT\Pgda\Entity
 */
class EndParticipationFinalPlayerBalanceEntity extends PgdaEntity
{
    /**
     * @var int
     */
    public $game_code;

    /**
     * @var int
     */
    public $game_type;

    /**
     * @var string
     */
    public $central_system_session_id;

    /**
     * @var string
     */
    public $participation_id_code;

    /**
     * @var int
     */
    public $number_stage_undertaken_player;

    /**
     * @var int
     */
    public $participation_amount;

    /**
     * @var int
     */
    public $amount_staked;

    /**
     * @var int
     */
    public $real_bonus_participation_amount;

    /**
     * @var int
     */
    public $play_bonus_participation_amount;

    /**
     * @var int
     */
    public $real_bonus_staked_amount;

    /**
     * @var int
     */
    public $amount_staked_resulting_play_bonus;

    /**
     * @var int
     */
    public $taxable_amount;

    /**
     * @var int
     */
    public $amount_returned_winnings;

    /**
     * @var int
     */
    public $amount_returned_resulting_jackpots;

    /**
     * @var int
     */
    public $amount_returned_resulting_additional_jackpots;

    /**
     * @var int
     */
    public $amount_returned_assigned_as_real_bonus;

    /**
     * @var int
     */
    public $amount_giver_over_play_bonus;

    /**
     * @var int
     */
    public $code_license_account_holder;

    /**
     * @var int
     */
    public $network_code;

    /**
     * @var string
     */
    public $gambling_account;

    /**
     * @var int
     */
    public $end_stage_progressive_number;

    /**
     * @var DateTimeType
     */
    public $date_final_balance;

    /**
     * @var int
     */
    public $jackpot_fund_amount;

    /**
     * @var string
     */
    protected $format = "A16A16N7JN6nCA*Nn6N";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'central_system_session_id',
        'participation_id_code',
        'number_stage_undertaken_player',
        'participation_amount',
        'amount_staked',
        'real_bonus_participation_amount',
        'play_bonus_participation_amount',
        'real_bonus_staked_amount',
        'amount_staked_resulting_play_bonus',
        'taxable_amount',
        'amount_returned_winnings',
        'amount_returned_resulting_jackpots',
        'amount_returned_resulting_additional_jackpots',
        'amount_returned_assigned_as_real_bonus',
        'amount_giver_over_play_bonus',
        'code_license_account_holder',
        'network_code',
        'gambling_account',
        'end_stage_progressive_number',
        'date_final_balance',
        'jackpot_fund_amount',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->date_final_balance)) {
            $this->date_final_balance = (new DateTimeType())->fill($this->date_final_balance);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray(
            array_merge(
                [
                    $this->central_system_session_id,
                    $this->participation_id_code,
                    $this->number_stage_undertaken_player,
                    $this->participation_amount,
                    $this->real_bonus_participation_amount,
                    $this->play_bonus_participation_amount,
                    $this->amount_staked,
                    $this->real_bonus_staked_amount,
                    $this->amount_staked_resulting_play_bonus,
                    $this->taxable_amount,
                    $this->amount_returned_winnings,
                    $this->amount_returned_resulting_jackpots,
                    $this->amount_returned_resulting_additional_jackpots,
                    $this->amount_returned_assigned_as_real_bonus,
                    $this->amount_giver_over_play_bonus,
                    $this->code_license_account_holder,
                    $this->network_code,
                    mb_strlen($this->gambling_account, '8bit'),
                    $this->gambling_account,
                    $this->end_stage_progressive_number
                ],
                $this->date_final_balance->toArray(),
                [$this->jackpot_fund_amount]
            )
        );
    }
}