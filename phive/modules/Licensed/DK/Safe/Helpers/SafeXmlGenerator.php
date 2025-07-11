<?php

class SafeXmlGenerator
{

    const DEVICE_TYPE_MOBILE = 'Mobil';
    const DEVICE_TYPE_DESKTOP = 'Internet';

    /**
     * Use to generate the xml for the close token request.
     *
     * @param string $transactions_id
     * @param int $token_id
     * @param string $start_mac
     * @param string $certifikat_identifikation - SpilCertifikatIdentifikation
     * @return string
     */
    public static function generateCloseTokenXML(string $transactions_id, int $token_id, string $start_mac, string $certifikat_identifikation)
    {
        $date_transaction = date(DK::FULL_SO_DATE_FORMAT);

        return "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
            xmlns:ns=\"http://skat.dk/begrebsmodel/2009/01/15/\">
                <soapenv:Header/>
                     <soapenv:Body>
                      <ns:TamperTokenAnvend_I>
                          <ns:Kontekst>
                             <ns1:HovedOplysninger xmlns:ns1=\"http://skat.dk/begrebsmodel/xml/schemas/kontekst/2007/05/31/\">
                             <ns1:TransaktionsID>{$transactions_id}</ns1:TransaktionsID>
                             <ns1:TransaktionsTid>{$date_transaction}</ns1:TransaktionsTid>
                           </ns1:HovedOplysninger>
                          </ns:Kontekst>
                        <ns:TamperOperationValg>
                           <ns:TamperTokenLuk>
                             <ns:TamperTokenID>{$token_id}</ns:TamperTokenID>
                             <ns:SpilCertifikatIdentifikation>{$certifikat_identifikation}</ns:SpilCertifikatIdentifikation>
                             <ns:TamperTokenMAC>{$start_mac}</ns:TamperTokenMAC>
                             </ns:TamperTokenLuk>
                         </ns:TamperOperationValg>
                      </ns:TamperTokenAnvend_I>
                 </soapenv:Body>
             </soapenv:Envelope>";
    }

    /**
     * XML for the request of getting new tamper token.
     *
     * @param $uuid
     * @param string $certifikat_identifikation
     * @return string
     */
    public static function generateOpenTokenXML($uuid, string $certifikat_identifikation)
    {
        $date = date(DK::FULL_SO_DATE_FORMAT);

        return "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns=\"http://skat.dk/begrebsmodel/2009/01/15/\">
        <soapenv:Header/>
            <soapenv:Body>
                <ns:TamperTokenAnvend_I>
                    <ns:Kontekst>
                        <ns1:HovedOplysninger xmlns:ns1=\"http://skat.dk/begrebsmodel/xml/schemas/kontekst/2007/05/31/\">
                            <ns1:TransaktionsID>{$uuid}</ns1:TransaktionsID>
                            <ns1:TransaktionsTid>{$date}</ns1:TransaktionsTid>
                        </ns1:HovedOplysninger>
                    </ns:Kontekst>
                    <ns:TamperOperationValg>
                        <ns:TamperTokenHent>
                            <ns:SpilCertifikatIdentifikation>{$certifikat_identifikation}</ns:SpilCertifikatIdentifikation>
                        </ns:TamperTokenHent>
                    </ns:TamperOperationValg>
                </ns:TamperTokenAnvend_I>
            </soapenv:Body>
        </soapenv:Envelope>";
    }

