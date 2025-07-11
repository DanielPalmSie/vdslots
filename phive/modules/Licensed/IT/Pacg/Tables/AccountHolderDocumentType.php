<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.4
 * Final class AccountHolderDocumentType
 */
final class AccountHolderDocumentType extends AbstractTable
{

    public static $identity_card = 1;

    public static $driver_license = 2;

    public static $passport = 3;

    public static $personal_identification_card_mod_at = 4;

    public static $personal_identification_card_mod_bt = 5;

    public static $firearm_license = 6;

    public static $other = 10;

}