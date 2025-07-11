<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeLotto extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $lotto = 1;

    public static $_10_and_lotto = 2;

    public static $_10_and_frequent_lotto = 3;

    public static $_10_and_instant_lotto = 4;

    public static $milion_day = 5;


}