    /**
     * List of supported report type for EndOfDay report
     * Fixed odds: (Ex. race horse)
     * - Fastoddsspil
     * - FastoddsspilBetexchange
     * - FastoddsSpilDanskHest
     * - FastoddsspilBeXDkHest
     * Poker:
     * - PokerCashGame
     * - PokerTurnering
     * Casino:
     * - KasinospilSinglePlayer ** This should be the only one we are using ATM.
     * - KasinospilMultiPlayer
     * Bingo:
     * - Bingospil
     * Pool betting ??
     * - Puljespil
     * - PuljespilDanskHest
     * Manager games ??
     * - Managerspil
     *
     * @param $namespace
     * @param $game_session
     * @param $spil_fil_identifikation
     * @param $spil_fil_erstatning_identifikation
     * @param $spil_certifikat_identifikation
     * @return array
     */
    public static function endOfDayToXml(
        $namespace,
        $game_session,
        $spil_fil_identifikation,
        $spil_fil_erstatning_identifikation,
        $spil_certifikat_identifikation
    ) {
        $SpilOpgeList = [
            "{$namespace}:SpilOpgørelse" => [
                "{$namespace}:SpilKategoriNavn" => 'KasinospilSinglePlayer',
                // for KasinospilSinglePlayer = sum of bets
                "{$namespace}:EndOfDayRapportAntalSpil" => $game_session['EndOfDayRapportAntalSpil'],
                "{$namespace}:EndOfDayRapportIndskudSpil" => $game_session['bet_amount'],
                // No jackpot for now, so 0
                "{$namespace}:EndOfDayRapportIndskudJackpot" => 0,
                "{$namespace}:EndOfDayRapportGevinster" => $game_session['win_amount'],
                // we don't get commission from players
                "{$namespace}:EndOfDayRapportKommissionRake" => 0,
            ]
        ];

        $xml_content = [
            "{$namespace}:EndOfDayRapportStruktur" =>
                [
                    "{$namespace}:FilInformation" =>
                        [
                            "{$namespace}:SpilFilVersion" => 'v2',
                            "{$namespace}:SpilFilIdentifikation" => $spil_fil_identifikation,
                            "{$namespace}:SpilFilErstatningIdentifikation" => $spil_fil_erstatning_identifikation
                        ],
                    "{$namespace}:Tilladelsesindehaver" =>
                        [
                            "{$namespace}:SpilCertifikatIdentifikation" => $spil_certifikat_identifikation,
                            "{$namespace}:EndOfDayRapportDato" => $game_session['date'],
                            "{$namespace}:ValutaOplysningKode" => lic('getForcedCurrency', [], null, null, 'DK')
                        ],
                    "{$namespace}:SpilOpgørelseListe" =>
                        [
                            $SpilOpgeList
                        ]
                ],
        ];

        $xsd = "http://skat.dk/begrebsmodel/2009/01/15/EndOfDayRapportStrukturType.xsd";
        $xml = new SimpleXMLElement('<?xml version = "1.0" encoding="UTF-8"?>'
            . "<{$namespace}:EndOfDayRapportStruktur  xsi:schemaLocation='{$xsd}' xmlns:fase2.1='http://skat.dk/begrebsmodel/2009/01/15/' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'>"
            . "</{$namespace}:EndOfDayRapportStruktur>");

        return [
            'xml' => $xml,
            'xml_content' => $xml_content,
        ];
    }

