<?php

require_once 'Abstract/AbstractTable.php';

/**
 * Code types of the tables to be used for message 600.
 * Section 8.5
 * Final class GamingTableType
 */
final class GamingTableType extends AbstractTable
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