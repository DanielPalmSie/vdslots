<?php

require_once __DIR__ . '/Gp.php';

class Blueprint extends Gp
{

    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is
     * transaction ID)
     *
     * @var string
     */
    protected $_m_sGpName = __CLASS__;


    /**
     * The logger channel name for Blueprint
     *
     * @var string
     */
    protected string $logger_name = 'blueprint';

    /**
     * Set the defaults
     * Separate function so it can be called also from the classes that extend TestGp class
     *
     * @return Gp
     */
    public function setDefaults()
    {
        $this
            //->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            //->_overruleErrors($this->_m_aErrors)
            ->_supportInhouseFrb($this->_m_sGpName)
            ->_checkDeclaredProperties()
            ->_setWalletActions()
        ;
        return $this;
    }

    /**
     * This function gets the jackpot data from the blueprint jackpot feed for currencies available on our system
     *
     * @return array
     */
    public function parseJackpots()
    {
        $cur = phive('Currencer');
        $inserts = [];

        $missingGames = [];
        $foundGames = [];

        $urls = $this->getAllJurSettingsByKey('jp_url');
        foreach ($urls as $jur => $url) {
            foreach ($cur->getAllCurrencies() as $ciso => $c) {
                $jpUrl = $url. '?currency=' .$ciso. '&affiliate=videoslots';

                $xmlContent = phive()->get($jpUrl, '', '', $this->getGpName() . '-curl');
                if ($xmlContent) {

                    $jackpots = json_decode(json_encode(simplexml_load_string($xmlContent)), true);
                    foreach ($jackpots as $jackpot) {
                        if(isset($jackpot['pots']['pot']) && isset($jackpot['games']['game'])) {

                            $pots = $jackpot['pots']['pot'];
                            $games = $jackpot['games']['game'];
                            if($games && $pots){
                                for ($i = 0; $i < 3; $i++) { // 3 loops as Blueprint has a 3 pot system
                                    $pot = $pots[$i]['@attributes'];
                                    foreach ($games as $game) {
                                        // check if we already know the game is missing
                                        if (isset($missingGames['blueprint_' . $game])) {
                                            continue;
                                        }

                                        // check if we already found the game
                                        if (!isset($foundGames['blueprint_' . $game])) {
                                            $gameInfo = phive('MicroGames')->getByGameId('blueprint_' . $game);

                                            if (empty($gameInfo)) {
                                                $missingGames['blueprint_' . $game] = true;
                                                continue;
                                            } else {
                                                $foundGames['blueprint_' . $game] = true;
                                            }
                                        }

                                        $inserts[] = [
                                            'jp_value'     => $pot['current_amount'] * 100,
                                            'jp_id'        => $pot['name'] . $jur,
                                            'jp_name'      => $pot['name'],
                                            'module_id'    => 'blueprint_' . $game,
                                            'network'      => $this->getGpName(),
                                            'currency'     => $ciso,
                                            'local'        => 0,
                                            'jurisdiction' => $jur,
                                            'game_id'      => 'blueprint_' . $game
                                        ];

                                    }
                                }
                            }
                            else{
                                echo("No pots or games found in the jackpot data. \n");
                            }
                        }
                    }
                }
                else {
                    echo("There was an error retrieving the XML data.\n");
                }
            }
        }

        return $inserts;
    }

    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        // blueprint is using GPR integration, thus this function is added here just to implement the Gp class
    }

    protected function _response($p_mResponse)
    {
        // blueprint is using GPR integration, thus this function is added here just to implement the Gp class
    }

    public function preProcess()
    {
        // blueprint is using GPR integration, thus this function is added here just to implement the Gp class
    }
}
