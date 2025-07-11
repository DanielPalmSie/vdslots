<?php

use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\FManager as DB;

class AddDeadHeatFactorExplanation extends SeederTranslation
{
    protected const TRANSLATIONS = [
        'sb.betslip.my_bets.dead_heat_factor_explanation' => 'A Dead Heat is calculated by dividing the stake proportionally between the number of winners in the event'
    ];

    protected array $stringConnectionsData = [
        'tag' => 'sb.betslip',
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