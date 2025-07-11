<?php
require_once __DIR__ . '/TypeOne.php';

class Playngo extends TypeOne
{

    protected string $logger_name = 'playngo';

    /**
     * @param $xml
     * @return array|SimpleXMLElement
     */
    public function parseXml($xml)
    {
        $r = new SimpleXMLElement($xml);
        $r = (array)$r;
        $tmp = preg_split('/<|>/', $xml);
        $r['action'] = $tmp[1];
        return $r;
    }

    /**
     * @param $action
     * @param $arr
     * @param bool $status
     * @return string
     */
    public function buildXml($action, $arr, $status = true)
    {
        if (empty($arr['statusCode']) && $status == true) {
            $arr['statusCode'] = 0;
            $arr['statusMessage'] = 'ok';
        }
        ob_start();
        ?>
        <<?php echo $action ?>>
        <?php foreach ($arr as $key => $val): ?>
        <<?php echo $key ?>><?php echo $val ?></<?php echo $key ?>>
        <?php endforeach ?>
        </<?php echo $action ?>>
        <?php
        $xml = ob_get_contents();
        ob_end_clean();

        $return = trim($xml);

        $this->logger->debug(sprintf('playngo_response_%s', $action), [$return]);
        $this->dumpTst("playngo_response_{$action}", $return);

        return $return;
    }

    public function buildFail(
        string $action,
        int    $code,
        string $statusMsg = 'Request failed',
        array  $statusParams = [],
        float  $balance = 0.00
    ) : string
    {
        $this->logger->warning(
            sprintf('%s-buildFail', $action),
            [
                'statusMessage' => $statusMsg,
                'statusParams' => $statusParams
            ]
        );

        $this->logToDb("{$action}-buildFail", $statusMsg, $statusParams);

        return $this->buildXml(
            $action,
            [
                'statusCode'    => $code,
                'statusMessage' => empty($statusMsg) ? 'Request failed' : $statusMsg,
                'real'          => $balance,
                'currency'      => 'EUR'
            ]
        );
    }

    public function logToDb(string $tag, string $statusMsg = 'Request failed', array  $statusParams = []) : void
    {
        $user     = cu();
        $dumpData = json_encode([
            'message' => $statusMsg,
            'params'  => $statusParams,
        ]);

        $this->dumpTst($tag, $dumpData, ! empty($user) ? $user->getId() : null);
    }

    /**
     * @param $xml
     * @return string
     */
    public function execReq($xml) : string
    {
        $parsedData = $this->parseXml($xml);
        $action     = $parsedData['action'];
        $_REQUEST['game_action'] = $action;
        $user       = $this->getUsr($parsedData);
        $dumpData   = [
            'parsedXML' => $parsedData,
            'user'      => $user,
            'licToken'  => $this->getLicSetting('token', $user),
        ];

        $this->logger->debug(sprintf('playngo_request_%s', $action), [$xml]);

        $this->dumpTst("playngo_request_{$action}", $xml);

        if (! $user) {
            return $this->buildFail($action, 4, 'Wrong username and password', $dumpData);
        }

        if ($parsedData['accessToken'] !== $this->getLicSetting('token', $user) && ! $this->getSetting('test')) {
            return $this->buildFail($action, 2, 'Wrong token', $dumpData);
        }

        if (in_array($action, ['balance', 'release', 'reserve']) && $this->useExternalSession($user)) {
            $this->setSessionById($user['id'], $parsedData['externalGameSessionId']);

            if ($parsedData['state'] === '1') {
                $this->finishExternalGameSession($user);
            }
        }

        $result = $this->$action($parsedData, $user);

        return is_string($result) ? $result : $this->buildXml($action, $result);
    }

    /**
     * @param null $req
     * @return array|bool|mixed
     */
    public function getUsr(&$req = null)
    {
        $uid = empty($req['externalId']) ? phMget($req['username']) : $req['externalId'];
        $this->uid = $this->getUsrId($uid);
        $user = cu($this->uid);
        if (!is_object($user)) {
            return false;
        }

        $this->user = $user;

        $GLOBALS['mg_username'] = $user->data['username'];
        return $user->data;
    }

