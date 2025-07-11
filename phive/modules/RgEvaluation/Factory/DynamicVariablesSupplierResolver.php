<?php

namespace RgEvaluation\Factory;

use DBUser;
use ReflectionClass;
use ReflectionException;

class DynamicVariablesSupplierResolver
{
    private array $factories = [];
    private DBUser $user;

    public function __construct(DbUser $user)
    {
        $this->user = $user;
    }

    public function addFactory(string $name, DynamicVariablesSupplier $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function resolve(string $name): DynamicVariablesSupplier
    {
        if (!empty($this->factories[$name])) {
            return $this->factories[$name];
        }

        try {
            $triggerClassName = "RgEvaluation\Factory\\" . $name . "DynamicVariablesSupplier";
            $this->factories[$name] = (new ReflectionClass($triggerClassName))->newInstance($this->user);
            return $this->factories[$name];
        } catch (ReflectionException $e) {
            return new NullObjectDynamicVariablesSupplier($this->user);
        }
    }
}
