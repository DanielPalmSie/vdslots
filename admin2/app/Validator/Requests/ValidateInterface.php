<?php

namespace App\Validator\Requests;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

interface ValidateInterface
{
    public function validate();
}