    /**
     * This function was using $balance = $this->lgaMobileBalance($user['user_id'], $this->getGameRef($req), $balance);
     * before.
     *
     * @param $req
     * @param $user
     * @return array
     */
    public function balance($req, $user)
    {
        $balance = $this->_getBalance($user, $req);

        $this->logger->debug('balance', ['user' => $user, 'balance' => $balance]);
        $this->logToDb('playngo_method', 'balance', ['user' => $user, 'balance' => $balance]);

        return array('real' => phive()->twoDec($balance), 'currency' => $this->getPlayCurrency($user));
    }

    /**
     * @param $req
     * @param $user
     * @return array
     */
    public function betResultGetUser($req, $user)
    {
        $amount = abs($req['real'] * 100);

        $id = $this->getTransactionId($req['transactionId'], $user['country']);
        $round_id = $this->getRoundId($req['roundId'], $user['country']);
        $this->setParams($amount, $id, $round_id);

        $this->gref = $this->new_token['game_ref'] = $this->token['game_ref'] = $this->getGameRef($req);

        return array($user, $amount, $id);
    }

    /**
     * @param $req
     * @return mixed|null
     */
    public function getBonusId($req)
    {
        return empty($req['freegameExternalId']) ? null : $req['freegameExternalId'];
    }

