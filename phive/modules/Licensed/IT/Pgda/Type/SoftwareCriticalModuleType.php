<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Client\PgdaClient;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class SoftwareModuleType
 * @package IT\Pgda\Type
 */
class SoftwareCriticalModuleType extends PgdaEntity
{
    /**
     * @var string
     */
    protected $name_critical_module;

    /**
     * @var string
     */
    protected $hash_critical_module;

    /**
     * @var string
     */
    protected $format = 'CA*A40';

    /**
     * @var array
     */
    protected $fillable = [
        'name_critical_module',
        'hash_critical_module',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            mb_strlen($this->name_critical_module, '8bit'),
            $this->name_critical_module,
            $this->hash_critical_module,
        ];
    }
}