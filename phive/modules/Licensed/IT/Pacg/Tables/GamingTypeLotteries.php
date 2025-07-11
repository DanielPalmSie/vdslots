<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.8
 * Final class GamingType
 */
final class GamingTypeLotteries extends AbstractTable
{
    /**
     * ** The Game Code 0 (zero) of each family acts as a wildcard and indicates, where applicable, all the games of the family.
     * @var int
     */
    public static $wildcard = 0;

    public static $online_scratch_cards = 1;


}