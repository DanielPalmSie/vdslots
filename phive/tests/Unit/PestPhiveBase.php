<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../../phive/phive.php';

use PHPUnit\Framework\TestCase;
use \Tests\Traits\HttpRequestTrait;

class PestPhiveBase extends TestCase
{
    use HttpRequestTrait;
}