<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeRetailerHorse extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $national_horse_race_betting = 1;

    public static $internation_horse_race_betting = 2;

    public static $v7 = 3;


}