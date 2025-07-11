<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\DateTimeType;
use IT\Traits\BinaryTrait;

/**
 * Acquisition of participation right message (420)
 * Class AcquisitionParticipationRightMessageEntity
 * @package IT\Pgda\Entity
 */
class AcquisitionParticipationRightMessageEntity extends PgdaEntity
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
    public $progressive_participation_number;

    /**
     * @var int
     */
    public $participation_fee;

    /**
     * @var int
     */
    public $real_bonus_participation_fee;

    /**
     * @var int
     */
    public $participation_amount_resulting_play_bonus;

    /**
     * @var int
     */
    public $regional_code;

    /**
     * @var string
     */
    public $ip_address;

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
     * @var string
     */
    public $player_pseudonym;

    /**
     * @var DateTimeType
     */
    public $date_participation;

    /**
     * @var int
     */
    public $initial_stage_progressive_number;

    /**
     * @var int
     */
    public $code_type_tag;

    /**
     * @var string
     */
    protected $format = "A16A16N4CA15NnCA*CA*n6Nn";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'central_system_session_id',
        'participation_id_code',
        'progressive_participation_number',
        'participation_fee',
        'real_bonus_participation_fee',
        'participation_amount_resulting_play_bonus',
        'regional_code',
        'ip_address',
        'code_license_account_holder',
        'network_code',
        'gambling_account',
        'player_pseudonym',
        'date_participation',
        'initial_stage_progressive_number',
        'code_type_tag'
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->date_participation)) {
            $this->date_participation = (new DateTimeType())->fill($this->date_participation);
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
                    $this->progressive_participation_number,
                    $this->participation_fee,
                    $this->real_bonus_participation_fee,
                    $this->participation_amount_resulting_play_bonus,
                    $this->regional_code,
                    $this->ip_address,
                    $this->code_license_account_holder,
                    $this->network_code,
                    mb_strlen($this->gambling_account, '8bit'),
                    $this->gambling_account,
                    mb_strlen($this->player_pseudonym, '8bit'),
                    $this->player_pseudonym,
                ],
                $this->date_participation->toArray(),
                [
                    $this->initial_stage_progressive_number,
                    $this->code_type_tag,
                ]
            )
        );
    }
}