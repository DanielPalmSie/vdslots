<?php

namespace ES\ICS\Validation;
use LogicException;

class InternalVersionMismatchException extends LogicException
{

    public function __construct()
    {
        parent::__construct(
            'Internal versions are not the same',
            0,
            null
        );
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}