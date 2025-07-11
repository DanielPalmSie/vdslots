<?php

trait PreventMultipleGameSessionsTrait
{

    /**
     * Setup game session close listener
     * @param $user_id
     */
    public function setGameSessionCloseListener($user_id): void
    {
        $user = cu($user_id);
        if (empty($user)) {
            return;
        }
        $tag = $_SESSION['next_unique_game_session_id'] ?? 'gs-' . (new DateTime())->getTimestamp();
        unset($_SESSION['next_unique_game_session_id']);
        $ref = phive('UserHandler')->addWsCloseListener($tag, 'closeGameSession', ['tag' => $tag, 'open_lic_func' => 'onWsConnectionOpen'], $user);
        $this->handleWsUserGameSessions($user_id, $ref, 'add', 'remove-on-logout');
        $events = [
            DBUserHandler::WS_EVENT_OPEN,
            DBUserHandler::WS_EVENT_CLOSE,
        ];
        ?>
        <script>
            window.game_session_id = '<?=$tag?>';
            var isConnectionOpen = function (websocket_connection) {
                return websocket_connection
                    && websocket_connection.readyState !== WebSocket.CLOSED
                    && websocket_connection.readyState !== WebSocket.CLOSING;
            };
            var parseObject = function (value) {
                var res = value;
                try {
                    res = JSON.parse(res);
                } catch (e) {
                    res = {};
                }
                return res;
            };
            var uniqueGameSessionCheck = function () {
                mgAjax({action: 'unique-game-session-check', game_session_tag: "<?= $tag ?>"}, function (res) {
                    res = parseObject(res);
                    if (!res.success) {
                        licFuncs.preventMultipleGameSessionsHandler({message: res.message});
                    }
                });
            };

            var session_connection = doWs('<?= phive('UserHandler')->wsUrl($tag, true, $events, $ref) ?>', function (message) {
                var data = parseObject(message.data);
                if (data['close-game-session'] === true) {
                    licFuncs.preventMultipleGameSessionsHandler({message: "<?= t('game-session-limit-unique.block') ?>"});
                }
                if (data['popup'] === 'closed_by_new_session') {
                    licFuncs.stopGame();
                    session_connection.close(WS_PREVENT_RECONNECT);
                    mboxMsg(data.msg, true, function () {
                        gotoLang('/');
                    }, 260);
                }
            }, function () {
                mgAjax({
                    action: 'unique-game-session-check',
                    game_session_tag: "<?= $tag ?>",
                    cache: true
                });
                var retries = 15;
                var closeInterval = setInterval(function () {
                    retries--;
                    // connection might be open now, but it could be too late so confirming with server
                    if (retries === 0 || isConnectionOpen(session_connection)) {
                        clearInterval(closeInterval);
                        uniqueGameSessionCheck();
                    }
                }, 1000);
            }, function (conn) {
                session_connection = conn;
                session_connection.onopen = uniqueGameSessionCheck;
            })
            session_connection.onopen = uniqueGameSessionCheck;
        </script>
        <?php
    }

    /**
     * Redirect, return json or just the status if user attempts to open a new game session
     * @param $user
     * @param false $redirect
     * @param false $die
     * @return array|bool|mixed|string
     */
    public function preventMultipleGameSessions($user, $redirect = false, $die = false)
    {
        $logger = phive('Logger')->getLogger('game_providers');
        $logger->trackProcess();

        $logger->debug(
            'PreventMultipleGameSessionsTrait::preventMultipleGameSessions',
            [
                'user' => $user,
                'redirect' => $redirect,
                'die' => $die,
            ]
        );
        $user = cu($user);
        if (empty($user)) {
            $logger->debug('PreventMultipleGameSessionsTrait::preventMultipleGameSessions->emptyUser');
            return true;
        }
        $sessions = $this->handleWsUserGameSessions($user->getId());
        if ($sessions[0] === $_SESSION['next_unique_game_session_id']) {
            $logger->debug('PreventMultipleGameSessionsTrait::preventMultipleGameSessions->newSession',
                [
                    'sessions' => $sessions,
                    'unique-game-session-id' => $_SESSION['next_unique_game_session_id']
                ]
            );
            return false;
        }
        $stop = count($sessions) > 0;
        if (!$stop) {
            $logger->debug('PreventMultipleGameSessionsTrait::preventMultipleGameSessions->stop',
                [
                    'session-count' => count($sessions)
                ]
            );
            return false;
        }

        if ($die) {
            $logger->debug('PreventMultipleGameSessionsTrait::preventMultipleGameSessions->die');
            //Close every open game session for given user
            $casino = phive('Casino');
            $casino->finishGameSession($user->getId());
            return false;
        }

        if ($redirect) {
            $logger->debug('PreventMultipleGameSessionsTrait::preventMultipleGameSessions->redirect');
            return licHtml(self::PREVENT_MULTIPLE_GAME_SESSIONS_HANDLER, $user);
        }

        $logger->debug('PreventMultipleGameSessionsTrait::preventMultipleGameSessions->end');
        return false;
    }

