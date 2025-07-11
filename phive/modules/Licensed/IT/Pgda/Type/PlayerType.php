<?php
namespace IT\Pgda\Type;

use IT\Pgda\Services\PgdaEntity;

/**
 * Class PlayerType
 * @package IT\Pgda\Type
 */
class PlayerType extends PgdaEntity
{
    /**
     * @var string
     */
    public $identifier;

    /**
     * @var int
     */
    public $amount_available;

    /**
     * @var int
     */
    public $amount_returned;

    /**
     * @var int
     */
    public $bet_amount;

    /**
     * @var int
     */
    public $taxable_amount;

    /**
     * @var int
     */
    public $license_code;

    /**
     * @var int
     */
    public $jackpot_amount;

    /**
     * @var int
     */
    public $amount_available_real_bonuses;

    /**
     * @var int
     */
    public $amount_available_play_bonuses;

    /**
     * @var int
     */
    public $amount_waged_real_bonuses;

    /**
     * @var int
     */
    public $amount_staked_resulting_play_bonuses;

    /**
     * @var int
     */
    public $amount_returned_real_bonuses;

    /**
     * @var int
     */
    public $amount_returned_play_bonuses;

    /**
     * @var int
     */
    public $amount_returned_resulting_jackpots;

    /**
     * @var int
     */
    public $amount_returned_resulting_additional_jackpots;

    /**
     * @var string
     */
    protected $format = 'A16N3JN10';

    /**
     * @var array
     */
    protected $fillable = [
        'identifier',
        'amount_available',
        'amount_returned',
        'bet_amount',
        'taxable_amount',
        'license_code',
        'jackpot_amount',
        'amount_available_real_bonuses',
        'amount_available_play_bonuses',
        'amount_waged_real_bonuses',
        'amount_staked_resulting_play_bonuses',
        'amount_returned_real_bonuses',
        'amount_returned_play_bonuses',
        'amount_returned_resulting_jackpots',
        'amount_returned_resulting_additional_jackpots',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            $this->identifier,
            $this->amount_available,
            $this->amount_returned,
            $this->bet_amount,
            $this->taxable_amount,
            $this->license_code,
            $this->jackpot_amount,
            $this->amount_available_real_bonuses,
            $this->amount_available_play_bonuses,
            $this->amount_waged_real_bonuses,
            $this->amount_staked_resulting_play_bonuses,
            $this->amount_returned_real_bonuses,
            $this->amount_returned_play_bonuses,
            $this->amount_returned_resulting_jackpots,
            $this->amount_returned_resulting_additional_jackpots,
        ];
    }
}