<?php

trait ExternalGameSessionTrait
{
    public $session_entry;
    private $licSessionService;

    /**
     * Creates a new external session with balance in the table.
     *
     * @param DBUser $user
     * @param string $session_id Session id to reference an external game session participation
     * @param string $token
     * @param string|int $tab_id Identifier for browser tab, needed to close sessions when the user leaves the page (IT);
     *                           OR '' if tab logic is not needed (ES)
     */
    public function createExternalSession(DBUser $user, string $session_id, string $token, $tab_id = '')
    {
        if (!empty($session_id)) {
            phMsetShard('ext-game-session-id' . $token, $session_id, $user->getId());
            phMsetShard('ext-game-session-token' . $session_id, $token, $user->getId());
            phMdelShard('ext-game-session-stake', $user);

            $this->startMetaGameSession($user, $tab_id, $session_id);
            $this->setSessionCloseListener($user, $tab_id);
        }
    }

    /**
     * Stores the ws channel to be able to close the session when the user closes the page and the channel is closed
     * @param DBUser $user
     * @param string|int $tab_id
     */
    private function setSessionCloseListener(DBUser $user, $tab_id = "")
    {
        $options = [
            'ext_session_ids' => implode("[-]", $this->getOpenSessionsByTabId($user, $tab_id))
        ];
        $tab_id = lic('hasExternalGameSessionTab', [], $user) ? $tab_id : '';
        phive('UserHandler')->addWsCloseListener('extgamesess' . $tab_id, 'finishExternalGameSession', $options, $user);
    }


    /**
     * To set the ws on the frontend
     * This will be called when a user goes into the game page before loading the iframe, before loading the actual game
     *  Click Game -> showExternalGameSessionPopup (Set balance Popup) -> ajaxUpdateExternalGameSessionBalance => Game page -> startExternalGameSession -> initGameSessionWithBalance
     * @param null $user
     * @param null $network
     * @param null $game_url
     * @param null $game
     * @param bool $show_demo
     * @return mixed
     */
    public function startExternalGameSession($user = null, $network = null, $game_url = null, $game = null, $show_demo = false)
    {
        $show_demo = filter_var($show_demo ?? false, FILTER_VALIDATE_BOOLEAN);
        $user = cu($user);
        if (empty($user) || $show_demo) {
            return false;
        }
        lic('showSessionBalancePopup', [$user, $game], $user);
        $data = json_decode(phMgetShard('ext-game-session-stake', $user), true);
        $data['tab_id'] = lic('hasExternalGameSessionTab', [], $user) ? rand(0, 10000) : '';
        if (!empty($data['token'])) {
            phMsetShard('ext-game-session-stake', $data, $user, 30);
        }
        $data['min_bet'] = $game['min_bet'];
        if ($data['ingame_popup'] ) {
            phMsetShard('ext-game-session-tab', $data['tab_id'], $user, 10800);
        }

        $tag = 'extgamesess' . $data['tab_id'];
        $channel = phive('UserHandler')->wsChannel($tag, $user->getId());
        $config = [
            'token' => $data['token'],
            'wsURL' => phive('UserHandler')->wsUrl($tag, true, [DBUserHandler::WS_EVENT_CLOSE], $channel),
            'network' => $network,
            'gameUrl' => $game_url,
            'game_id' => $game['game_id'],
            'tabId' => $data['tab_id'],
            'data' => $data,
            'isBos' => !empty($_GET['eid'])
        ];

        $popup = $this->showSessionBalancePopups($user, $game, false, true);
        ?>
        window.extSessHandler = lic('extSessHandler', []);
        window.extSessHandler.init(<?= json_encode($config) ?>, '<?= $popup ?>');
        <?php
        return $data['token'] === null;
    }


