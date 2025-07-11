<?php


trait RealityCheckTrait
{
    /**
     * Return all the data needed to populate the RC dialog.
     *
     * @param $user
     * @param string $lang
     * @param string $ext_game_name
     * @param bool $translate
     * @return array
     */
    public function getRealityCheck($user, $lang = 'en', string $ext_game_name, bool $translate = true)
    {
        $string = $this->getRcPopupMessageString();
        $rc_data = $this->getRcPopupMessageData($user, $ext_game_name);
        $header_alias = 'reality-check.header.title';
        $popup_title = null;

        if(!useOldDesign()) {
            $popup_title = '<div><img class="login-popup__image" src="/diamondbet/images/' . brandedCss() . 'set-time.png" alt="set-time-image"></div>';
        }

        return [
            'header' => $translate ? t($header_alias, $lang) : $header_alias,
            'title' => $popup_title,
            'message' => tAssoc($string, $rc_data, $lang), // already translated for easier use on the old site
            'messageString' => $string, // needed on user-service
            'messageData' => $rc_data, // needed on user-service
            'buttons' => $this->getRcPopupButtons($user, $lang),
        ];
    }

    /**
     * Return the localized string for the RC dialog
     * will check if a license define a different message
     *
     * @return string
     */
    public function getRcPopupMessageString()
    {
        return 'reality-check.msg.elapsedtime';
    }

    /**
     * Calculate how many minutes passed since the game session opened
     * Used by default on RC when enabled only in game play
     * @param $user
     * @param string $ext_game_name
     * @return false|float
     */
    public function getMinutesReached($user, string $ext_game_name)
    {
        $game_start_time = phMgetShard("cur-game-start-time-{$ext_game_name}", $user->getId());
        if (! $game_start_time) {
            return 0;
        }

        return floor((time() - $game_start_time) / 60);
    }

    /**
     * Return the data needed to properly replace the localized string params {{xxxxx}}.
     * @param $user
     * @param string $ext_game_name
     * @return array
     */
    public function getRcPopupMessageData($user, string $ext_game_name)
    {
        $user = cu($user);
        if (empty($user)) {
            return [];
        }
        $rg = phive('Licensed')->rgLimits();
        $rg_limits = $rg->getRcLimit($user);
        $winloss = nfCents($user->winLossBalance()->getTotal(), true);

        return [
            'minutes' => $rg_limits['cur_lim'], // interval
            'minutes_reached' => $this->getMinutesReached($user, $ext_game_name),
            'currency' => $user->data['currency'],
            'winloss' => $winloss,
            'lost' => $winloss >= 0 ? '' : t('lost'),
            'won' => $winloss >= 0 ? t('won') : '',
        ];
    }

    /**
     * Return the buttons that need to displayed in the popup
     * will check if a license define different buttons
     *
     * @param $user
     * @param $lang
     * @return array
     */
    public function getRcPopupButtons($user, $lang = 'en')
    {
        return [
            [
                'string' => 'reality-check.label.closeAndResumeGame',
                'action' => 'continue',
                'url' => '',
            ],
            [
                'string' => 'reality-check.label.gameHistory',
                'action' => 'gameHistory',
                'url' => phive('Casino')->getHistoryUrl(false, $user, $lang),
            ],
            [
                'string' => 'reality-check.label.leaveGame',
                'action' => 'leaveGame',
                'url' => phive('Casino')->getLobbyUrl(false, $lang),
            ],
        ];
    }

