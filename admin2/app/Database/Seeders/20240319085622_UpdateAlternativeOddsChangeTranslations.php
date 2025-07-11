<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class UpdateAlternativeOddsChangeTranslations extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $stringConnectionsData = [
        'tag' => 'sb',
        'bonus_code' => 0
    ];

    protected const TRANSLATIONS_DEFAULT = [
        'sb.alternative_odds_change.none' => 'No Odds Changes Accepted',
        'sb.alternative_odds_change.any' => 'Accept Any Odds',
        'sb.alternative_odds_change.higher' => 'Accept Only Higher Odds',
        'sb.alternative_odds_change_rejection_message' => 'Bet rejected as odds have changed',
    ];

    protected const TRANSLATIONS_CL_ES_PE = [
        'sb.alternative_odds_change.none' => 'No se aceptan cambios de cuotas',
        'sb.alternative_odds_change.any' => 'Aceptar cualquier posibilidad',
        'sb.alternative_odds_change.higher' => 'Sólo mayores probabilidades',
        'sb.alternative_odds_change_rejection_message' => 'Apuesta rechazada porque las cuotas han cambiado',
    ];

    protected array $data = [
        'en' => self::TRANSLATIONS_DEFAULT,
        'br' => [
            'sb.alternative_odds_change.none' => 'Não são aceitas alterações nas probabilidades',
            'sb.alternative_odds_change.any' => 'Aceitar todas as probabilidades',
            'sb.alternative_odds_change.higher' => 'Somente chances maiores',
            'sb.alternative_odds_change_rejection_message' => 'Aposta rejeitada porque as chances foram alteradas',
        ],
        'cl' => self::TRANSLATIONS_CL_ES_PE,
        'de' => self::TRANSLATIONS_DEFAULT,
        'es' => self::TRANSLATIONS_CL_ES_PE,
        'fi' => [
            'sb.alternative_odds_change.none' => 'Kertoimien muutoksia ei hyväksytä',
            'sb.alternative_odds_change.any' => 'Hyväksy kaikki mahdollisuudet',
            'sb.alternative_odds_change.higher' => 'Vain korkeammat kertoimet',
            'sb.alternative_odds_change_rejection_message' => 'Veto on hylätty, koska kertoimet ovat muuttuneet',
        ],
        'hi' => self::TRANSLATIONS_DEFAULT,
        'it' => [
            'sb.alternative_odds_change.none' => 'Non si accettano modifiche delle quote',
            'sb.alternative_odds_change.any' => 'Accettare qualsiasi probabilità',
            'sb.alternative_odds_change.higher' => 'Solo probabilità più alte',
            'sb.alternative_odds_change_rejection_message' => 'Scommessa rifiutata perché le quote sono cambiate',
        ],
        'ja' => self::TRANSLATIONS_DEFAULT,
        'nl' => self::TRANSLATIONS_DEFAULT,
        'no' => [
            'sb.alternative_odds_change.none' => 'No se aceptan cambios de cuotas',
            'sb.alternative_odds_change.any' => 'Aksepter alle odds',
            'sb.alternative_odds_change.higher' => 'Bare høyere odds',
            'sb.alternative_odds_change_rejection_message' => 'Spillet avvises fordi oddsen har endret seg',
        ],
        'pe' => self::TRANSLATIONS_CL_ES_PE,
        'sv' => [
            'sb.alternative_odds_change.none' => 'Inga oddsändringar accepteras',
            'sb.alternative_odds_change.any' => 'Acceptera alla odds',
            'sb.alternative_odds_change.higher' => 'Endast högre odds',
            'sb.alternative_odds_change_rejection_message' => 'Spelet avvisas eftersom oddsen har ändrats',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->update(['value' => $value]);
                } else {
                    $this->connection
                        ->table($this->table)
                        ->insert([
                            [
                                'alias' => $alias,
                                'language' => $language,
                                'value' => $value,
                            ]
                        ]);
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->data as $language => $translation) {
            $translation = self::TRANSLATIONS_DEFAULT;
            foreach ($translation as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->update(['value' => $value]);
                }
            }
        }
    }
}