    /**
     * Return session balance popups
     *
     * @param       $user
     * @param       $game
     * @param bool  $check_session_balance
     * @param false $return
     *
     * @return string|void
     */
    public function showSessionBalancePopups($user, $game, $check_session_balance = true, $return = false)
    {
        $popup = null;
        $skip_popup = lic('skipPopupDueToFreeSpins', [$user, $game], $user);
        $select_popup = !$check_session_balance || lic('showSetSessionBalance', [$user, $game], $user);

        if (empty($skip_popup) && $select_popup) {
            // restriction has priority over other popups
            if (lic('hasGameSessionRestrictions', [$user], $user)) {
                $popup = 'game_session_temporary_restriction';
            } elseif (lic('hasAnOpenSession', [$user], $user)) {
                $popup = 'game_session_close_existing_and_open_new_session_prompt';
            } elseif (lic('showTooCloseNewGameSessionWarning', [$user], $user)) {
                $popup = 'game_session_limit_too_close_new_session_warning';
            } else {
                $popup = 'game_session_balance_set';
            }
        }

        if (!empty($popup) && !$return) {
            die($popup);
        }
        return $popup;
    }


    /**
     * Increment/decrement atomically the session balance
     *
     * @param int $amount
     * @return mixed|bool returns false if the query failed
     */
    public function incrementSessionBalance(int $amount)
    {
        $this->incrementMetaGameSessionBalance($this->session_entry['user_id'], $amount);
        $this->session_entry['balance'] += $amount;

        return $this->getLicSessionService($this->session_entry['user_id'])->incrementSessionBalance($amount);
    }

    /**
     *  Increment atomically the session balance and stake
     *
     * @param $real_increment
     * @param $bonus_increment
     * @return bool
     */
    public function addMoreStake($real_increment, $bonus_increment)
    {
        $this->session_entry['balance'] += $real_increment;
        $this->session_entry['stake'] += ($real_increment + $bonus_increment);

        return $this->getLicSessionService($this->session_entry['user_id'])->incrementSessionStake($real_increment, $bonus_increment);
    }

    /**
     * Handles the popup action to set or update session balance popup
     *
     * @param $post
     * @return array|bool[]
     */
    public function ajaxUpdateExternalGameSessionBalance($post)
    {
        $user = cuPl();
        if (empty($user)) {
            return ['success' => false, 'message' => 'No user.'];
        }

        $unique_session = lic('enforceUniqueGameSession', [$user, $post], $user);
        if (!empty($unique_session) && $unique_session['success'] === false) {
            return $unique_session;
        }
        $incr_balance = (int)((string)(phive('Cashier')->cleanUpNumber($post['balance']) * 100));
        $all_open_sessions_balance = ($this->getLicSessionService($user))->getAllOpenSessionsBalance($user);
        $user_balance              = $this->getMetaGameSessionBalance($user) - $all_open_sessions_balance;
        $max_stake                 = $this->getExtGameSessionStakes($user)['max_session_stake'];
        $balance_before_popup      = (int)phMgetShard('ext-game-session-balance-before-popup', $user->getId());
        $bonus_balance             = phive('Bonuses')->getBalanceByRef($post['game_ref'], $user->getId());
        $total_balance             = $user_balance + $bonus_balance;

        // print_r([ $max_stake, $balance_before_popup,  $all_open_sessions_balance, $incr_balance, $total_balance, $is_launching_other_game ]);

        if (empty($post['token'])) {

            $is_launching_other_game = phMgetShard('ext-game-session-stake', $user->getId());

            if ($incr_balance > $total_balance) {
                $message = sprintf('insufficient balance: session_amount:%d, user_balance:%d, open_balance:%d, bonus:%d, total:%d',
                    $incr_balance,
                    $user_balance,
                    $all_open_sessions_balance,
                    $bonus_balance,
                    $total_balance
                );

                phive('UserHandler')->logAction(
                    $user,
                    $message,
                    'setting-session-limit-error'
                );
                return ['success' => false, 'message' => 'User does not have enough balance.'];
            }
            // if max_stake is empty it means no upper limit exist (Ex. on ES user can bring his full balance) // TODO check if this need to be handled via lic setting/function /Paolo
            if (!empty($max_stake) && ($balance_before_popup != $all_open_sessions_balance || $incr_balance > $total_balance || ($incr_balance > $max_stake) || $is_launching_other_game)) {
                return ['success' => false, 'message' => 'Can not set the session stakes properly.'];
            }

            $new_session_token = phive()->uuid();
//            phMsetShard('ext-game-session-stake', ['real_stake' => $incr_balance, 'token' => $new_session_token, 'game_ref' => $post['game_ref'], 'tab_id' => $post['tab_id']], $user->getId(), 30);
            // TODO review if the other columns added for ES (below) may be causing problems for IT (above)
            phMsetShard('ext-game-session-stake', [
                'real_stake' => $incr_balance,
                'game_limit' => $post['gameLimit'],
                'set_reminder' => $post['setReminder'],
                'restrict_future_check' => ! empty($post['restrictFutureSessions']),
                'restrict_future_sessions' => (int) $post['restrictFutureSessions'],
                'token' => $new_session_token,
                'game_ref' => $post['game_ref'],
                'tab_id' => $post['tab_id'],
            ], $user->getId(), 30);
            return ['success' => true, 'newToken' => $new_session_token];
        }

        $session_id        = phMgetShard('ext-game-session-id' . $post['token'], $user->getId());
        $this->setSessionById($user->getId(), $session_id);
        $actual_stake      = $this->getSessionStake();
        $new_session_stake = $incr_balance + $actual_stake;
        $bonus_increment   = ($incr_balance < $user_balance) ? 0 : $incr_balance - $user_balance ;
        $real_increment    = $incr_balance - $bonus_increment;

        if ($balance_before_popup != $all_open_sessions_balance || $new_session_stake < 0 || $new_session_stake > $max_stake || $incr_balance > $total_balance) {
            return ['success' => false, 'message' => 'Can not update the session stakes.'];
        }

        return [
            'success' => $this->addMoreStake($real_increment, $bonus_increment),
            'result' => [
                'token' => $post['token'],
                'newBalance' => phive()->twoDec($this->getSessionBalance($user)),
            ],
        ];

    }

