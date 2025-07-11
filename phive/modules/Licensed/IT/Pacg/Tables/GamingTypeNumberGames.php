<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeNumberGames extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $superenalotto = 1;

    public static $win_4_life = 2;

    public static $superstar = 3;

    public static $win_4_life_gold = 4;

    public static $si_vince_tutto = 5;

    public static $wfl_viva_italia = 6;

    public static $wfl_grattacieli = 7;

    public static $wfl_cassaforte = 8;

    public static $euro_jackpot = 9;

    public static $wfl_classico = 10;

    public static $_6_x_36 = 11;

    public static $vinci_casa = 12;

}