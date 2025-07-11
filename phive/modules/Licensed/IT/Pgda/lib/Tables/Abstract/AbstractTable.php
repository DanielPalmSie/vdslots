<?php

abstract class AbstractTable
{
    /**
     * @return array
     * @throws ReflectionException
     */
    public static function getAllowedValues(): array
    {
        $class = new ReflectionClass(get_called_class());
        $arr = $class->getStaticProperties();
        return array_values($arr);
    }

}