<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Type\SoftwareCriticalModuleListType;

/**
 * Installed software version communication message (831)
 * Class InstalledSoftwareVersionCommunicationEntity
 * @package IT\Pgda\Services
 */
class InstalledSoftwareVersionCommunicationEntity extends PgdaEntity
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
     * @var int
     */
    public $cod_element_type;

    /**
     * @var int
     */
    public $cod_element;

    /**
     * @var int
     */
    public $prog_cert_version;

    /**
     * @var int
     */
    public $prog_sub_cert_version;

    /**
     * @var SoftwareCriticalModuleListType
     */
    public $software_modules;

    /**
     * @var string
     */
    protected $format = "CNC2n";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'cod_element_type',
        'cod_element',
        'prog_cert_version',
        'prog_sub_cert_version',
        'software_modules',
    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);

        if (is_array($this->software_modules)) {
            $this->software_modules = (new SoftwareCriticalModuleListType())->fill(['software_modules' => $this->software_modules]);
            $this->format .= $this->software_modules->getFormat();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray(
            array_merge(
                [
                    $this->cod_element_type,
                    $this->cod_element,
                    $this->prog_cert_version,
                    $this->prog_sub_cert_version,
                    $this->software_modules->getNumberSoftwareModules(),
                ],
                $this->software_modules->toArray()
            )
        );
    }
}