    /**
     * Generate the XML file data for KasinoSpil.
     *
     * @param $namespace
     * @param $game_session
     * @param $cancel
     * @param $SpilHjemmeside
     * @param $SpilFilIdentifikation
     * @param $spil_certifikat_identifikation
     * @param $iso
     * @return array
     */
    public static function kasinoSpilToXml(
        $namespace,
        $game_session,
        $cancel,
        $SpilHjemmeside,
        $SpilFilIdentifikation,
        $spil_certifikat_identifikation,
        $iso
    ) {
        /**
         * Available DGAs categories
         * 'roulette', 'baccarat', 'puntobanco', 'blackjack', 'poker', 'spilleautomat', 'bingo', 'andet'
         */
        $map_game_tag_into_category = [
            // VS => DGA
            'blackjack' => 'blackjack',
            'casino-playtech' => 'spilleautomat',
            'live' => 'andet',
            'live-casino' => 'andet',
            'other' => 'andet',
            'roulette' => 'roulette',
            'scratch-cards' => 'andet',
            'slots' => 'spilleautomat',
            // should never happen
            'slots_jackpot' => 'spilleautomat',
            // ??
            'system' => 'andet',
            'table' => 'andet',
            'videopoker' => 'andet',
            'videoslots' => 'spilleautomat',
            // should never happen
            'videoslots_jackpot' => 'spilleautomat',
            // should never happen
            'videoslots_jackpotbsg' => 'spilleautomat',
            // TODO check if this is stored in the user_game_session and SKIP adding bet/win values to the table data, for wheel-of-jps
            //  (probably this is already OK but we need to confirm) + double check the same on the users_daily_game_stats for the EndOfDay reports
            'wheel-of-jps' => 'andet',
        ];

        foreach ($game_session as $session) {
            // We get the channel in game session when a report for Evolution is being generated.
            if (isset($session['channel'])) {
                $device = $session['channel'] == 'Mobile' ? self::DEVICE_TYPE_MOBILE : $session['channel'];
            } else {
                $device = empty($session['device_type_num']) ? self::DEVICE_TYPE_DESKTOP : self::DEVICE_TYPE_MOBILE;
            }

            $game = $session['game'];
            $id = $session['SpilHjemmeside'] === $SpilHjemmeside ? $session['id'] : $session['SpilHjemmeside'] . "_" . $session['id'];

            $SpilAnnullering = self::getCanceledBets($cancel, $namespace);

            $rng_info = self::getRngInformations($game, $namespace, $iso, $session);
            $generator = $rng_info['generator'];

            $JackpotListe = self::getJackpotSession($namespace);

            $KasinoSpilSession[] = [
                "{$namespace}:KasinospilSession" =>
                    [
                        "{$namespace}:SpilProduktNavn" => substr($game['game_name'], 0, 45),
                        // needs to be "human-readable"
                        "{$namespace}:SpilProduktÅbentNetværk" => '0',
                        // open network game -  “0” = closed network
                        "{$namespace}:SpillerInformationIdentifikation" => $session['user_id'],
                        "{$namespace}:SpilTransaktionIdentifikation" => $id,
                        "{$namespace}:SpilKøbDatoTid" => self::getGmDate($session['start_time']),
                        "{$namespace}:SpilFaktiskSlutDatoTid" => self::getGmDate($session['end_time']),
                        "{$namespace}:SpilSalgskanal" => $device,
                        "{$namespace}:KasinospilKategori" => $map_game_tag_into_category[$game['tag']] ?? 'andet',
                        "{$namespace}:KasinospilIndskudSpil" => empty($session['bet_amount']) ? 0 : rnfCents($session['bet_amount'], '.', ''),
                        "{$namespace}:KasinospilGevinstSpil" => empty($session['win_amount']) ? 0 : rnfCents($session['win_amount'], '.', ''),
                        "{$namespace}:KasinospilAntalTræk" => $session['bet_cnt'] ?? 0,
                        "{$namespace}:KasinospilKommission" => '0',
                        // commission for example on P2P games (Ex. poker)
                        "{$namespace}:ValutaOplysningKode" => $session['currency'],
                        "{$namespace}:SpilSted" => [
                            "{$namespace}:SpilHjemmeside" => $session['SpilHjemmeside']
                        ],
                        $generator,
                        $SpilAnnullering,
                        $JackpotListe,
                    ]
            ];
        }

        $xml_content = [
            "{$namespace}:KasinospilStruktur" =>
                [
                    "{$namespace}:FilInformation" =>
                        [
                            "{$namespace}:SpilFilVersion" => 'v2',
                            "{$namespace}:SpilFilIdentifikation" => $SpilFilIdentifikation,
                        ],
                    "{$namespace}:TilladelsesindehaverOgSpil" =>
                        [
                            "{$namespace}:SpilCertifikatIdentifikation" => $spil_certifikat_identifikation,
                            "{$namespace}:SpilKategoriNavn" => 'KasinospilSinglePlayer',
                        ],
                    "{$namespace}:KasinospilAggregeretPrSession" =>
                        [
                            $KasinoSpilSession
                        ],
                ],
        ];
        $xsd = "http://skat.dk/begrebsmodel/2009/01/15/KasinospilPrSessionStrukturType.xsd";
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . "<{$namespace}:KasinospilPrSessionStruktur xsi:schemaLocation='{$xsd}' xmlns:fase2.1='http://skat.dk/begrebsmodel/2009/01/15/' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'>"
            . "</{$namespace}:KasinospilPrSessionStruktur>");

