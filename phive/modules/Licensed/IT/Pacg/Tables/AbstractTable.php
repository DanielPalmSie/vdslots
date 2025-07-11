<?php
namespace IT\Pacg\Tables;

abstract class AbstractTable
{
    /**
     * @return array
     */
    public static function getAllowedValues(): array
    {
        $class = new \ReflectionClass(get_called_class());
        return array_values($class->getStaticProperties());
    }

    /**
     * @return array|null
     */
    public static function getStaticProperties(): ?array
    {
        $class = new \ReflectionClass(get_called_class());
        return $class->getStaticProperties();
    }

}