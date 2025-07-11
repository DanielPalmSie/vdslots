<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddVoidStringForSportsbook extends SeederTranslation
{

    protected const TRANSLATIONS = [
        'sb.bet.voided' => 'Void'
    ];

    protected array $stringConnectionsData = [
        'tag' => 'sb.bet',
        'bonus_code' => 0,
    ];

    protected array $data = [
        'en' => self::TRANSLATIONS,
        'br' => self::TRANSLATIONS,
        'cl' => self::TRANSLATIONS,
        'de' => self::TRANSLATIONS,
        'es' => self::TRANSLATIONS,
        'fi' => self::TRANSLATIONS,
        'hi' => self::TRANSLATIONS,
        'it' => self::TRANSLATIONS,
        'ja' => self::TRANSLATIONS,
        'nl' => self::TRANSLATIONS,
        'no' => self::TRANSLATIONS,
        'pe' => self::TRANSLATIONS,
        'sv' => self::TRANSLATIONS
    ];
}