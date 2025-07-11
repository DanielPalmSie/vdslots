<?php
namespace IT\Pgda\Type;

use IT\Abstractions\AbstractEntity;
use IT\Pgda\Services\PgdaEntity;

/**
 * Class SoftwareModuleListType
 * @package IT\Pgda\Type
 */
class SoftwareModuleListType extends PgdaEntity
{
    /**
     * @var SoftwareModuleType[]
     */
    protected $software_modules = [];

    /**
     * @var array
     */
    protected $fillable = [
        'software_modules',
    ];

    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->setSoftwareModules();
        return $this;
    }

    protected function setSoftwareModules()
    {
        $software_modules = $this->software_modules;
        $this->software_modules = [];
        foreach ($software_modules as $key => $software_module) {
            $this->software_modules[$key] = (new SoftwareModuleType())->fill($software_module);
            if (! empty($this->software_modules[$key]->errors)) {
                $this->errors = array_merge($this->errors, $this->software_modules[$key]->errors);
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getSoftwareModule(): array
    {
        $result_software_modules = [];
        foreach ($this->software_modules as $software_module) {
            if (! ($software_module instanceof SoftwareModuleType)) {
                throw new \Exception('Attributes Session item isn\'t a SoftwareModuleType object');
            }
            foreach ($software_module->toArray() as $name => $field) {
                $result_software_modules[] = $field;
            }
        }

        return $result_software_modules;
    }

    /**
     * @return int
     */
    public function getNumberSoftwareModules(): int
    {
        return count($this->software_modules);
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        $format = "";
        $format_string = end($this->software_modules)->getFormat();
        for ($i = 0; $i < $this->getNumberSoftwareModules(); $i++) {
            $format .= $format_string;
        }

        return $format;
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    public function toArray(array $array = []): array
    {
        return $this->getSoftwareModule();
    }
}