    /**
     * Process a bet
     *
     * @param $req
     * @param $user
     * @return array|string
     */
    public function reserve($req, $user)
    {
        [$user, $bet_amount, $pngo_id] = $this->betResultGetUser($req, $user);

        $round_id = $this->getRoundId($req['roundId'], $user['country']);
        $bet      = $this->getBetByMgId($pngo_id);
        $dumpData = [
            'round_id' => $round_id,
            'bet'      => $bet,
            'user'     => $user,
        ];

        if (! empty($bet)) {
            $balance = $bet['balance'];
            $ext_id  = $bet['id'];
        } else {
            $balance             = $this->_getBalance($user, $req);
            $cur_game            = $this->getGameByRef($req, 'playngo_system');
            $dumpData['game']    = $cur_game;
            $dumpData['balance'] = $balance;

            if (empty($cur_game)) {
                return $this->buildFail('reserve', 2, "Game missing", $dumpData);
            }

            $jp_contrib          = round($bet_amount * $cur_game['jackpot_contrib']);
            $balance             = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $bet_amount);
            $dumpData['balance'] = $balance;
            $dumpData['jp']      = $jp_contrib;

            if ($balance < $bet_amount) {
                return $this->buildFail('reserve', 7, t('game-info.no-money-message'), $dumpData, phive()->twoDec($balance));
            }

            $GLOBALS['mg_id']    = $pngo_id;
            $balance             = $this->playChgBalance($user, -$bet_amount, $round_id, 1);
            $dumpData['balance'] = $balance;

            if ($balance === false) {
                return $this->buildFail('reserve', 2, "Could not update balance", $dumpData);
            }

            $bonus_bet             = empty($this->bonus_bet) ? 0 : 1;
            $ext_id                = $this->insertBet($user, $cur_game, $round_id, $pngo_id, $bet_amount, $jp_contrib, $bonus_bet, $balance);
            $dumpData['bonus_bet'] = $bonus_bet;

            if (! $ext_id) {
                return $this->buildFail('reserve', 2, "Could not log bet", $dumpData);
            }

            $this->insertRound($user['id'], $ext_id, $round_id);
            $balance = $this->betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $round_id, $pngo_id);
        }

        return [
            'real'                  => phive()->twoDec($balance),
            'externalTransactionId' => $ext_id,
            'currency'              => $user['currency'],
        ];
    }


    /**
     * @param $user
     * @param $req
     * @param $balance
     * @param string $ext_id
     * @return array
     */
    public function returnRelease($user, $req, $balance, $ext_id = '')
    {
        if (empty($ext_id)) {
            $ext_id = phive('SQL')->sh($user, 'id', 'bets')->insertBigId();
        }
        return array(
            'real' => phive()->twoDec($balance),
            'externalTransactionId' => $ext_id,
            'currency' => $user['currency']
        );
    }

    /**
     * Process a win
     *
     * FRB stuff start, turnover requirements are not possible due to the fact that it's a lot of work keeping track of the total win sum
     * since the wins come in on every win
     *
     * @param $req
     * @param $user
     * @return array|string
     */
    public function release($req, $user)
    {
        [$user, $amount, $pngo_id] = $this->betResultGetUser($req, $user);

        $GLOBALS['mg_id']  = $pngo_id;
        $this->game_action = 'win';
        $round_id          = $this->getRoundId($req['roundId'], $user['country']);
        $result            = $this->getBetByMgId($pngo_id, 'wins');
        $dumpData          = [
            'round_id' => $round_id,
            'bet'      => $result,
            'user'     => $user,
        ];

        if (! empty($result)) {
            $ext_id  = $result['id'];
            $balance = $this->_getBalance($user, $req);
        } else {
            $cur_game = $this->getGameByRef($req, 'playngo_system');
            $bonus_id = ! empty($req['freegameExternalId']) ? $req['freegameExternalId'] : null;

            /* FRB logic start */
            if (! is_null($bonus_id)) {
                $fspin = phive('Bonuses')->getBonusEntry($bonus_id, $user['id']);

                if ($fspin['status'] === 'failed') {
                    $this->logger->warning('playngo-frb-fail', [
                            'user_id' => $user['id'],
                        'message' => sprintf('%s %s cents due to failed FRB bonus','Forfeited', $amount )
                    ]);

                    phive('UserHandler')->logAction($user['id'], "Forfeited $amount cents due to failed FRB bonus.", 'playngo-frb-fail');
                } else {
                    $this->processFspinWin($fspin, $amount, $user, 'Freespin win', $req);
                }

                $balance = $this->_getBalance($user, $req);

                if (! empty($amount)) {
                    $ext_id = $this->insertWin($user, $cur_game, $balance, $round_id, $amount, 3, $pngo_id, 2);
                }

                return $this->returnRelease($user, $req, $balance + $amount, $ext_id);
            }
            /* FRB logic end */

            $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
            if (!empty($amount)) {
                $balance = $this->_getBalance($user, $req);
                $ext_id = $this->insertWin($user, $cur_game, $balance, $round_id, $amount, $bonus_bet, $pngo_id, 2);
            } else {
                $ext_id = '';
            }

            $dumpData['game']      = $cur_game;
            $dumpData['bonus_id']  = $bonus_id;
            $dumpData['bonus_bet'] = $bonus_bet;
            $dumpData['ext_id']    = $ext_id;

            if ($ext_id === false) {
                return $this->buildFail('reserve', 2, "Could not log win", $dumpData);
            }

            // TODO make a DDBB transaction with the whole process of inserting a win + round + change balance
            $this->updateRound($user['id'], $round_id, $ext_id);

            $balance             = $this->playChgBalance($user, $amount, $round_id, 2);
            $dumpData['balance'] = $balance;

            if ($balance === false) {
                return $this->buildFail('reserve', 2, "Could not update balance", $dumpData);
            }


            $balance = $this->handlePriorFail($user, $round_id, $balance, $amount);
        }

        return $this->returnRelease($user, $req, $balance, $ext_id);
    }

    /**
     *
     * TODO we need to capture a false return here to be 100% safe
     *
     * TODO fix the function declaration is it is not compatible with the parent
     *
     * @param $e
     * @param $amount
     * @param $ud
     * @param $description
     * @param $req
     */
    public function processFspinWin($e, $amount, $ud, $description, $req)
    {
        if (empty($e)) {
            return;
        }
        $b = phive('Bonuses')->getBonus($e['bonus_id']);
        if (empty($b)) {
            return;
        }

        if ($e['status'] == 'approved') {
            $this->changeBalance($ud, $amount, $description, 2);
            if (!empty($req['freegameFinished'])) {
                phive('Bonuses')->editBonusEntry($e['id'], ['frb_remaining' => 0], $ud['id']);

                $device_type = phive()->isMobile() ? 1 : 0;
                phive()->pexec('Casino', 'propagateFreeSpinsBets', [$e['id'], (int) $ud['id']]);
                if (empty($amount)) {
                    phive()->pexec('Casino', 'propagateFreeSpinsEndSession', [(int) $ud['id'], $b['game_id'], $device_type, strtotime($e['activated_time']), time()]);
                }
            }
        } else {
            if (!empty($req['freegameFinished'])) {
                $e = phive('Bonuses')->getBonusEntry($e['id']);
                $this->handleFspinWin($e, $amount, $ud['id'], $description, true);
            } else {
                phive('SQL')->incrValue('bonus_entries', '', ['id' => (int)$e['id']], [
                    'balance' => (int)$amount,
                    'reward' => (int)$amount,
                    'frb_remaining' => -1 // One request per fs round played
                ], [], $ud['id']);
            }
        }

    }

    /**
     * Process rollbacks
     *
     * @param $req
     * @param $user
     * @return array|string|string[]
     */
    public function cancelReserve($req, $user)
    {
        $tbl      = 'bets';
        $already  = 'false';
        $pngo_id  = $this->getTransactionId($req['transactionId'], $user['country']);
        $bet      = $this->getBetByMgId($pngo_id);
        $dumpData = [
            'bet'  => $bet,
            'user' => $user,
        ];

        if (empty($bet)) {
            $pngo_already_id = "$pngo_id" . 'ref';
            $bet             = $this->getBetByMgId($pngo_already_id);

            if (!empty($bet)) {
                $already = 'true';
            } else {
                return ['externalTransactionId' => ''];
            }
        }

        $amount                      = abs($req['real'] * 100);
        $this->new_token['game_ref'] = $bet['game_ref'];

        if ($already == 'false' && ! empty($bet)) {
            $balance             = $this->playChgBalance($user, $amount, $bet['trans_id'], 7);
            $dumpData['balance'] = $balance;

            if ($balance === false) {
                return $this->buildFail('reserve', 2, "Database error, could not change balance on rollback", $dumpData);
            }

            $this->doRollbackUpdate($pngo_id, $tbl, $balance, $amount);
        }

        return [
            'externalTransactionId' => $bet['id'],
        ];
    }

    /**
     * Auth call on first game load
     *
     * @param $req
     * @param $user
     * @return array
     */
    public function authenticate($req, $user)
    {
        $user_obj = cu($user['id']);
        $user['currency'] = $this->getPlayCurrency($user);
        $user['id'] = $this->mkUsrId($user['id']);

        if ($this->useExternalSession($user_obj)) {
            $game       = $this->getGameByRef($req);
            $session_id = lic('initGameSessionWithBalance', [$user_obj, $req['username'], $game], $user_obj);

            if (empty($session_id)) { // happens when the user reaches the game page directly by URL, this error may be seen by the user for a second
                $this->logger->warning('authenticate failed', ['user' => $user, 'game' => $game]);

                $this->logToDb('playngo_method', 'authenticate failed', ['user' => $user, 'game' => $game]);

                return ['statusCode' => 1, 'statusMessage' => t('game-session-balance.reload-required'), 'real' => 0.00, 'currency' => 'EUR'];
            }
        }

        $data = [
            'externalId'            => $user['id'],
            'statusCode'            => 0,
            'userCurrency'          => $user['currency'],
            'country'               => $this->getTournamentCountry($user),
            'gender'                => $user['sex'] == 'Male' ? 'm' : 'f',
            'birthdate'             => $user['dob'],
            'registration'          => $user['register_date'],
            'affiliateId'           => $this->getBrand($user['country']),
            'externalGameSessionId' => $session_id ?? ''
        ];
        if (!empty($this->t_entry)) {
            $data['real'] = phive()->twoDec($this->_getBalance($user, $req));
        }

        $province = $user_obj->getMainProvince();
        if(!empty($province)){
            $data['region'] = $province;
        }

        $data['betLimit'] = $this->formatAmount(phive('Gpr')->getMaxBetLimit($user_obj));

        $this->logger->info('authenticate', [$data]);
        $this->logToDb('playngo_method', 'authenticate', $data);

        return $data;
    }

    /**
     * @param $entry
     * @param $na
     * @param $bonus
     */
    public function activateFreeSpin(&$entry, $na, $bonus)
    {
        $entry['status'] = 'approved';
        //$entry['ext_id'] = $bonus['ext_ids'];
        //$entry['status'] = 'active';
    }

    /**
     * @param mixed|DBUser $user
     * @return array|bool
     */
    public function getLicParameters($user = null)
    {
        $buttons = lic('getBaseGameParams', [$user], $user);
        if (!empty($buttons)) {
            $elapsedTime = lic('rcElapsedTime', [$user], $user);
            return [
                'pauseplay' => $buttons['selfexclusion_url'],
                'selftest' => $buttons['selfassessment_url'],
                'playlimit' => $buttons['accountlimits_url'],
                'sessiontime' => $elapsedTime
            ];
        }
        return false;
    }

    /**
     * @param mixed|DBUser $user
     * @return array
     */
    public function getRcParameters($user = null)
    {
        $rcParameters = $this->getRcParamsCommon($user, false);
        if (empty($rcParameters['rcInterval'])) {
            return [];
        }

        if ($this->getRcPopup(phive()->isMobile(), $user) !== 'ingame') {
            return [];
        }
        $elapsedTime = lic('rcElapsedTime', [$user], $user);
        return [
            'rccurrentsessiontime' => $elapsedTime,
            'rcintervaltime' => $rcParameters['rcInterval'] * 60,
            'rcaccounthistoryurl' => $this->wrapUrlInJsForRedirect($rcParameters['rcHistoryUrl']),
            'rcexiturl' => $this->wrapUrlInJsForRedirect($rcParameters['rcLobbyUrl']),
            'rchistoryurlmode' => 'open'
        ];
    }

    /**
     * @param mixed|DBUser $ud
     * @return mixed|string
     */
    public function getTournamentCountry($ud = null)
    {
        $country = $this->getCountry($ud);
        if (!empty($this->t_eid)) {
            return $this->getLicSetting('bos-country', $ud['id']);
        }
        return $country;
    }

    /**
     * TODO fix the language for BOS mobile
     *
     * @param $gid
     * @param $lang
     * @param string $key Token key
     * @param string $mp_id
     * @param array $args
     * @param bool $show_demo
     * @param null $game
     * @return string
     */
    public function getBaseArgs($gid, $lang, $key, $mp_id = '', $args = [], bool $show_demo = false, $game = null): string
    {
        $game = $game ?? phive("MicroGames")->getByGameId($gid);
        $gid = str_replace('playngo_', '', $gid);

        $locale = phive('MicroGames')->getGameLocale($game['game_id'], $lang, $game['device_type']);
        $extra = '';

        $user = cu();

        $pid = $this->getLicSetting('pid', $user);

        if (empty($user) || $show_demo) {
            $extra .= '&country=' . phive('Licensed')->getLicCountry();
        } else {
            $this->t_eid = $mp_id;

            $token = mKey($_SESSION['mg_id'], phive()->uuid());
            phMset($token,  empty($mp_id) ? $_SESSION['mg_id'] : $mp_id);
            $extra = "&$key=" . $token;

            if (!empty(licJur($user))) {
                $extra .= "&country=" . $this->getTournamentCountry($user->data);
                $province = $user->getMainProvince();
                if(!empty($province)){
                    $extra .= "&region=" . $province;
                }
            }

            if (!empty($lic_parameters = $this->getLicParameters($user))) {
                $extra .= '&' . http_build_query($lic_parameters);
            }

            if (!empty($rc_parameters = $this->getRcParameters($user))) {
                $extra .= '&' . http_build_query($rc_parameters);
            }
        }

        $url_params = http_build_query([
            'pid' => $pid,
            'gid' => $gid,
            'lang' => $locale,
            'practice' => ($show_demo ? 1 : 0),
            'lobby' => $this->wrapUrlInJsForRedirect($this->getLobbyUrl(false, $lang)),
            'brand' => $this->getBrand(cuCountry())
        ]);

        $return = '?' . $url_params . $extra;

        $this->logger->info('playngo-launch-params', [$return]);

        $this->dumpTst('playngo-launch-params', $return);

        return $return;
    }

    /**
     * @param $gid
     * @param $lang
     * @param $game
     * @param bool $show_demo
     * @return string
     */
    public function getDepUrl($gid, $lang, $game = null, $show_demo = false): string
    {
        $user = cu();
        $args = [];
        $token_key = "user";
        $extension = "ContainerLauncher";
        $token_uid = $_SESSION['token_uid'];
        $container_params = '&embedmode=iframe&div=pngCasinoGame&channel=desktop&origin=' . $this->getBasePath($lang) . '&id=mbox-iframe-play-box';

        $url = $this->getLicSetting('base_url', $user) . $extension;
        $url .= $this->getBaseArgs($gid, $lang, $token_key, $token_uid, $args, $show_demo);
        $lobby_url = $this->getLobbyUrl(false, $lang);
        $url .= "&mp_id={$token_uid}&lobby={$lobby_url}" . $container_params;

        $this->logger->debug('playngo-desktop-launch', [$url]);
        $this->dumpTst('playngo-desktop-launch', $url);

        return $url;
    }

    /**
     * @param $gid
     * @param $lang
     * @param string $mp_id
     * @param bool $show_demo
     * @return string
     */
    function getFlashUrl($gid, $lang, $mp_id = '', bool $show_demo = false): string
    {
        return $this->getLicSetting('base_url') . "js" . $this->getBaseArgs($gid, $lang, 'username', $mp_id, [], $show_demo);
    }

    /**
     * TODO container hardcoded id has to be moved to a config to support multibranding
     *
     * @param $gref
     * @param $lang
     * @param $lobby_url
     * @param $g
     * @param $args
     * @param bool $show_demo
     * @return string
     */
    public function getMobilePlayUrl($gref, $lang, $lobby_url, $g, $args = [], $show_demo = false): string
    {
        $user = cu();
        $game   = phive("MicroGames")->getByGameRef($gref);
        $game_id = $this->getSetting('common_game_session', false) ? str_replace('mobile', '', $game['game_id']) : $game['game_id'];

        if (phive('MicroGames')->gameInIframe($game)) {
            $token_key =  "user";
            $extension = "ContainerLauncher";
            $container_params = '&embedmode=iframe&div=pngCasinoGame&channel=mobile&origin=' . $this->getBasePath($lang) . '&id=vs-game-container__iframe';
        } else {
            $token_key =  "ticket";
            $extension = "PlayMobile";
            $container_params = '';
        }

        $url = $this->getLicSetting('base_url', $user) . $extension;
        $url .= $this->getBaseArgs($game_id, $lang, $token_key, $_SESSION['token_uid'], $args, $show_demo, $game);
        $url .= "&mp_id={$_SESSION['token_uid']}&lobby=" . $lobby_url . $container_params . $this->getRcParameters($user);

        $this->logger->debug('playngo-mobile-launch', [$url]);
        $this->dumpTst('playngo-mobile-launch', $url);

        return $url;
    }


    /**
     * TODO mobtrophy, this will always refer to the desktop game so in case we're on a mobile device we have to switch to the mobile version
     * @param $uid
     * @param $gids
     * @param $rounds
     * @param $bonus_name
     * @param $entry
     * @return bool|SimpleXMLElement|string
     */
    public function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry)
    {
        if ($this->getSetting('no-out') === true) {
            return true;
        }
        $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);
        $cg = phive('MicroGames')->getByGameId($bonus['game_id'], '');
        $cg = phive('MicroGames')->getCurrentGame($cg);
        if (empty($cg)) {
            return 'fail';
        }
        $gid = str_replace('playngo', '', $cg['ext_game_name']);
        if ($this->getSetting('common_game_session')) {
            $gid = '100'.ltrim($gid, '100');
        }

        $xml = '';

        if (empty($bonus['frb_lines'])) {
            // We're looking at an offer created in the Playngo BO?
            // TODO do we even have these anymore, if not remove this conditional. /Henrik
            $xml = "<v1:AddFreegameOffers>
                        <v1:UserId>$uid</v1:UserId>
                        <v1:GameId>$gid</v1:GameId>
                        <v1:TriggerId>$gids</v1:TriggerId>
                        <v1:Rounds>{$bonus['reward']}</v1:Rounds>
                        <v1:ExpireTime>{$this->dateToDtime($entry['end_time'])}</v1:ExpireTime>
                        <v1:FreegameExternalId>{$entry['id']}</v1:FreegameExternalId>
                        <v1:GameIdList>
                            <int>$gid</int>
                        </v1:GameIdList>
                    </v1:AddFreegameOffers>";
        } else {
            $xml = "<v1:AddFreegameOffers>
                        <v1:UserId>$uid</v1:UserId>
                        <v1:GameId>$gid</v1:GameId>
                        <v1:Lines>{$bonus['frb_lines']}</v1:Lines>
                        <v1:Coins>{$bonus['frb_coins']}</v1:Coins>
                        <v1:Denomination>{$bonus['frb_denomination']}</v1:Denomination>
                        <v1:Rounds>{$bonus['reward']}</v1:Rounds>
                        <v1:ExpireTime>{$this->dateToDtime($entry['end_time'])}</v1:ExpireTime>
                        <v1:FreegameExternalId>{$entry['id']}</v1:FreegameExternalId>
                        <v1:GameIdList>
                            <int>$gid</int>
                        </v1:GameIdList>
                    </v1:AddFreegameOffers>";

        }
        return $this->postSoap('AddFreegameOffers', $xml, $uid);
    }

    /**
     * Playngo has different min bet levels per currency (currently CAD, JPY and NZD) so FS has to be overridden
     *
     * For now only overridden frb_cost is supported as frb_lines couldn't be determined
     * If we could determine games with fixed lines and trust the info in the DB we could do if frb_lines % 2 is 0 then divide etc
     *
     *
     * @param $user_id
     * @param $bonus
     * @return array|bool
     */
    public function fsBonusOverride($user_id, $bonus) {

        if (in_array(cu($user_id)->getCurrency(), $this->getSetting('currencies_override_cost', ['CAD']))) {
            $bonus['frb_cost'] *= 2;
        }
        return $bonus;
    }

    /**
     * @param $command
     * @param $req_data
     * @param $uid
     * @return string
     */
    public function postSoap($command, $req_data, $uid)
    {
        $data_string = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v1="http://playngo.com/v1"><soapenv:Header/><soapenv:Body>';
        $data_string .= $req_data . '</soapenv:Body></soapenv:Envelope>';

        $headers = ["SOAPAction: \"http://playngo.com/v1/CasinoGameTPService/$command\""];

        if($this->getLicSetting('is_upgraded')){
            $headers[] = "Authorization: Basic " . base64_encode($this->getLicSetting('Username', $uid) . ':' . $this->getLicSetting('Password', $uid));
        }

        $res = phive()->post($this->getLicSetting('CGTPServiceURL', $uid), $data_string, 'text/xml', $headers, $this->getSetting('test') ? 'playngo-post' : '');

        $this->logger->debug('playngo_addfs_res', [$res]);
        $this->dumpTst('playngo_addfs_res', $res);

        if (strpos($res, 'faultstring') !== false) {
            return 'fail';
        }

        return $res;
    }

    /**
     * Return the brand using the following priority (high to low):
     * - if is BoS then we always enforce the "bos-country" brand if exist, otherwise DEFAULT
     * - if we have the user country specified in "rtp-country" that brand will be set for the player
     * - if we don't have the user country in "rtp-country" the "DEFAULT" brand will be used instead
     * - if no setting exist, we return '' (should happen only in demo mode)
     *
     * @param $country
     * @return string
     */
    public function getBrand($country)
    {
        $rtp_settings = $this->getSetting('rtp-country');
        $brand = $rtp_settings['DEFAULT'] ?? '';

        if (!empty($rtp_settings) && isLogged()) {
            if (!empty($this->t_eid)) {
                $country = $this->getLicSetting('bos-country', $this->uid);
            }
            $brand = $rtp_settings[$country] ?? $rtp_settings['DEFAULT'];
        }
        return $brand;
    }

    /**
     * @param $date
     * @return string
     */
    public function dateToDtime($date)
    {
        return $date . "T23:59:59.000Z";
    }

    /**
     * Adds the prefix used in micro_games to the game id received in the request
     * When common_game_session is enabled we get the device from the channel parameter instead of the gameId
     * TODO IT I don't understand the above comment when is this enabled?
     * REPLY IT - from Antonio:  When Common Game Session is enabled (Mr Vegas is) Playngo uses the same game references
     * for both desktop and mobile, what makes possible to switch devices keeping all session progression perks.
     * This is configured by Playngo. This code was deleted on some merge, if this change reaches MrVegas
     * it will break Mobile games there.
     * @param $req
     * @return string
     */
    public function getGameRef($req)
    {
        if (isset($req['channel']) && $req['channel'] == '2') {
            return 'playngo100'.$req['gameId'];
        }
        return 'playngo' . $req['gameId'];
    }

    /**
     * @param $trans_id
     * @param $country
     * @return string
     */
    public function getTransactionId($trans_id, $country)
    {
        $brand = $this->getBrand($country);
        return "plngo{$brand}{$trans_id}";
    }

    /**
     * @param $round_id
     * @param $country
     * @return int
     */
    public function getRoundId($round_id, $country)
    {
        $brand = $this->getBrand($country);
        return (int)($brand . $round_id);
    }

    /**
     * @return array|mixed
     */
    public function parseJackpots()
    {

        $urls = $this->getAllJurSettingsByKey('jp_url');
        $pids = $this->getAllJurSettingsByKey('pid');
        $res = array();


        foreach ($urls as $jur => $url) {
            $jp_url = $url . $pids[$jur];
            $jps = json_decode(phive()->get($jp_url, 10), true);

            foreach ($jps as $jp) {
                foreach ($jp['Games'] as $game) {
                    $res[] = array(
                        'jp_id' => $jp['JackpotId'],
                        'jp_name' => $jp['Description'],
                        'module_id' => 'playngo' . $game['GameId'],
                        'network' => 'playngo',
                        'jp_value' => $jp['BaseAmount'] * 100,
                        'currency' => $jp['Currency'],
                        'game_id' => 'playngo' . $game['GameId'],
                        'jurisdiction' => $jur
                    );
                }
            }
        }
        return $res;
    }


    /**
     * @param $number
     * @param int $precision
     * @return string
     */
    private function formatAmount($number, int $precision = 2): string
    {
        return bcdiv($number, 1, $precision);
    }
}
