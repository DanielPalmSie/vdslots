<?php

require_once 'Abstract/AbstractCode.php';

/**
 * Section 8.3
 * Class AnomalyCode
 */
class AnomalyCode extends AbstractCode
{
    /**
     * TODO Add the other codes
     * @var array
     */
    protected static $codes = [
        1000 => "PRIZE PLAN MESSAGE MISSING",
        1001 => "WINNER LIST MESSAGE MISSING",
        1002 => "PAYMENT MESSAGE MISSING",
        1003 => "SESSION END MESSAGE MISSING",
        1004 => "END SESSION DATE NON-COMPLIANT",
        2000 => "COMMUNICATION OF FIXED ODDS GAME EXECUTION MESSAGE MISSING",
        2001 => "DAILY ALIGNMENT OF DAILY FIXED ODDS MESSAGE MISSING",
        3000 => "COMMUNICATION OF CASH GAME EXECUTION MESSAGE MISSING",
        3001 => "DAILY ALIGNMENT OF DAILY CASH GAME MESSAGE MISSING"
    ];




}