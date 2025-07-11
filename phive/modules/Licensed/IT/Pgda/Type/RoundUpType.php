<?php

namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Client\PgdaClient;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class RoundUpType
 * @package IT\Pgda\Type
 */
class RoundUpType extends PgdaEntity
{
    /**
     * @var int
     */
    protected $license_code;

    /**
     * @var int
     */
    protected $total_amounts_waged;

    /**
     * @var int
     */
    protected $total_amounts_returned;

    /**
     * @var int
     */
    protected $total_taxable_amount;

    /**
     * @var int
     */
    protected $total_mount_returned_resulting_jackpot;

    /**
     * @var int
     */
    protected $total_mount_returned_resulting_additional_jackpot;

    /**
     * @var int
     */
    protected $jackpot_amount;

    /**
     * @var int
     */
    protected $total_amount_waged_real_bonuses;

    /**
     * @var int
     */
    protected $total_amount_waged_play_bonuses;

    /**
     * @var int
     */
    protected $total_amount_returned_real_bonuses;

    /**
     * @var int
     */
    protected $total_amount_returned_play_bonuses;

    /**
     * @var string
     */
    protected $format = 'NJ10';

    /**
     * @var array
     */
    protected $fillable = [
        'license_code',
        'total_amounts_waged',
        'total_amounts_returned',
        'total_taxable_amount',
        'total_mount_returned_resulting_jackpot',
        'total_mount_returned_resulting_additional_jackpot',
        'jackpot_amount',
        'total_amount_waged_real_bonuses',
        'total_amount_waged_play_bonuses',
        'total_amount_returned_real_bonuses',
        'total_amount_returned_play_bonuses',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            $this->license_code,
            $this->total_amounts_waged,
            $this->total_amounts_returned,
            $this->total_taxable_amount,
            $this->total_mount_returned_resulting_jackpot,
            $this->total_mount_returned_resulting_additional_jackpot,
            $this->jackpot_amount,
            $this->total_amount_waged_real_bonuses,
            $this->total_amount_waged_play_bonuses,
            $this->total_amount_returned_real_bonuses,
            $this->total_amount_returned_play_bonuses,
        ];
    }
}