    /**
     * Post-message Trigger handler for limit popup. Some providers don't send the bet request when they detect the user is
     * performing a bet greater than his available balance and we need to get this trigger from the frontend
     * @param $post
     */
    public function ajaxOnExternalGameSessionLimitReached($post)
    {
        $user = cu();
        if (isset($post['token'])) {
            $post['session_id'] = phMgetShard('ext-game-session-id' . $post['token'], $user);
        }
        $this->onExternalGameSessionLimitReached($user, $post['session_id']);
    }

    /**
     * Checks if the player has reached the balance limit
     *
     * @param $user_id
     * @param $session_id
     * @return void
     */
    public function onExternalGameSessionLimitReached($user_id, $session_id)
    {
        $user = cu($user_id);
        $this->setSessionById($user, $session_id);

        if (empty($this->session_entry)) {
            return;
        }

        $stake = $this->getSessionStake();
        $max_stake = $this->getExtGameSessionStakes($user)['max_session_stake'];
        $is_free_spin_session = boolval($this->session_entry['is_free_spin_session']);

        $msg['token'] = phMgetShard('ext-game-session-token' . $session_id, $user);
        if ($stake < $max_stake) {
            if ($is_free_spin_session) {
                return;
            }

            $msg['popup'] = 'balance_addin_popup';
            phMsetShard('ext-game-session-data', $this->session_entry, $user->getId(), 30);
        } else {
            $msg['popup'] = 'balance_session_reached_popup';
        }
        $tab_id = lic('hasExternalGameSessionTab', [], $user) ? $this->getSessionTabId(uid($user_id)) : '';

        toWs($msg, 'extgamesess' . $tab_id, $user->getId());
    }

