<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Client\PgdaClient;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class AttributesSessionType
 * @package IT\Pgda\Type
 */
class AttributesSessionType extends PgdaEntity
{
    const JACKPOT_INTERNAL = 'JK1';
    const JACKPOT_ADDITIONAL = 'JK2';
    const BONUS = 'BON';
    const MINIMUM_AMOUNT = 'MNI';
    const MAXIMUM_AMOUNT = 'MXI';

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $format = 'A3A16';

    /**
     * @var array
     */
    protected $fillable = [
        'code',
        'value',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            $this->code,
            $this->value,
        ];
    }
}