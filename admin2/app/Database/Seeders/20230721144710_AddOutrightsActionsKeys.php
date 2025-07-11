<?php

use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\FManager as DB;

class AddOutrightsActionsKeys extends SeederTranslation
{
    protected const TRANSLATIONS = [
        'sb.matches' => 'Matches',
        'sb.outrights' => 'Outrights',
        'sb.show.more' => 'Show More',
        'sb.show.less' => 'Show Less',
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
