<?php


use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForMarketGroupsContents extends Seeder
{

    private Connection $connection;
    private string $table = 'localized_strings';

    protected array $data = [
        'fi' => [
            'sb.betting-group.1' => 'Pää',
            'sb.betting-group.2' => 'Maalit',
            'sb.betting-group.3' => '1. puoliaika',
            'sb.betting-group.4' => '2. puoliaika',
            'sb.betting-group.5' => 'Kulmat',
            'sb.betting-group.6' => 'Kortit',
            'sb.betting-group.7' => 'Maalintekijät',
            'sb.betting-group.8' => 'Pelaaja',
            'sb.betting-group.9' => 'Erityiset',
            'sb.betting-group.10' => 'Nopeat markkinat',
            'sb.betting-group.11' => '5. minuutin markkinat',
            'sb.betting-group.12' => '10. minuutin markkinat',
            'sb.betting-group.13' => '15 minuutin markkinat',
            'sb.betting-group.14' => 'Kaikki',
            'sb.betting-group.15' => 'Erä',
            'sb.betting-group.16' => 'Erityiset',
            'sb.betting-group.17' => 'Kombo',
            'sb.betting-group.18' => 'Pisteet',
            'sb.betting-group.19' => 'Neljännekset',
            'sb.betting-group.20' => 'Asetelmat',
            'sb.betting-group.21' => 'Pelit',
            'sb.betting-group.22' => 'Tries',
            'sb.betting-group.23' => 'Tries HT',
            'sb.betting-group.24' => 'Juoksut',
            'sb.betting-group.25' => 'Vuoroparit',
            'sb.betting-group.26' => '1. vuoropari',
            'sb.betting-group.27' => 'Asetelmat',
            'sb.betting-group.28' => "180's"
        ],
        'sv' => [
            'sb.betting-group.1' => 'Mest populärt',
            'sb.betting-group.2' => 'Mål',
            'sb.betting-group.3' => 'Första halvlek',
            'sb.betting-group.4' => 'Andra halvlek',
            'sb.betting-group.5' => 'Hörnor',
            'sb.betting-group.6' => 'Markeringskort',
            'sb.betting-group.7' => 'Målgörare',
            'sb.betting-group.8' => 'Spelare',
            'sb.betting-group.9' => 'Specialer',
            'sb.betting-group.10' => 'Rapid-marknader',
            'sb.betting-group.11' => '5-minutersmarknader',
            'sb.betting-group.12' => '10-minutersmarknader',
            'sb.betting-group.13' => '15-minutersmarknader',
            'sb.betting-group.14' => 'Alla',
            'sb.betting-group.15' => 'Period',
            'sb.betting-group.16' => 'Specialer',
            'sb.betting-group.17' => 'Kombo',
            'sb.betting-group.18' => 'Poäng',
            'sb.betting-group.19' => 'Kvartslekar',
            'sb.betting-group.20' => 'Sets',
            'sb.betting-group.21' => 'Matcher',
            'sb.betting-group.22' => 'Försök',
            'sb.betting-group.23' => 'Försök HT',
            'sb.betting-group.24' => 'Runs',
            'sb.betting-group.25' => 'Innings',
            'sb.betting-group.26' => 'Första Inning',
            'sb.betting-group.27' => 'Sets',
            'sb.betting-group.28' => '180s'
        ],
        'da' => [
            'sb.betting-group.1' => 'Populære',
            'sb.betting-group.2' => 'Mål',
            'sb.betting-group.3' => '1. halvleg',
            'sb.betting-group.4' => '2. halvleg',
            'sb.betting-group.5' => 'Hjørner',
            'sb.betting-group.6' => 'Booking',
            'sb.betting-group.7' => 'Målscorer',
            'sb.betting-group.8' => 'Spiller',
            'sb.betting-group.9' => 'Specielle',
            'sb.betting-group.10' => 'Hurtige markeder',
            'sb.betting-group.11' => '5 minutters markeder',
            'sb.betting-group.12' => '10 minutter markeder',
            'sb.betting-group.13' => '15 minutter markeder',
            'sb.betting-group.14' => 'Alle',
            'sb.betting-group.15' => 'Periode',
            'sb.betting-group.16' => 'Specielle',
            'sb.betting-group.17' => 'Kombination',
            'sb.betting-group.18' => 'Points',
            'sb.betting-group.19' => 'Quarters',
            'sb.betting-group.20' => 'Sæt',
            'sb.betting-group.21' => 'Spil',
            'sb.betting-group.22' => 'Forsøg',
            'sb.betting-group.23' => 'Forsøg HL',
            'sb.betting-group.24' => 'Runs',
            'sb.betting-group.25' => 'Innings',
            'sb.betting-group.26' => '1. Inning',
            'sb.betting-group.27' => 'Sæt',
            'sb.betting-group.28' => "180's"
        ],
        'es' => [
            'sb.betting-group.1' => 'Principal',
            'sb.betting-group.2' => 'Goles',
            'sb.betting-group.3' => '1.ª parte',
            'sb.betting-group.4' => '2.ª parte',
            'sb.betting-group.5' => 'Córneres',
            'sb.betting-group.6' => 'Tarjetas',
            'sb.betting-group.7' => 'Goleadores',
            'sb.betting-group.8' => 'Jugador',
            'sb.betting-group.9' => 'Especiales',
            'sb.betting-group.10' => 'Mercados rápidos',
            'sb.betting-group.11' => 'Mercados de 5 minutos',
            'sb.betting-group.12' => 'Mercados de 10 minutos',
            'sb.betting-group.13' => 'Mercados de 15 minutos',
            'sb.betting-group.14' => 'Todos',
            'sb.betting-group.15' => 'Periodo',
            'sb.betting-group.16' => 'Especiales',
            'sb.betting-group.17' => 'Combo',
            'sb.betting-group.18' => 'Puntos',
            'sb.betting-group.19' => 'Cuartos',
            'sb.betting-group.20' => 'Sets',
            'sb.betting-group.21' => 'Partidos',
            'sb.betting-group.22' => 'Intentos',
            'sb.betting-group.23' => 'Intentos al descanso',
            'sb.betting-group.24' => 'Carreras',
            'sb.betting-group.25' => 'Entradas',
            'sb.betting-group.26' => '1.ª entrada',
            'sb.betting-group.27' => 'Sets',
            'sb.betting-group.28' => 'N.º de 180'
        ],
        'it' => [
            'sb.betting-group.1' => 'Principali',
            'sb.betting-group.2' => 'Goal',
            'sb.betting-group.3' => '1° Tempo',
            'sb.betting-group.4' => '2° Tempo',
            'sb.betting-group.5' => "Calci d'Angolo",
            'sb.betting-group.6' => 'Cartellini',
            'sb.betting-group.7' => 'Marcatori',
            'sb.betting-group.8' => 'Giocatore',
            'sb.betting-group.9' => 'Speciali',
            'sb.betting-group.10' => 'Scommesse Rapide',
            'sb.betting-group.11' => 'Scommesse a 5 Minuti',
            'sb.betting-group.12' => 'Scommesse a 10 Minuti',
            'sb.betting-group.13' => 'Scommesse a 15 Minuti',
            'sb.betting-group.14' => 'Tutte',
            'sb.betting-group.15' => 'Periodo',
            'sb.betting-group.16' => 'Speciali',
            'sb.betting-group.17' => 'Combo',
            'sb.betting-group.18' => 'Punti',
            'sb.betting-group.19' => 'Quarti',
            'sb.betting-group.20' => 'Set',
            'sb.betting-group.21' => 'Giochi',
            'sb.betting-group.22' => 'Mete',
            'sb.betting-group.23' => 'Mete 1° Tempo',
            'sb.betting-group.24' => 'Punti',
            'sb.betting-group.25' => 'Inning',
            'sb.betting-group.26' => '1° Inning',
            'sb.betting-group.27' => 'Set',
            'sb.betting-group.28' => '180'
        ],
        'ja' => [
            'sb.betting-group.1' => 'メイン',
            'sb.betting-group.2' => 'ゴール',
            'sb.betting-group.3' => '前半',
            'sb.betting-group.4' => '後半',
            'sb.betting-group.5' => 'コーナー',
            'sb.betting-group.6' => 'ブッキング',
            'sb.betting-group.7' => '得点者',
            'sb.betting-group.8' => '選手',
            'sb.betting-group.9' => 'スペシャルズ',
            'sb.betting-group.10' => 'ラピッドマーケット',
            'sb.betting-group.11' => '5分マーケット',
            'sb.betting-group.12' => '10分マーケット',
            'sb.betting-group.13' => '15分マーケット',
            'sb.betting-group.14' => 'すべて',
            'sb.betting-group.15' => 'ピリオド',
            'sb.betting-group.16' => 'スペシャルズ',
            'sb.betting-group.17' => 'コンボ',
            'sb.betting-group.18' => 'ポイント',
            'sb.betting-group.19' => 'クオーター',
            'sb.betting-group.20' => 'セット',
            'sb.betting-group.21' => 'ゲーム',
            'sb.betting-group.22' => 'トライ',
            'sb.betting-group.23' => 'トライ・ハーフタイム',
            'sb.betting-group.24' => 'ラン',
            'sb.betting-group.25' => 'イニング',
            'sb.betting-group.26' => '第1イニング',
            'sb.betting-group.27' => 'セット',
            'sb.betting-group.28' => 'ワンエイティ'
        ],
        'pt' => [
            'sb.betting-group.1' => 'Principal',
            'sb.betting-group.2' => 'Gols',
            'sb.betting-group.3' => '1º tempo',
            'sb.betting-group.4' => '2º tempo',
            'sb.betting-group.5' => 'Escanteios',
            'sb.betting-group.6' => 'Apostas',
            'sb.betting-group.7' => 'Artilheiros',
            'sb.betting-group.8' => 'Jogador',
            'sb.betting-group.9' => 'Especiais',
            'sb.betting-group.10' => 'Mercados rápidos',
            'sb.betting-group.11' => 'Mercados de 5 minutos',
            'sb.betting-group.12' => 'Mercados de 10 minutos',
            'sb.betting-group.13' => 'Mercados de 15 minutos',
            'sb.betting-group.14' => 'Todos',
            'sb.betting-group.15' => 'Período',
            'sb.betting-group.16' => 'Especiais',
            'sb.betting-group.17' => 'Combo',
            'sb.betting-group.18' => 'Pontos',
            'sb.betting-group.19' => 'Quartos',
            'sb.betting-group.20' => 'Sets',
            'sb.betting-group.21' => 'Jogos',
            'sb.betting-group.22' => 'Tries',
            'sb.betting-group.23' => 'Tries PT',
            'sb.betting-group.24' => 'Corridas',
            'sb.betting-group.25' => 'Entradas',
            'sb.betting-group.26' => '1ª entrada',
            'sb.betting-group.27' => 'Sets',
            'sb.betting-group.28' => "180's"
        ],
        'br' => [
            'sb.betting-group.1' => 'Principal',
            'sb.betting-group.2' => 'Gols',
            'sb.betting-group.3' => '1º tempo',
            'sb.betting-group.4' => '2º tempo',
            'sb.betting-group.5' => 'Escanteios',
            'sb.betting-group.6' => 'Apostas',
            'sb.betting-group.7' => 'Artilheiros',
            'sb.betting-group.8' => 'Jogador',
            'sb.betting-group.9' => 'Especiais',
            'sb.betting-group.10' => 'Mercados rápidos',
            'sb.betting-group.11' => 'Mercados de 5 minutos',
            'sb.betting-group.12' => 'Mercados de 10 minutos',
            'sb.betting-group.13' => 'Mercados de 15 minutos',
            'sb.betting-group.14' => 'Todos',
            'sb.betting-group.15' => 'Período',
            'sb.betting-group.16' => 'Especiais',
            'sb.betting-group.17' => 'Combo',
            'sb.betting-group.18' => 'Pontos',
            'sb.betting-group.19' => 'Quartos',
            'sb.betting-group.20' => 'Sets',
            'sb.betting-group.21' => 'Jogos',
            'sb.betting-group.22' => 'Tries',
            'sb.betting-group.23' => 'Tries PT',
            'sb.betting-group.24' => 'Corridas',
            'sb.betting-group.25' => 'Entradas',
            'sb.betting-group.26' => '1ª entrada',
            'sb.betting-group.27' => 'Sets',
            'sb.betting-group.28' => "180's"
        ],
        'hi' => [
            'sb.betting-group.1' => 'मुख्य',
            'sb.betting-group.2' => 'गोल',
            'sb.betting-group.3' => '1ला हाफ़',
            'sb.betting-group.4' => '2रा हाफ़',
            'sb.betting-group.5' => 'कॉर्नर',
            'sb.betting-group.6' => 'बुकिंग',
            'sb.betting-group.7' => 'स्कोर',
            'sb.betting-group.8' => 'प्लेयर',
            'sb.betting-group.9' => 'स्पेशल',
            'sb.betting-group.10' => 'रैपिड मार्केट',
            'sb.betting-group.11' => '5 मिनट मार्केट',
            'sb.betting-group.12' => '10 मिनट मार्केट',
            'sb.betting-group.13' => '15 मिनट मार्केट',
            'sb.betting-group.14' => 'सभी',
            'sb.betting-group.15' => 'पीरियड',
            'sb.betting-group.16' => 'स्पेशल',
            'sb.betting-group.17' => 'कॉम्बो',
            'sb.betting-group.18' => 'पॉइंट',
            'sb.betting-group.19' => 'क्वार्टर',
            'sb.betting-group.20' => 'सेट',
            'sb.betting-group.21' => 'गेम्स',
            'sb.betting-group.22' => 'ट्राइ',
            'sb.betting-group.23' => 'ट्राइ HT',
            'sb.betting-group.24' => 'रन',
            'sb.betting-group.25' => 'इनिंग',
            'sb.betting-group.26' => '1ली इनिंग',
            'sb.betting-group.27' => 'सेट',
            'sb.betting-group.28' => "180's"
        ],
        'de' => [
            'sb.betting-group.1' => 'Hauptwetten',
            'sb.betting-group.2' => 'Tore',
            'sb.betting-group.3' => '1. Halbzeit ',
            'sb.betting-group.4' => '2. Halbzeit ',
            'sb.betting-group.5' => 'Eckball',
            'sb.betting-group.6' => 'Gelbe/Rote Karten',
            'sb.betting-group.7' => 'Torschützen',
            'sb.betting-group.8' => 'Spieler',
            'sb.betting-group.9' => 'Sonderaktionen',
            'sb.betting-group.10' => 'Rapid-Märkte',
            'sb.betting-group.11' => '5-Minuten-Märkte',
            'sb.betting-group.12' => '10-Minuten-Märkte',
            'sb.betting-group.13' => '15-Minuten-Märkte',
            'sb.betting-group.14' => 'Alle',
            'sb.betting-group.15' => 'Period',
            'sb.betting-group.16' => 'Sonderaktionen',
            'sb.betting-group.17' => 'Kombiwette',
            'sb.betting-group.18' => 'Punkte',
            'sb.betting-group.19' => 'Quarter',
            'sb.betting-group.20' => 'Sets',
            'sb.betting-group.21' => 'Spiele ',
            'sb.betting-group.22' => 'Versuche',
            'sb.betting-group.23' => 'Versuche HT',
            'sb.betting-group.24' => 'Runs',
            'sb.betting-group.25' => 'Innings',
            'sb.betting-group.26' => '1. Inning',
            'sb.betting-group.27' => 'Sets',
            'sb.betting-group.28' => '180er'
        ],
        'no' => [
            'sb.betting-group.1' => 'Hoved',
            'sb.betting-group.2' => 'Mål',
            'sb.betting-group.3' => '1. omgang',
            'sb.betting-group.4' => '2. omgang',
            'sb.betting-group.5' => 'Hjørnespark',
            'sb.betting-group.6' => 'Kort',
            'sb.betting-group.7' => 'Målscorer',
            'sb.betting-group.8' => 'Spiller',
            'sb.betting-group.9' => 'Spesialer',
            'sb.betting-group.10' => 'Hurtigmarkeder',
            'sb.betting-group.11' => '5-minuttsmarkeder',
            'sb.betting-group.12' => '10-minuttsmarkeder',
            'sb.betting-group.13' => '15-minuttsmarkeder',
            'sb.betting-group.14' => 'Alle',
            'sb.betting-group.15' => 'Periode',
            'sb.betting-group.16' => 'Spesialer',
            'sb.betting-group.17' => 'Kombo',
            'sb.betting-group.18' => 'Poeng',
            'sb.betting-group.19' => 'Kvarter',
            'sb.betting-group.20' => 'Sett',
            'sb.betting-group.21' => 'Game',
            'sb.betting-group.22' => 'Forsøk',
            'sb.betting-group.23' => 'Forsøk pause',
            'sb.betting-group.24' => 'Runs',
            'sb.betting-group.25' => 'Innings',
            'sb.betting-group.26' => '1. inning',
            'sb.betting-group.27' => 'Sett',
            'sb.betting-group.28' => '180-ere'
        ]
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
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }
}
