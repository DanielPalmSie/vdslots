<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddSbMaintenanceTranslations extends SeederTranslation
{

    protected const TRANSLATIONS = [
        'sb.maintenance.header' => "This page is currently for Maintenance. We'll be back soon!",
        'sb.maintenance.body' => '<p> We apologise for any inconvenience.
        For further assistance please do not hesitate to get in contact with our Customer Support via chat or e-mail on:
        <a href="mailto:{{support_email}}"> {{support_email}} </a> </p>'
    ];

    protected array $stringConnectionsData = [
        'tag' => 'sb.maintenance',
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