        return [
            'xml' => $xml,
            'xml_content' => $xml_content
        ];
    }

    /**
     * We extract this from game_country_versions -> game_version.
     * This table is not sharded so i do a query to get all the games.
     *
     * @param $game
     * @param $namespace
     * @param $iso
     * @param array $game_sessions
     * @return array
     */
    public static function getRngInformations($game, $namespace, $iso, $game_sessions = [])
    {
        $game_country_cache = [];

        if (isset($game_sessions['rng_version']) && isset($game_sessions['game_version'])) {
            $rng_version = $game_sessions['rng_version'];
            $game_version = $game_sessions['game_version'];
        } else {
            $query = "SELECT * FROM game_country_versions WHERE country = '{$iso}';";

            $game_country_versions = phive('SQL')->loadArray($query);
            foreach ($game_country_versions as $game_country_version) {
                $game_country_cache[$game_country_version['game_id']] = $game_country_version;
            }

            // Fallback values (if the games doesn't have an RNG it should be blocked)
            $rng_version = '1.0';
            $game_version = '1.0';
            if (in_array($game['tag'], ['live', 'live-casino'])) {
                $rng_version = 'LIVE';
                $game_version = 'LIVE';
            }

            if ($game_country_cache[$game['id']]) {
                $rng_version = $game_country_cache[$game['id']]['rng_version'];
                $game_version = $game_country_cache[$game['id']]['game_version'];
            }
        }

        $generator = [];

        $generator["{$namespace}:TilfældighedGeneratorListe"][] = [
            [
                "{$namespace}:TilfældighedGenerator" =>
                    [
                        "{$namespace}:TilfældighedGeneratorIdentifikation" => $rng_version,
                        "{$namespace}:TilfældighedGeneratorSoftwareId" => $game_version
                    ],
            ],
        ];

        return [
            'generator' => $generator,
            'game_country_cache' => $game_country_cache,
        ];
    }

    /**
     * Create xml from array
     *
     * @param SimpleXMLElement $object
     * @param array $data
     */
    public static function arrayToXml(SimpleXMLElement $object, array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    self::arrayToXml($object, $value);
                    continue;
                }
                $new_object = $object->addChild($key);
                self::arrayToXml($new_object, $value);
            } else {
                if (!is_numeric($key)) {
                    $object->addChild($key, $value);
                }
            }
        }
    }

    /**
     *
     * used when a bet is cancelled , we send the cancel report
     * @param $cancel
     * @return array
     */
    public static function getCanceledBets($cancel, $namespace)
    {
        if ($cancel) {
            $SpilAnnullering[] = [
                "{$namespace}:SpilAnnullering" =>
                    [
                        "{$namespace}:SpilAnnullering" => 1,
                        /**
                         * As this is used only during bets rollback process doing
                         * "cancel single session + re-generate with correct data"
                         * it should be fine to use current time instead of session end time
                         */
                        "{$namespace}:SpilAnnulleringDatoTid" => self::getGmDate(date("Y-m-d H:i:s")),
                    ]
            ];
        } else {

            $SpilAnnullering[] = [
                "{$namespace}:SpilAnnullering" =>
                    [
                        "{$namespace}:SpilAnnullering" => 0,
                        // TODO verify with DGA if we can set "SpilAnnulleringDatoTid" as empty (as this should be not be used when "SpilAnnullering" is 0)
                        "{$namespace}:SpilAnnulleringDatoTid" => self::getGmDate(date("Y-m-d H:i:s")),
                    ]
            ];
        }

        return $SpilAnnullering;
    }

    /**
     * Returns GM date.
     *
     * @param string $datetime
     * @return false|string
     */
    public static function getGmDate($datetime = '')
    {
        if (!empty($datetime)) {
            $timestamp = strtotime($datetime);
            return gmDate("Y-m-d\TH:i:s.u\Z", $timestamp);
        }
        return gmDate("Y-m-d\TH:i:s.u\Z");
    }

    /**
     * Return empty array, for now we do not have jackpots games for DK.
     *
     * @param $namespace
     * @return mixed
     */
    private static function getJackpotSession($namespace)
    {
        $jackpot[] = [];
        $jackpotListe["{$namespace}:JackpotListe"] = $jackpot;
        return $jackpotListe;
    }
}