    /**
     * Finishes external game session
     *
     * @param $user
     * @param $secondCall bool added to prevent circular calls between this method and finishUniqueGameSession
     * @return bool
     */
    public function finishExternalGameSession($user, bool $secondCall = false)
    {
        if(empty($this->session_entry['user_game_session_id'])){
            return true;
        }

        $user = cu($user);
        $end_time = phive()->hisNow();
        $nw = phive('Casino');
        $ugs = phive('SQL')->sh($user)->loadAssoc("SELECT * FROM users_game_sessions WHERE id = {$this->session_entry['user_game_session_id']}");
        $nw->finishUniqueGameSession($ugs, $user->data, $secondCall);

        if (!$this->endParticipation($user, $end_time)) {
            phive()->dumpTbl('error-end-ext-session', [$user->getId(), $this->session_entry]);

            phive('Logger')->getLogger('game_providers')->error(__METHOD__ . 'error-end-ext-session', [
                'user' => $user->getId(),
                'jurisdiction' => $user->getCountry(),
                'session_entry' => $this->session_entry,
                'user_game_session' => $ugs,
            ]);
            return false;
        }
        return true;
    }

    /**
     * Session end on page exit/reload/redirect
     * This method is called when the user closes or switches a game. It will be called from IT.js when user opens a new game from search bar or
     * closes on multiview window.
     * Also will be called when the user navigates away from the page from wsSessionHandler.js (on websocket channel close)
     *
     * @param $post
     * user_id
     * token -> used to retrieve session_id from memory (optional)
     * session_id -> the participation id stored in redis (optional)
     * ext_session_ids -> the participation ids array (optional)
     *
     */
    public function ajaxFinishExternalGameSession($post)
    {
        $user = cu($post['user_id']);
        if (empty($user)){
            return;
        }

        if (!empty($post['token'])){
            $session_id = phMgetShard('ext-game-session-id' . $post['token'], $post['user_id']);
            $token = $post['token'];
            $this->doFinishExternalGameSession($user, $session_id, $token);
        } else if (!empty($post['ext_session_id'])){
            $session_id = $post['ext_session_id'];
            $token = phMgetShard('ext-game-session-token' . $session_id, $post['user_id']);
            $this->doFinishExternalGameSession($user, $session_id, $token);
        } else if(!empty($post['ext_session_ids'])) {
            foreach (explode('[-]', $post['ext_session_ids']) as $session_id) {
                $token = phMgetShard('ext-game-session-token' . $session_id, $post['user_id']);
                $this->doFinishExternalGameSession($user, $session_id, $token);
            }
        }
    }

    /**
     * In order to kill any stuck session
     *
     * @param DBUser $user
     */
    public function cleanExternalGameSession(DBUser $user)
    {
        phMdelShard('meta-ext-games-session', $user->getId());
        $session_service = $this->getLicSessionService($user);
        $orphan_sessions = $session_service->getAllOpenExternalSessions($user);
        foreach ($orphan_sessions as $session) {
            $end_time = phive()->hisNow();
            $session_service->endPlayerSession($user, $session['id'], $end_time);
        }
    }

    /**
     * Performs the individual session closing and cleans memory keys used during the game
     * @param DBUser $user
     * TODO review if below comments with "null if not in redis" still applies or now those are always passed/forced properly, add to function params "?string" ???. /Paolo
     * @param string $session_id - session_id or null in case the key is not found in redis
     * @param string|null $token - token or null in case the key is not found in redis
     */
    public function doFinishExternalGameSession(DBUser $user, string $session_id, string $token = null)
    {
        if (!empty($session_id)) {
            phMdelShard('ext-game-session-id' . $token, $user->getId());
            phMdelShard('ext-game-session-token' . $session_id, $user->getId());
            $this->setSessionById($user, $session_id);
            if (!$this->isGameSessionFinished()) {
                $this->finishExternalGameSession($user);
                $this->reloadBalance($user);
            }
        }
    }

    /**
     * Finishes the current participation
     *
     * @param $user
     * @param $end_time
     * @return bool
     */
    public function endParticipation($user, $end_time)
    {
        if (!$this->hasSessionBalance() || $this->isGameSessionFinished()) {
            return false;
        }
        $session_service = ($this->getLicSessionService($user));
        $this->finishMetaGameSessionEntry($user, $this->session_entry['id']);

        return $session_service->endPlayerSession($user, $this->session_entry['id'], $end_time);
    }

