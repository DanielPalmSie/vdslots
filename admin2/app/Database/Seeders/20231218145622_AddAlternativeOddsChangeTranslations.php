<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddAlternativeOddsChangeTranslations extends SeederTranslation
{

    protected const TRANSLATIONS = [
        'sb.alternative_odds_change.none' => 'No Odds Changes Accepted',
        'sb.alternative_odds_change.any' => 'Accept Any Odds',
        'sb.alternative_odds_change.higher' => 'Accept Only Higher Odds',
        'sb.alternative_odds_change_rejection_message' => 'Bet rejected as odds have changed',
    ];

    protected array $stringConnectionsData = [
        'tag' => 'sb',
        'bonus_code' => 0
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
