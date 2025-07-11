<?php

declare(strict_types=1);

namespace ES\ICS\Reports;

abstract class BaseProxy
{
    public const TYPE = 'abstract';
    public const SUBTYPE = 'abstract';

    protected BaseReport $report;

    /** @throws \Exception */
    public function __construct(string $iso, array $lic_settings = [], array $report_settings = [])
    {
        $className = $this->getVersionClassName();
        $this->report = new $className($iso, $lic_settings, $report_settings);
    }

    public function __call($method, $arguments)
    {
        if (!method_exists($this->report, $method)) {
            throw new \BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', get_class($this->report),
                $method
            ));
        }

        return call_user_func_array([$this->report, $method], $arguments);
    }

    /**
     * @throws \Exception
     * @return string Report class name
     */
    private function getVersionClassName(): string
    {
        $version = Info::getVersion();
        $reportSubtype = $this->reportSubtype();
        $classPath = __DIR__ . "/v$version/$reportSubtype.php";

        if (!file_exists($classPath)) {
            throw new \Exception("ICS Report file {$classPath} does not exist");
        };

        $className = "\\ES\\ICS\\Reports\\v$version\\$reportSubtype";

        if (!class_exists($className)) {
            throw new \Exception("ICS Report class {$className} does not exist");
        }

        return $className;
    }

    public function reportSubtype(): string
    {
        return static::SUBTYPE;
    }
}
