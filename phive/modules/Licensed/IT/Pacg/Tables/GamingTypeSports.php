<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeSports extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $fixed_odds_sports_betting = 1;

    public static $fixed_odds_sports_betting_direct_interaction = 2;

}