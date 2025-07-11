<?php

require_once 'Abstract/AbstractTable.php';

/**
 * Codes of the game types identified based on the subdivisions established by law.
 * Section 8.7
 * Final class GameTypeCode
 */
final class GameTypeCode extends AbstractTable
{

    public static $no_limit = "NL";

    public static $fixed_limit = "FL";

    public static $pot_limit = "PL";

    /**
     * PL + FL
     * @var string
     */
    public static $mixed_mode = "MM";

    public static $high_low = "HL";

    public static $spread_limit = "SL";

    public static $cap_limit = "CL";



}