    /**
     * @param $rci - reality_check_interval set by the user
     * @param bool $return_non_translated - param used by "user-service" to retrieve non translated data
     *
     * @return array|bool
     */
    public function isValidRealityCheckDuration($rci, $return_non_translated = false)
    {
        $rc_configs = $this->getRcConfigs();
        $rc_steps = $rc_configs['rc_steps'];
        $rc_min = $rc_configs['rc_min_interval'];
        $rc_max = $rc_configs['rc_max_interval'];

        $error = false;
        $message = '';
        $errors_array = []; // for user-service
        if (empty($rci)) {
            $error = true;
            $message .= t('reality-check.error.specify.value') . '<br>';
            $errors_array[] = 'reality-check.error.specify.value';
        }
        if (($rci < $rc_min || $rci > $rc_max || $rci % $rc_steps !== 0)) {
            $error = true;
            $lang = phive('Localizer')->getCurNonSubLang();
            $message .= tAssoc('reality-check.error.value.between', ['rc_min' => $rc_min, 'rc_max' => $rc_max], $lang);
            $message .= ' ' . tAssoc('reality-check.error.value.steps', ['rc_steps' => $rc_steps], $lang). '<br>';
            $errors_array[] = 'reality-check.error.value.between';
            $errors_array[] = 'reality-check.error.value.steps';
        }
        $ud = ud();
        if (empty($ud)) {
            $error = true;
            $message = t('reality-check.error.do.login', $lang). '';
            $errors_array = ['reality-check.error.do.login'];
        }

        $status = $error ? 'error' : 'ok';

        if ($return_non_translated) {
            return [
                'valid' => ! $error,
                'errors' => $errors_array,
            ];
        }

        return [
            'status' => $status, // ok | error
            'message' => $message,
        ];
    }

    /**
    *  Resets the reality check counter. Called when the player clicks on continue
    *
    *  Call Example:  licJson('startRc', {'user_id' : <?= $this-> user_id ?> });
    */
    public function ajaxStartRc($post = [])
    {
        $user = cu($post['user_id']);

        return json_encode(['success' => true, 'result' => rgLimits()->startRc($user)]);
    }

    /**
     * Log when a RC popup is triggered for a player
     * Avoid logging the action more than once every XX min if a player has multiple tabs open
     *
     * @param $user
     * @param $msg_data
     * @param string $ext_game_name
     * @return bool  / if the popup has to show
     */
    public function logRealityCheckAction($user, $msg_data, string $ext_game_name)
    {
        $reached_interval = $msg_data['messageData']['minutes_reached'] > 0 && $msg_data['messageData']['minutes_reached'] % $msg_data['messageData']['minutes'] == 0;

        $force_trigger = false;
        $rc_last_triggered = 'rc_last_triggered_' .  $ext_game_name;
        if (empty($_SESSION[$rc_last_triggered]) && $reached_interval) {
            $_SESSION[$rc_last_triggered] = phive()->hisNow();
            $force_trigger = true;
        }

        // if enough time has pass or the first time (forced)
        $enough_time_passed = phive()->hisMod("- {$msg_data['messageData']['minutes']} minutes") >= $_SESSION[$rc_last_triggered];
        if (($enough_time_passed && $reached_interval) || $force_trigger) {
            $_SESSION[$rc_last_triggered] = phive()->hisNow();
            // by default RC is from the beginning of a game session, but for SE we check from the login (so we don't have "game_id" in the messageData)
            $game_or_session = !empty($ext_game_name) ? "on game {$ext_game_name}" : 'from login';
            $action_msg = $user->getUsername() . " - RC triggered after {$msg_data['messageData']['minutes_reached']} min $game_or_session, user has won/loss {$msg_data['messageData']['winloss']} {$msg_data['messageData']['currency']} next RC will trigger in {$msg_data['messageData']['minutes']} min";
            phive('UserHandler')->logAction($user, $action_msg, 'rc-rgl-triggered');
        }

        return $reached_interval;
    }

    /**
     * Return the localized string for the RC dialog
     * will check if a license define a different message
     *
     * @param $user
     * @param string $lang
     * @return string
     */
    public function getRcPopupHistoryLinkString($user, $lang = 'en')
    {
        $history_link = phive('Casino')->getHistoryUrl(false, $user);
        $string = "<br><a href='". $history_link ."' target='blank\'>".t('reality-check.msg.historylink', $lang)."</a>";

        return $string;
    }

    /**
     * Return all the data needed to populate the RC dialog.
     *
     * @param $user
     * @param string $lang
     * @param string $ext_game_name
     * @return string
     */
    public function getRealityCheckWithHistoryLink($user, $lang = 'en', string $ext_game_name)
    {
        $string = $this->getRcPopupMessageString();
        $rc_data = $this->getRcPopupMessageData($user, $ext_game_name);
        $history_link = $this->getRcPopupHistoryLinkString($user, $lang);

        return tAssoc($string, $rc_data, $lang) . $history_link; // already translated for easier use on the old site
    }
}