    /**
     * Handle all actions required on the open game sessions
     * @param string|int $user_id
     * @param string|null $tag
     * @param string|null $action
     * @param string $key
     * @return array|mixed
     */
    public function handleWsUserGameSessions($user_id, $tag = null, $action = null, $key = 'open-game-sessions')
    {
        $logger = phive('Logger')->getLogger('game_providers');
        $logger->trackProcess();

        $sessions = json_decode(phMgetShard($key, $user_id));
        $logger->debug(
            'PreventMultipleGameSessionsTrait::handleWsUserGameSessions',
            [
                'user_id' => $user_id,
                'tag' => $tag,
                'action' => $action,
                'key' => $key,
                'sessions' => $sessions
            ]
        );

        if (empty($sessions)) {
            $sessions = [];
            $logger->debug('PreventMultipleGameSessionsTrait::handleWsUserGameSessions->emptySession');
        }

        if ($action === 'add') {
            $logger->debug('PreventMultipleGameSessionsTrait::handleWsUserGameSessions->add');
            // we expect maximum 1 item in $sessions hence comparing tag with first element
            if (!empty($sessions) && $sessions[0] !== $tag) {
                $logger->debug('PreventMultipleGameSessionsTrait::handleWsUserGameSessions->closeOldSesion');
                toWs(['close-game-session' => true], $tag, $user_id);
                return $sessions;
            }
            $sessions = array_unique(array_merge($sessions, [$tag]));
            phMsetShard($key, array_values($sessions), $user_id);
        } elseif ($action === 'remove') {

            $logger->debug('PreventMultipleGameSessionsTrait::handleWsUserGameSessions->remove');
            $sessions = array_filter($sessions, static function ($item) use ($tag) {
                return $item !== $tag;
            });
            phMsetShard($key, array_values($sessions), $user_id);
        }


        $logger->debug('PreventMultipleGameSessionsTrait::handleWsUserGameSessions->end',
            [
                    'sessions' => $sessions
            ]
        );
        return $sessions;
    }

    /**
     * Notify browser tabs that a new game session was open
     * @param DBUser $user
     * @param string $new_session
     * @return void
     */
    public function notifyNewGameSessionOpen($user, $new_session): void
    {
        $logger = phive('Logger')->getLogger('game_providers');
        $logger->trackProcess();
        $logger->debug(
            'PreventMultipleGameSessionsTrait::notifyNewGameSessionOpen',
            [
                'user' => $user,
                'newSessions' => $new_session
            ]
        );
        if (empty($user)) {
            return;
        }

        $sessions = $this->handleWsUserGameSessions($user->getId());
        foreach ($sessions as $session) {
            if ($session !== $new_session) {
                toWs([
                    'popup' => 'closed_by_new_session',
                    'msg' => t('closed.by.new.session'),
                    'new_session' => $new_session
                ], $session, $user->getId());   
            }
        }
    }

    /**
     * Clean unique game session from redis
     * Remove all open game session on logout to avoid having hanging sessions that will prevent game play on login from a different browser
     * @param DBUser|null $u
     */
    public function cleanUniqueGameSession($u): void
    {
        $logger = phive('Logger')->getLogger('game_providers');
        $logger->trackProcess();
        $logger->debug(
            'PreventMultipleGameSessionsTrait::cleanUniqueGameSession',
            [
                'user' => $u
            ]
        );
        if (empty($u)) {
            return;
        }
        $channels = json_decode(phMgetShard('remove-on-logout', $u)) ?? [];
        foreach ($channels as $channel) {
            phMdel($channel);
        }
        phMdelShard('remove-on-logout', $u);
        phMdelShard('open-game-sessions', $u);
    }

    /**
     * Handle websocket disconnect
     * @param $data
     */
    public function ajaxCloseGameSession($data): void
    {
        $logger = phive('Logger')->getLogger('game_providers');
        $logger->trackProcess();
        $logger->debug(
            'PreventMultipleGameSessionsTrait::cleanUniqueGameSession',
            [
                'data' => $data
            ]
        );
        $this->handleWsUserGameSessions($data['user_id'], $data['tag'], 'remove');
    }

    /**
     * Handle websocket connected
     * @param $post
     */
    public function ajaxOnWsConnectionOpen($post): void
    {
        $user = cu($post['user_id']);
        $tag = $post['tag'];

        $logger = phive('Logger')->getLogger('game_providers');
        $logger->trackProcess();
        $logger->debug(
            'PreventMultipleGameSessionsTrait::ajaxOnWsConnectionOpen',
            [
                'user' => $user,
                'tag' => $tag
            ]
        );

        if (empty($user) || empty($tag)) {
            return;
        }

        $ws_channel = phive('UserHandler')->wsChannel($tag, $user->getId());
        $channel_info = phMgetArr($ws_channel);
        if (empty($channel_info)) {
            return;
        }
        $channel_info['ws_started'] = true;
        phMsetArr($ws_channel, $channel_info);

        $this->handleWsUserGameSessions($user->getId(), $tag, 'add');
    }

}