    /**
     * Keeps track of all game sessions opened to allow the player use his total balance
     *
     * @param $user
     * @param string|int $tab_id
     * @param null $session_id
     * @return array|bool
     */
    public function startMetaGameSession($user, $tab_id = '', $session_id = null)
    {
        if (!($data = $this->getMetaGameSession($user->getId()))) {
            $data = [
                'balance' => $user->getBalance(),
                'open_sessions' => []
            ];
        }
        if (!empty($session_id) && !in_array($session_id, $data['open_sessions'])) {
            $data['open_sessions'][$session_id] = ['last_bet' => 0, 'tab_id' => $tab_id];
        }
        phMsetShard('meta-ext-games-session', $data, $user->getId());

        return $data;
    }

    /**
     * Returns the sessions opened on current browser tab
     * @param DBUser $user
     * @param string|int $tab_id
     * @return array
     */
    public function getOpenSessionsByTabId(DBUser $user, $tab_id)
    {
        $sessions_in_tab = [];
        if (($data = $this->getMetaGameSession($user->getId()))) {
            foreach ($data['open_sessions'] as $session_id => $session_data) {
                if ($session_data['tab_id'] == $tab_id) {
                    array_push($sessions_in_tab, $session_id);
                }
            }
        }
        return $sessions_in_tab;
    }

    /**
     * Get the browser tab the session is being played on
     * @param int $user_id
     * @return int|mixed
     */
    public function getSessionTabId(int $user_id)
    {
        $data = $this->getMetaGameSession($user_id);
        if (!empty($data)) {
            $session = $data['open_sessions'][$this->session_entry['id']];
            return $session['tab_id'];
        }
        return '';
    }

    /**
     * Increments/decrements the total virtual balance (meta balance) of the user
     * @param $user_id
     * @param $amount
     */
    public function incrementMetaGameSessionBalance($user_id, $amount)
    {
        $data = $this->getMetaGameSession($user_id);
        if (!empty($data)) {
            $data['balance'] += (int)$amount;

            if ($amount < 0 && !empty($data['open_sessions'][$this->session_entry['id']])) {
                $data['open_sessions'][$this->session_entry['id']]['last_bet'] = abs($amount);
            }
            phMsetShard('meta-ext-games-session', $data, $user_id);
        }
    }

    /**
     * Removes one session from the meta session
     *
     * @param $user
     * @param $session_id
     */
    public function finishMetaGameSessionEntry($user, $session_id)
    {
        if ($data = $this->getMetaGameSession($user->getId())) {

            if (!empty($data['open_sessions'][$session_id])) {
                if (count($data['open_sessions']) == 1) {
                    phMdelShard('meta-ext-games-session', $user->getId());
                } else {
                    unset($data['open_sessions'][$session_id]);
                    phMsetShard('meta-ext-games-session', $data, $user->getId());
                }
            }
        }
    }

    /**
     * For situations where we want to make sure we don't have ANY meta session
     * @param $user
     * @return bool
     */
    public function purgeMetaGameSession($user): bool
    {
        $user = cu($user);
        $data = phMgetShard('meta-ext-games-session', $user->getId());

        phive('Logger')->getLogger('game_providers')->info(__METHOD__, [
            'user' => $user->getId(),
            'meta_session' => $data,
        ]);

        if(!empty($data)) {
            phMdelShard('meta-ext-games-session', $user->getid());
            return true;
        }

        return false;
    }


    /**
     * Gets the meta game session for the player
     * Meta session: A solution to avoid modifying the user real balance until the player leaves the game,
     * also making possible to use the same balance for different games without having to reserve the balance.
     *
     * @param $user_id
     * @return array|bool
     */
    public function getMetaGameSession($user_id)
    {
        $uid = uid($user_id);
        $data = phMgetShard('meta-ext-games-session', $uid);
        return empty($data) ? false : json_decode($data, true);
    }

