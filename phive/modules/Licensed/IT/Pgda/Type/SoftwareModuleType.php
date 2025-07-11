<?php
namespace IT\Pgda\Type;

use IT\Pgda\Services\PgdaEntity;

class SoftwareModuleType extends PgdaEntity
{
    /**
     * @var int
     */
    protected $type;

    /**
     * @var int
     */
    protected $item_code;

    /**
     * @var string
     */
    protected $detail;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var string
     */
    protected $format = "CNA40A40";

    /**
     * @var array
     */
    protected $fillable = [
        'type',
        'item_code',
        'detail',
        'hash',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            $this->type,
            $this->item_code,
            $this->detail,
            $this->hash,
        ];
    }
}