<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeRetailerSports extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $big_match = 1;

    public static $big_race_auto_or_moto = 2;

    public static $big_race_bicycle = 3;

    public static $big_race_ski = 4;

    public static $big_race_ski_2 = 5;

}