    /**
     * Gets the real balance that the player has for playing
     * Note that this information is the sum of the balances in ext_game_participations table an can also be calculated from there
     * @param $user
     * @return mixed
     */
    public function getMetaGameSessionBalance($user)
    {
        $user = cu($user);
        $meta_session = $this->getMetaGameSession($user->getId());
        if (empty($meta_session)) {
            return $user->getBalance();
        }
        return max((int)$meta_session['balance'], 0);
    }


    /**
     * We set the session entry in the class given the id
     *
     * @param $user
     * @param $id
     */
    public function setSessionById($user, $id)
    {
        $this->session_entry = $this->getLicSessionService($user)->getPlaySessionById($user, $id);
    }

    /**
     * We set the session entry in the class given the token
     * In some cases we don't have the external session id and we have to rely on the token
     *
     * @param $user
     * @param $token
     */
    public function setExternalSessionByToken($user, $token)
    {
        $user = cu($user);
        $this->session_entry = $this->getLicSessionService($user)->getPlaySessionByToken($user, $token);
    }

    /**
     * Get the balance from a prepopulated session entry
     *
     * @param $user
     * @return int
     */
    public function getSessionBalance($user)
    {
        $user = cu($user);
        $user_balance = $user->getBalance();
        if ($this->isGameSessionFinished() || empty($user_balance)) {
            return 0;
        }
        return min($user_balance, (int)$this->session_entry['balance']);
    }

    /**
     * Get the balance from a pre-populated session entry
     *
     * @return int
     */
    public function getSessionStake()
    {
        return (int)$this->session_entry['stake'];
    }

    /**
     * Check if the session entry exists
     *
     * @return bool
     */
    public function hasSessionBalance()
    {
        return !empty($this->session_entry);
    }

    /**
     * To check if we should show the user the set_session_balance popup or not when going to the game
     * @param $user
     * @param array|null $game
     * @return bool
     */
    public function showSetSessionBalance($user, array $game = null)
    {
        if ($user === false) {
            return false;
        }
        $lic_show_popup = empty($game) || lic('showSessionBalancePopup', [$user, $game], $user);
        return $lic_show_popup && isLogged() && empty(phMgetShard('ext-game-session-stake', $user));
    }

    /**
     * Sets the window as initialized. To be able to kill orphan sessions if user leaves the page before ws channel is created
     * @param $post
     */
    public function ajaxExtGameSessWsStarted($post)
    {
        $user = cuPl();
        [$need_tab, $tab_id] = lic('hasExternalGameSessionTab', [], $user) ? [!empty($post['tab_id']), $post['tab_id']] : [true, ''];
        if (!empty($user) && $need_tab) {

            $ws_channel = phive('UserHandler')->wsChannel('extgamesess'.$tab_id, $user->getId());
            $channel_info = phMgetArr($ws_channel);
            if (!empty($channel_info)) {
                $channel_info['ws_started'] = true;
                phMsetArr($ws_channel, $channel_info);
            }
        }
    }

    /**
     * If the game session is actually finished
     * @return bool
     */
    public function isGameSessionFinished()
    {
        return $this->hasSessionBalance() && $this->session_entry['ended_at'] !== phive()->getZeroDate();
    }

    /**
    * Check if game session balance is determined by game type (IT) and if so, return game type session balance
    *
    * @param $user User
    * @return mixed
    */
    private function getGameSessionBalance(User $user)
    {
        $request_game_id = $_POST['game_ref'] ?? $_POST['game_id'];
        $ext_game_id = phive('MicroGames')->getGameRefByIdWithAutoDeviceDetect($request_game_id ?? curPlaying($user));
        $current_game_type = lic('getCurrentGameType', [$ext_game_id], $user);
        $session_balance = $this->getLicSetting('session_balance_limit')['popup_default_values'];

        if($this->getLicSetting('has_game_type_session_balance') && !empty($current_game_type)) {
          $session_balance = $this->getLicSetting('session_balance_limit')[$current_game_type]['popup_default_values'];
        }

        return $session_balance;
    }

