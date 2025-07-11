<?php
namespace App\Exceptions;

use RuntimeException;

class UpdateLiabilitiesException extends RuntimeException
{
    protected $message = "misc_cache 'liability-report-adjusted-month' cannot be found or not in format Y-m";
}
