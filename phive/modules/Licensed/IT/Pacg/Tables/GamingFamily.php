<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.7
 * Final class GamingFamily
 */
final class GamingFamily extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $sports_betting = 1;

    public static $bookshop_horse_race_betting = 2;

    public static $retailer_horse_race_betting = 3;

    public static $retailer_spots_betting = 4;

    public static $sport_pool_contests = 5;

    /**
     * include also: card games in tournament, fixed odds change games as well as card games non in tournament both in solitaire and among players
     * @var int
     */
    public static $skill_games = 6;

    public static $number_games = 7;

    public static $bingo = 8;

    public static $remote_lotteries = 9;

    public static $virtual_betting = 10;

    public static $lotto = 11;


}