    /**
     * Gets the wager limits
     * @param $user
     * @return array
     */
    public function getExtGameSessionStakes($user)
    {
        $balance_ss = $this->getGameSessionBalance($user);
        foreach ($balance_ss as &$limit) {
            $limit = $limit * 100;
        }
        $rg = rgLimits();
        $rg_limits = $rg->getByTypeUser($user, 'wager');
        if (empty($rg_limits)) {
            return $balance_ss;
        }
        $d = $rg->getRemaining($rg_limits['day']);
        $w = $rg->getRemaining($rg_limits['week']);
        $m = $rg->getRemaining($rg_limits['month']);
        $rg_wager_limit = min($d, $w, $m);
        $max_stake = min($rg_wager_limit, $balance_ss['max_session_stake']);
        $default_stake = min($rg_wager_limit, $balance_ss['default_session_stake']);

        return ['max_session_stake' => $max_stake, 'default_session_stake' => $default_stake];
    }


    /**
     * Reload user balance on homepage
     * @param DBUser|null $user
     */
    public function reloadBalance($user = null)
    {
        $u = cu($user);
        toWs(['cash' => $u->getAttr('cash_balance', true)], 'balance', $u->getId());
    }


    /**
     * Applies to Mobile games, when we should delay the execution of the game launcher
     *
     * force: Will display the popup on launch for direct url launch and then load the game. Also works on game splip
     * split: Will not pause the game launch (balance popup already displayed in the lobby), but will pause any new split game launched
     *
     * @param $user
     * @return string
     */
    public function delayMobileLaunch($user, $demo = false, $game = null)
    {
        if($demo) {
            return 'split';
        }

        return $this->showSetSessionBalance($user, $game) ? 'force' : 'split';
    }

    /**
     * Check if the next round will trigger a session balance popup
     * This function needs to be called at the end of each round, commonly providers send a win request, even with 0 amount
     * See Netent or Playngo deposit method
     *
     * @param null $user
     * @return void
     */
    public function checkNextRound($user = null)
    {
        if ($this->hasSessionBalance()) {
            $data = $this->getMetaGameSession($this->session_entry['user_id']);

            if (!empty($data['open_sessions'][$this->session_entry['id']])) {
                $last_bet = $data['open_sessions'][$this->session_entry['id']]['last_bet'];
                $bonus_balance = phive('Bonuses')->getBalanceByRef($this->session_entry['ext_game_id'], $this->session_entry['user_id']);
                $total_balance = $bonus_balance + $this->session_entry['balance'];

                if (($this->session_entry['balance'] == 0 && $bonus_balance == 0) || $last_bet > $total_balance) {
                    $all_open_sessions_balance = ($this->getLicSessionService($user))->getAllOpenSessionsBalance($user);
                    phMsetShard('ext-game-session-balance-before-popup', $all_open_sessions_balance, $this->session_entry['user_id']);
                    lic('onExternalGameSessionLimitReached', [$user, $this->session_entry['id']], $user);
                }
            }
        }
    }

    /**
     * When freespins are finished we trigger may need to trigger an action. For Italy we will show a popup to alert the player that he will be redirected
     *
     * @param null $user
     */
    public function extSessionFreespinsFinished($user = null)
    {
        $user = cu($user);
        if ($this->hasSessionBalance()) {
            $tab_id = lic('hasExternalGameSessionTab', [], $user) ? $this->getSessionTabId($user->getId()) : '';
            lic('onSessionFreespinsFinished', [$user, $this->session_entry, $tab_id], $user);
        }
    }

    /**
     * Get the service that implements the external game session interface
     *
     * @param $user
     * @return \IT\Services\AAMSSession\AAMSSessionService|\ES\Services\SessionService
     */
    public function getLicSessionService($user)
    {
        if (empty($this->licSessionService)) {
            $this->licSessionService = lic('getExternalSessionService', [], $user);
        }

        return $this->licSessionService;
    }
}
