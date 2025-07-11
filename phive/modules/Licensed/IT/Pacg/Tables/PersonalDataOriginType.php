<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.15
 * Final class PersonalDataOriginType
 */
final class PersonalDataOriginType extends AbstractTable
{

    public static int $manual = 1; // Personal data is coming from manual input

    public static int $spid = 2; // SPID (Public System of Digital Identity)

}