<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.1
 * Final class SalesNetworkCodes
 */
final class SalesNetworkCodes extends AbstractTable
{
    public static $sport_public_games = 2;

    /**
     * Includes: Bersani and Italian Legislative Decree 149/08
     * @var int
     */
    public static $horse_public_games = 3;

    /**
     * Includes: Renewed horse racing bets and race tracks
     * @var int
     */
    public static $renewed_horse_betting = 7;

    public static $renewed_sport_betting = 8;

    public static $super_enalotto = 12;

    public static $bingo = 13;

    public static $gad_licenses = 14;

    /**
     * Remote Instant Lotteries
     * @var int
     */
    public static $lit_licence = 15;

}