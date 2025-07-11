<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeSkillGames extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $tournament_card_games = 1;

    public static $fixed_odds_chance_games = 2;

    public static $card_games_organised_different_from_tournament = 3;


}