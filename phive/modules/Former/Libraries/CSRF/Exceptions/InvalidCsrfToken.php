<?php

namespace FormerLibrary\CSRF\Exceptions;

class InvalidCsrfToken extends \Exception
{

    public function __construct($message = 'Invalid CSRF token', $context = null)
    {
        phive('Logger')->error('csrf-token-error' , ['error' => $message, 'context' => $context]);

        parent::__construct($message);
    }
}
