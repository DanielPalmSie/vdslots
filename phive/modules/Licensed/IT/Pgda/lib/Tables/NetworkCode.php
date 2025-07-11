<?php

require_once 'Abstract/AbstractTable.php';

/**
 * //TODO Check the type of properties (integer or string)
 * Network identification codes to be used in message 220/420/620, 280/430/630, 340, 510.
 * Section 8.6
 * Final class NetworkCode
 */
final class NetworkCode extends AbstractTable
{
    /**
     * BERSANI SPORTS LICENSEES pursuant to Art. 38, paragraph 2 of Italian Legislative Decree 223/2006
     * @var string
     */
    public static $public_sport_games = "2";

    /**
     * INCLUDES:
     *  - BERSANI HORSE RACING LICENSEES pursuant to art. 38, paragraph 4 of Italian Legislative Decree 223/2006
     *  - HORSE RACING LICENSEES Italian Legislative Decree 149/08
     * @var string
     */
    public static $public_horse_racing_games = "3";

    /**
     * INCLUDES:
     *  - HORSE RACING BETTING SHOPS
     *  - RACE TRACKS
     * @var string
     */
    public static $novel_horse_racing_bets = "7";

    /**
     * SPORTS BETTING SHOPS
     * @var string
     */
    public static $novel_sports_bets = "8";

    public static $superenalotto = "12";

    public static $bingo = "13";

    /**
     * LICENSEES pursuant to art. 24 of Law no. 88 of 07 July 2009.
     * @var string
     */
    public static $remote_gambling = "14";



}