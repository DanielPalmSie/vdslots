<?php

namespace ES\ICS\Constants;

class ICSConstants
{
    public const REGULATION_NAME = 'ICS';
    public const COUNTRY = 'ES';

    public const ITEMS_PER_SUBRECORD = 1000;
    public const RECORD_PER_BATCH = 10;

    public const XMLNS_URL = 'http://cnjuego.gob.es/sci/v{version}.xsd';
    public const SCHEMA_LOCATION = 'CNJ_Monitorizacion_{version}.xsd http://www.w3.org/2000/09/xmldsig# xmldsig-core-schema.xsd';
    public const XMLNS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public const DAILY_FREQUENCY = 'Daily';
    public const MONTHLY_FREQUENCY = 'Monthly';
    public const REALTIME_FREQUENCY = 'Realtime';

    public const FREQUENCY_VALUES = [
        self::DAILY_FREQUENCY => 'Diaria',
        self::MONTHLY_FREQUENCY => 'Mensual'
    ];

    public const FREQUENCY_DIRECTORY_VALUES = [
        self::DAILY_FREQUENCY => 'Diario',
        self::MONTHLY_FREQUENCY => 'Mensual'
    ];

    public const FREQUENCY_FILE_NAME_VALUES = [
        self::DAILY_FREQUENCY => 'D',
        self::MONTHLY_FREQUENCY => 'M'
    ];

    public const MALE_GENDER = 'M';
    public const FEMALE_GENDER = 'F';

    // TODO LEON check if we have some const for the viodeslots constants

    public const GENDER_VALUES = [
        'Male' => self::MALE_GENDER,
        'Female' => self::FEMALE_GENDER
    ];

    public const DATETIME_FORMAT = 'YmdHis';
    public const DATETIME_TO_GMT_FORMAT = 'YmdHisO';
    public const MONTH_FORMAT = 'Ym';
    public const DAY_FORMAT = 'Ymd';
    public const TIME_FORMAT = 'His';
    public const DATETIME_DBFORMAT = 'Y-m-d H:i:s';

    public const VERIFIED = 'S';
    public const NOT_VERIFIED = 'N';

    public const INITIAL_DATE = '1970-01-01 00:00:00';
    public const CURRENCY = 'EUR';

    /**
     *  Player Status
     */
    // Reflects  the  status  of  a  gambler  duly  identified  and  verified  through  documents
    public const ACTIVE = 'A';

    // Reflects the status of a resident gambler whose identity has not been verified reliably through a document verification system.
    public const PENDING_DOCUMENT_VERIFICATION = 'PV';

    // This  is  the  status  of  the  gambler  who  the  operator  has  decided  to  suspend after 2 years of uninterrupted inactivity.
    public const SUSPENDED = 'S';

    // This is the status of the gambler who has been cancelled after 4 years of suspension.
    public const CANCELED = 'C';

    // This is the status of the gambler with a precautionary suspension  from  the  operator  for  suspected  collusion
    // or  fraudulent  behaviour  or  for  having allowed third parties to use the user record.
    public const PRECAUTIONARY_SUSPENSION = 'SC';

    // This is the status of the gambler who, after having been suspended  on  a  precautionary  basis,
    // the  operator  has  considered  it  proven  that  the  participant had taken part in fraud, collusion or allowed
    // third parties to use the account, and the operator has decided to unilaterally terminate the contract
    public const CONTRACT_CANCELLATION = 'AC';

    // This  is  the  status  of  the  gambler  subject  to  any  of  the  individual prohibitions stipulated
    // in article 6 of Gambling Act 13/2011 (minors, registered in the RGIAJ, connected parties, etc.)
    public const INDIVIDUAL_PROHIBITION = 'PR';

    // This  is  the  status  of  the  gambler  who  has  voluntarily  excluded  themselves from the gambling offered by the operator.
    public const SELF_EXCLUDED = 'AE';

    // Other situations in which a gambler may be found and not included in any of those before.
    public const OTHERS = 'O';

    // This status has been set from a third party service check
    public const DECEASED = 'CD';

    // Document type
    public const ID_DOCUMENT_TYPE = 'ID';

    // Minimum transactions amount
    public const MINIMUM_TRANSACTION_AMOUNT_CENTS = 100;

    // Used by real time report to configure interval minutes
    public const REAL_TIME_MINUTES = 15;
    // Used by real time report to configure batch entries
    public const REAL_TIME_ENTRIES = 500;

    public const BONUS_DESCRIPTION_RELEASE = 'LIBERACION';
    public const BONUS_DESCRIPTION_CANCELLATION = 'CANCELACION';
    public const BONUS_DESCRIPTION_CONCESSION = 'CONCESION';

    public const BONUS_TYPE_DESCRIPTIONS = [
        14 => self::BONUS_DESCRIPTION_CONCESSION,
        32 => self::BONUS_DESCRIPTION_CONCESSION,
        66 => self::BONUS_DESCRIPTION_CONCESSION,
        69 => self::BONUS_DESCRIPTION_CONCESSION,
        74 => self::BONUS_DESCRIPTION_CONCESSION,
        77 => self::BONUS_DESCRIPTION_CONCESSION,
        80 => self::BONUS_DESCRIPTION_CONCESSION,
        82 => self::BONUS_DESCRIPTION_CONCESSION,
        84 => self::BONUS_DESCRIPTION_CONCESSION,
        85 => self::BONUS_DESCRIPTION_CONCESSION,
        86 => self::BONUS_DESCRIPTION_CONCESSION,
        90 => self::BONUS_DESCRIPTION_CONCESSION,
        94 => self::BONUS_DESCRIPTION_CONCESSION,
        95 => self::BONUS_DESCRIPTION_CONCESSION,
        96 => self::BONUS_DESCRIPTION_CONCESSION,
    ];

    public const FS_WIN_TYPE = 3;
    public const FRB_COST = 51;

    // TipoMedioPago for generic cards
    public const UNDEFINED_CARD_TYPE = '13';

    public const TRANSACTION_RESULT_OK = 'OK';
    public const TRANSACTION_RESULT_CANCELLED_BY_PLAYER = 'CU';
    public const TRANSACTION_RESULT_CANCELLED_BY_OPERATOR = 'CO';
    public const TRANSACTION_RESULT_CANCELLED_BY_PAYMENT_GATEWAY = 'CM';
    public const TRANSACTION_RESULT_OTHER = 'OT';

}
