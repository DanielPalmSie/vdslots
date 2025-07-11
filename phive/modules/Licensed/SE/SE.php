<?php

use Videoslots\RgLimits\Builders\Locks\LockInterface;
use Videoslots\User\CustomLoginTop\CustomLoginTopForSE;
use Videoslots\User\RgData\RgLogoForSE;

require_once __DIR__ . '/../Traits/RealityCheckTrait.php';
require_once __DIR__ . '/../Traits/ResponsibleGamblingTrait.php';

class SE extends Licensed
{
    use RealityCheckTrait;
    use ResponsibleGamblingTrait;

    public const FORCED_LANGUAGE = 'sv';
    protected const SELF_LOCK_COOL_OFF_DAYS = 3;

    public string $ext_exclusion_name = 'spelpaus';

    /**
     * @var string
     */
    public const RG_LOGO_TYPE_WHITE = 'white';

    /**
     * @var string
     */
    public const RG_LOGO_TYPE_BLACK = 'black';

    /**
     * @var string
     */
    public const RG_LOGO_TYPE_LANDING = 'landing';

    /**
     * @var string
     */
    private const DEFAULT_NATIONALITY = 'SE';

    public $personal_number_length = 12;

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'nationality'
    ];

    public function nidToDob($nid)
    {
        return substr($nid, 0, 4) . '-' . substr($nid, 4, 2) . '-' . substr($nid, 6, 2);
    }

    /**
     * Controls whether ot not the player can get a reload bonus or not.
     *
     * We first check if the current bonus we're contemplating giving to the player
     * is a casino or casinowager bonus. If it is not (eg freespin) we return true right away.
     *
     * If it is either a casino or casinowager we get the player's old casino and / or casinowager bonuses
     * from the database and if there are any rows we return false.
     *
     * @param array $b A row from bonus_types.
     * @param DBUser $u_obj The player object.
     *
     * @return bool True if the player can get the bonus, false otherwise.
     */
    public function canGetReloadBonus($b, $u_obj)
    {
        $types = ['casino', 'casinowager'];
        if (!in_array($b['bonus_type'], $types)) {
            return true;
        }
        $in = phive('SQL')->makeIn($types);
        $bs = phive('Bonuses')->getUserBonuses($u_obj->getId(), '', '', "IN($in)");
        return empty($bs);
    }

    public function testIso()
    {
        return $this->getLicSetting('test');
    }

    /**
     * Override default message for RC dialog
     *
     * @return string
     */
    public function getRcPopupMessageString()
    {
        return 'reality-check.msg.elapsedtime.se';
    }

    /**
     * Override localized string replace data on RC dialog
     *
     * @param null $user
     * @param null $lang
     * @return array|bool
     */
    public function getRcPopupMessageData($user = null)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }
        $rc_interval =  rgLimits()->getRcLimit($user)['cur_lim'] ?? $this->getRcConfigs()['rc_default_interval'];
        $time_passed = $this->rcElapsedTime($user);

        $sessionBetsAndWins = phive('UserHandler')->sumGameSessionsBySessionId($user->getId(), $user->getCurrentSession()['id']);
        $total_session_bet = $sessionBetsAndWins['bet_amount'];
        $total_session_win = $sessionBetsAndWins['win_amount'];
        $total_session_profit = $total_session_win - $total_session_bet;

        $aRcMsg = array(
            'minutes'         => $rc_interval, // not needed on the localized string, remove?
            'minutes_reached' => round($time_passed / 60),
            'currency'        => $user->data['currency'],
            'winloss'         => phive()->twoDec($total_session_profit),
            'lost'            => phive()->twoDec($total_session_bet), // not needed on the localized string, remove?
            'won'             => phive()->twoDec($total_session_win) // not needed on the localized string, remove?
        );

        return $aRcMsg;
    }

    /**
     * Override buttons on RC dialog
     *
     * @param $user
     * @param string $lang
     * @return array
     */
    public function getRcPopupButtons($user, $lang = 'en')
    {
        return [
            [
                'string' => 'reality-check.label.closeAndResumeGame',
                'action' => 'continue',
                'url'    => ''
            ],
            [
                'string' => 'reality-check.label.responsibleGaming',
                'action' => 'responsibleGaming',
                'url'    => $this->getRespGamingUrl($user, $lang)
            ],
            [
                'string' => 'reality-check.label.leaveGame',
                'action' => 'leaveGame',
                'url'    => phive('Casino')->getLobbyUrl(false, $lang)
            ]
        ];
    }

    /**
     * The time the RC should use as starting point when launching a game
     * For SE we are using the time since the user logged in to the casino
     *
     * @param DBUser $user
     * @return int - seconds
     */
    public function rcElapsedTime($user = null)
    {
        $user = cu($user);

        if (!empty($user)) {
            return $this->getSessionLength($user);
        }
        return false;
    }

    /**
     *   Gets the common parameters used for reality checks
     *   @param $user ->  User
     *   @return array
     */
    public function getRcParams($user = null)
    {
        $user = cu($user);
        if (empty($user)) {
            return [];
        }
        $sessionBetsAndWins = phive('UserHandler')->sumGameSessionsBySessionId($user->getId(), $user->getCurrentSession()['id']);

        return [
            'jurisdiction' => 'SE',
            'rcElapsedTime' => $this->rcElapsedTime($user),
            'rcTotalBet' => $sessionBetsAndWins['bet_amount'],
            'rcTotalWin' => $sessionBetsAndWins['win_amount'],
            'spelpausLink' => $this->getLicSetting('spelpaus_btn_url'),
            'sjalvtestLink' => $this->getGamTestUrl($user),
            'spelgranserLink' => $this->getRespGamingUrl($user)
        ];
    }


    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     */
    public function getBaseGameParams($user = null, $imgType = 'black')
    {
        $res = [
            'self_exclusion' => [
                'url' => $this->getLicSetting('spelpaus_btn_url'),
                'img' => $this->imgUri("spelpaus-small-$imgType.png")
            ],
            'self_assessment' => [
                'url' => $this->getGamTestUrl($user), // This needs to be open in an iframe
                'img' => $this->imgUri("sjalvtest-small-$imgType.png"),
                'overlay_header' => t('gamtest.box.headline')
            ],
            'account_limits' => [
                'url' => $this->getRespGamingUrl($user),
                'img' => $this->imgUri("spelgranser-small-$imgType.png")
            ]
        ];
        if (!empty($user)) {
            $res['elapsed_session_time'] = $this->getSessionLength($user);
        }

        return $res;
    }

    /* For SE all games should display inside an iframe */
    public function forceMobileGamesInIframe()
    {
        if (!empty($username = $this->getLicSetting('test_iframe_user'))) {
            $user = cu();
            if (!empty($user) && in_array($user->getUsername(), $username)) {
                return 'Yes';
            }
        }

        if ($this->getLicSetting('rg-buttons') && $this->getLicSetting('mobile_games_in_iframe')) {
            return 'Yes';
        } else {
            return 'No';
        }
    }

    public function handleGameVersion($session_id, $ins)
    {
        parent::handleGameVersionCommon($session_id, $ins);
    }

    public function disableBoPwdChange()
    {
        return true;
    }

    public function getFirstDepositBonus()
    {
        return phive('Bonuses')->getBonus(phive('Config')->getValue('license-se', 'first-deposit-bonus-id', 0));
    }

    public function onSuccessfulLogin($u_obj)
    {
        // Do not enforce RC limit for privileged users (Ex. admin)
        if (!privileged($u_obj) && empty(rgLimits()->getByTypeUser($u_obj, 'rc'))) {
            rgLimits()->saveLimit($u_obj, 'rc', 'na', 4 * 60);
        }
        $this->rgLimits()->startProgressableTimeLimit($u_obj, 'login');
    }

    /**
     * Invoked after a deposit successful.
     *
     * @param $user
     * @param array|null $args
     */
    public function onSuccessfulDeposit($user, ?array $args)
    {
        lic('addNationalitySetting', [$user], $user);
    }

    public function checkLoginLimit($u_obj)
    {
        return $this->rgLimits()->checkProgressableTimeLimit($u_obj, 'login', true);
    }

    public function onLoggedPageLoad($u_obj)
    {
        $this->rgLimits()->progressResettableTimeLimit($u_obj, 'login', [], null, true);

        if (!$this->checkLoginLimit($u_obj)) {
            phive('UserHandler')->logout('rg_login_limit_reached');
        }
    }

    public function onLogin($u_obj)
    {
        parent::onLogin($u_obj);
        if (!$this->checkLoginLimit($u_obj)) {
            return 'rg_login_limit_reached';
        }

        return true;
    }

    public function getRgIndefiniteLockDays()
    {
        return 365;
    }

    /**
     * We don't support custom affiliate bonus codes
     *
     * @return bool
     */
    public function blockAffiliateBonusCode()
    {
        return true;
    }

    /**
     * @param DBUser $user
     * @return bool
     */
    public function needsNid($user): bool
    {
        if ($user->isTestAccount()) {
            return false;
        }
        return !$user->hasAttr('nid');
    }

    public function validateRegFields()
    {
        return ['email', 'country', 'mobile'];
    }

    /**
     * Called by Licensed/ajax.php - will check if the user has some enforced action to do before play
     * Currently used on click on "Mobile BoS" links to enforce popup from the old site, before going into new site.
     *
     * @param array $post - $_POST is being passed here
     * @return array
     */
    public function ajaxBeforePlay($post = [])
    {
        return ['url' => $this->beforePlay(null, 'mobile', null)];
    }

    /**
     * @see Licensed::beforePlay() +
     * - "change-deposit-before-play": this check will enforce that weekly deposit limit is below a certain threshold
     *                                 before allowing the customer to play. (Ex. deposit week limit <= 5000 SEK)
     *
     *
     * @param DBUser $u_obj
     * @param string $type
     * @param null $game
     * @return mixed|string
     */
    public function beforePlay($u_obj, $type = 'flash', $game = null)
    {
        $url = parent::beforePlay($u_obj, $type, $game);
        if (!empty($url)) {
            return $url;
        }
        $url = $this->handleRgLimitPopupRedirection($u_obj, $type, 'change-deposit-before-play');
        return $url;
    }

    /**
     * RG36: anyone that sets a limit above 10K SEK
     *
     * @param DBUser $u_obj
     * @param array|mixed $rgl
     * @param mixed $limit
     * @return bool
     */
    public function onAddChangeRgLimit($u_obj, $rgl, $limit)
    {
        $trigger = 'RG36';
        $intervals = [
            'day' => "daily",
            'week' => "weekly",
            'month' => "monthly",
        ];

        if ($rgl['type'] != 'deposit') {
            return false;
        }

        // Swedes are not supposed to be able to play with anything else than SEK but anyway, just in case
        $amount = (int)chg($u_obj, 'SEK', $limit, 1);
        $thold = phive('Config')->getValue('RG', "$trigger-threshold", 10000);

        // convert from cents to units because $thold is in units
        $amount = $amount / 100;

        if ($amount >= $thold) {
            $interval = $intervals[$rgl['time_span']] ?? $rgl['time_span'];
            $desc = "Set a {$interval} deposit limit of {$amount} SEK which is higher than {$thold} SEK";

            $res = $this->uh->logTrigger($u_obj, $trigger, $desc);

            if (!empty($res)) {
                phive('Cashier/Arf')->sendEmailAndComment($u_obj, $trigger);
            }

            return true;
        }
        return false;
    }

    public function onAddRgLimit($u_obj, $rgl, $limit)
    {
        return $this->onAddChangeRgLimit($u_obj, $rgl, $limit);
    }

    public function onChangeRgLimit($u_obj, $rgl, $limit, $action)
    {
        if ($action == 'remove') {
            // Nothing to do here atm.
            return false;
        }
        return $this->onAddChangeRgLimit($u_obj, $rgl, $limit);
    }

    /**
     * Handle, in case we have limits with default values, special actions based on what the customer selected.
     * All default values VS something changed.
     *
     * Ex. in case of deposit limit with all default value we will keep active the "undo withdrawals" function,
     *     otherwise it will be disabled and need to be manually enabled by the player in the RG section
     *
     * @param $u_obj
     * @param $type
     * @param $using_defaults
     */
    public function onAddRgLimitWithDefault($u_obj, $type, $using_defaults)
    {
        switch ($type) {
            case 'deposit':
                if ($this->getLicSetting('undo_withdrawals') === true) {
                    $this->rgLimits()->addLimit($u_obj, 'undo_withdrawal_optout', 'na', !((int)$using_defaults));
                }
                break;
            default:
                break;
        }
    }

    /**
     * Prefill some rg values:
     * - optout undo withdrawal
     * - force RC to 4h
     * Verify similarity with external data
     *
     * @param DBUser $u_obj
     * @see Licensed::onRegistrationEnd()
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        if ($this->getLicSetting('undo_withdrawals') === true) {
            $this->rgLimits()->saveLimit($u_obj, 'undo_withdrawal_optout', 'na', 1);
        }

        if (empty(rgLimits()->getByTypeUser($u_obj, 'rc'))) {
            rgLimits()->saveLimit($u_obj, 'rc', 'na', 4 * 60);
        }

        $this->checkSimilarityWithExternalData($u_obj);

        if (empty($u_obj->getSetting('verified-nid'))) {
            $this->createEmptyDocuments($u_obj);
        }
    }

    /**
     * Return the cool off period when making and RG limit more liberal.
     *
     * ATM only "login" is with a special logic
     * cool off will start beginning of next month but with a minimum of 72h from now.
     *
     * @param $modifier
     * @param null $type
     * @param string $iso - iso2 of the user country
     * @return mixed
     */
    public function getRgChangeStamp($modifier, $type = null, $iso = null)
    {
        if ($type == 'login') {
            $first_day_next_month = phive()->hisMod('+1 month', date('Y-m-01'));
            $today_plus_minimum_cooloff = phive()->hisMod('+3 days', phive()->hisNow());
            $diff_in_hours = phive()->subtractTimes($first_day_next_month, $today_plus_minimum_cooloff, 'h');
            return $diff_in_hours >= 0 ? $first_day_next_month : $today_plus_minimum_cooloff;
        }

        return parent::getRgChangeStamp($modifier, $type, $iso);
    }

    /**
     * Modified reset timestamp, on Sweden the weekly deposit limit resets Sunday at 23:59:59
     *
     * @param $time_span
     * @param $type
     * @return false|void
     */
    public function getResetStamp($time_span, $type)
    {
        if ($type == 'deposit' && $time_span == 'week') {
            return date("Y-m-d 23:59:59", strtotime('sunday this week'));
        }
        return false;
    }

    /**
     * We only hide deposit limit
     *
     * @param $type
     * @return bool
     */
    public function hideRgRemoveLimit($type)
    {
        return parent::hideRgRemoveLimit($type) || $type == 'deposit';
    }

    public function showLoginLimit()
    {
        return true;
    }

    /**
     * Documentation: https://testapi.spelpaus.se/documentation
     *
     * Test interface: https://testapi.spelpaus.se/swagger/index.html
     *
     * In Licensed.config.php:
     * $this->setSetting('SE', [
     * 'gamstop' => [
     * 'key'       => 'rxCzrck6ggsJPbb5YGdKLFs1LJmaMWPkKM3YJnl3vX6iakjArIhW6noOOzZH',
     * 'actor_id'  => 20,
     * 'url'       => 'https://testapi.spelpaus.se/api',
     * 'is_active' => true
     * ]
     * ]);
     * Returns: {"isBlocked":true,"requestId":"abc","responseId":"ab46b8c877e94e89b2b65e56ac48b46b"}
     *
     * Local code used to test a blocked NID:
     * $u = cu('devtestse');
     * $u->setNid('180711144006');
     * $is_excluded_externally = lic('hasExternalSelfExclusion', [$u], $u);
     *
     * @param DBUser $user
     * @param string $req_id
     * @return mixed|string
     */
    public function gamstopRequest($user, $req_id = '')
    {
        $nid = $user->getNid();

        $content = [
            'subjectId' => $nid
        ];
        return $this->makeGamstopRequest($content, 'blocking-info', $req_id, $user);
    }

    /**
     * Documentation: https://testapi.spelpaus.se/Documentation/Marketing
     *  The maximum number of "items" in the same question is 10000.
     *
     * Example Request: {"requestId": "myRequestId", "items": [{"itemId": "t1","subjectId": "195201282233" }]}
     * Response: {
     *  "requestId": String,
     *  "allowedItemIds": Array of not blocked itemIds,
     *  "responseId": String
     * }
     * Example Response: {"requestId": "myRequestId","allowedItemIds": ["t1"],"responseId": "xxxx"}
     *
     * @param array $users
     * @param string $req_id
     * @return array|string
     */
    public function bulkGamstopRequest($users, $req_id = '')
    {
        $content = [
            "items" => array_map(function ($user) {
                return [
                    'itemId' => $user['id'],
                    'subjectId' => $user['nid']
                ];
            }, $users)
        ];
        return $this->makeGamstopRequest($content, 'marketing-subjectid', $req_id, null, true, true);
    }

    /**
     * Documentation: https://testapi.spelpaus.se/Documentation/Marketing
     *
     * Example Request: {"requestId": "myRequestId", "subjectId": "195201282233" }
     * Response: {
     *  "isBlocked": Bool,
     *  "requestId": String,
     *  "responseId": String
     * }
     * Example Response: {"requestId": "myRequestId","isBlocked": true,"responseId": "xxxx"}
     *
     * @param DBUser $user
     * @param string $req_id
     * @return array|string
     */
    public function requestMarketingBlockedUser($user, $req_id = '')
    {
        $content = [
            'subjectId' => $user->getNid()
        ];
        return $this->makeGamstopRequest($content, 'marketing-single-subjectid', $req_id, $user, true);
    }

    /**
     * @param $content
     * @param $action
     * @param $req_id
     * @param null|DBUser $user
     * @param boolean $is_marketing
     * @param boolean $is_bulk
     * @return mixed|string
     */
    private function makeGamstopRequest($content, $action, $req_id, $user = null, $is_marketing = false, $is_bulk = false)
    {
        if ($this->getLicSetting('gamstop')['disable_calls'] === true) {
            return 'D';
        }

        $content['requestId'] = $req_id = empty($req_id) ? uniqid() : $req_id;

        $start_time = microtime(true);
        $ss = $this->getLicSetting('gamstop');
        $base_url = $is_marketing ? $ss['marketing_url'] : $ss['url'];

        $tag = $this->ext_exclusion_name;
        $timeout = 10;
        $request = $content;

        if ($is_bulk) {
            $tag .= '-bulk';
            $request = array_merge(['users_count' => count($content['items'])], $content);
            $timeout = 60;
        }

        for ($retries = 0; $retries < 3; $retries++) {
            $res = phive()->post($base_url . "/$action/" . $ss['actor_id'], $content, 'application/json', ["authorization: " . $ss['key']], $tag, 'POST', $timeout);

            $this->logExternal(
                $tag,
                $request,
                $response   = json_decode($res, true),
                $time       = (microtime(true) - $start_time),
                $status     = (empty($res) ? 500 : 200),
                $request_id = $req_id,
                $response_id = $response['responseId'],
                $uid        = empty($user) ? 0 : $user->getId()
            );

            if (!empty($res)) {
                break;
            }
        }

        if (empty($res) || empty($response)) {
            return 'E';
        }

        return $response;
    }

    public function userIsMarketingBlocked($user)
    {
        $user = cu($user);

        if (empty($user)) {
            return true;
        }

        if (empty($user->getNid())) {
            return true;
        }

        $res = $this->requestMarketingBlockedUser($user);

        // Deactivated so no user is blocked
        if ($res === 'D') {
            return false;
        }

        // Error OR we received an unexpected response
        if ($res === 'E' || !isset($res['isBlocked'])) {
            return true;
        }

        return $res['isBlocked'];
    }

    /**
     * @param array $users
     * @return mixed
     */
    public function getMarketingBlockedUsers($users)
    {
        $failed_validations = [];

        foreach ($users as $index => $user) {
            if ($user['country'] !== $this->getIso()) {
                unset($users[$index]);
                continue;
            }
            if (empty($user['nid']) || strlen($user['nid']) != $this->personal_number_length) {
                $failed_validations[] = $user;
                unset($users[$index]);
            }
        }

        $blocked = [$failed_validations];

        phive('Logger')->debug('failed-spelpaus-validation', $failed_validations);

        $chunk_size = !empty($this->getLicSetting('gamstop')['bulk_chunk_size']) ? $this->getLicSetting('gamstop')['bulk_chunk_size'] : 10000;
        foreach (array_chunk($users, $chunk_size) as $users_chunk) {
            $res = $this->bulkGamstopRequest($users_chunk);

            // Deactivated so no user is blocked
            if ($res === 'D') {
                continue;
            }

            // Error OR we received an unexpected response
            if ($res === 'E' || !isset($res['allowedItemIds'])) {
                $blocked[] = $users_chunk;
                continue;
            }

            // push only blocked users
            $blocked[] = array_filter($users_chunk, function ($user) use ($res) {
                return !in_array($user['id'], $res['allowedItemIds']);
            });
        }

        // convert array of arrays to array of users
        $blocked = phive()->flatten($blocked, true);

        // return array of user ids
        return array_map(function ($user) {
            return $user['id'];
        }, $blocked);
    }

    /**
     * @param DBUser $user
     * @return mixed|string
     */
    public function checkGamStop($user)
    {
        $res = $this->gamstopRequest($user);

        // We expect array, if we get string we have an error.
        if (is_string($res)) {
            return $res;
        }

        if (!isset($res['isBlocked'])) {
            return 'E';
        }

        return $res['isBlocked'] ? self::SELF_EXCLUSION_POSITIVE : self::SELF_EXCLUSION_NEGATIVE;
    }

    public function getSelfExclusionExtraInfo()
    {
        return 'exclude.spelpaus.account.info.html';
    }

    /**
     * @return string
     */
    public function getSelfExclusionRecommendation()
    {
        return t('gamble.too.much.description.selfexclude.se');
    }

    /**
     * @param null $user
     * @param string $gamstop_res
     * @return bool
     */
    public function hasExternalSelfExclusion($user = null, $gamstop_res = '')
    {
        return $this->hasExternalSelfExclusionCommon($user, $gamstop_res);
    }

    public function getNidPlaceholder()
    {
        return 'ÅÅÅÅMMDDNNNN';
    }

    public function getPrepopNid($u = null)
    {
        $nid = $this->getNid($u);
        return empty($nid) ? $this->getNidPlaceholder() : $nid;
    }

    // --- Below here is display related stuff, could be moved to separate includes if need be, ie if we start to get a lot of stuff in this file ---

    /**
     * @param bool $translate
     *
     * @return mixed
     */
    public function getRegistrationMessage(bool $translate = true)
    {
        $alias = 'registration.with.verification.method';

        return $translate ? t($alias) : $alias;
    }

    /**
     * @param string $context
     * @param bool $return
     *
     * @return void|string
     */
    public function customLoginInfo($context, bool $return = false)
    {
        $alias = "{$context}.verification.method.instructions.html";
        if ($return) {
            return $alias;
        }

        et($alias);
    }

    public function customLoginTop($context)
    { ?>
        <?php if ($context == 'login'): ?>
            <?php
            $custom_login_top = $this->getCustomLoginTop();
            $intermediary_step_params = $this->getIntermediaryStepParameters("", false);
            ?>
            <div class="lic-mbox-btn lic-mbox-btn-active verification-btn-SE" id="bankid-start-verification">
                <span><?php et("$context.with.verification.method") ?></span>
            </div>
            <script>
                $('#bankid-start-verification').on('click', function() {
                    licFuncs.prepareExternalVerification(<?= json_encode($intermediary_step_params) ?>);
                    licFuncs.startExternalVerification('login');
                });
            </script>
            <div class="clearfix"></div>
        <?php endif ?>
    <?php }

    public function loginSeparator($context)
    { ?>
        <?php if ($context == 'login'): ?>
            <div class="login-separator">
            </div>
            <div class="login-alternative-text">
                <span><?php et('login.alternative.text') ?></span>
            </div>
        <?php endif ?>
    <?php }

    /**
     * @return \Videoslots\User\CustomLoginTop\CustomLoginTopForSE
     */
    public function getCustomLoginTop(): CustomLoginTopForSE
    {
        return new CustomLoginTopForSE(
            'email.and.password',
            'verification.method'
        );
    }

    public function topMobileLogos($lang = null)
    {
        if ($this->getLicSetting('rg-buttons')) {
            return phive()->ob(function () use ($lang) {
                $this->rgLogo($this->getRgLogosMobileType(), 'rg-mobile-top rg-mobile-top-se', $lang);
            });
        }
        return false;
    }

    public function topLogos($type = 'white', $extra_classes = '')
    {
        if ($this->showTopLogos()) {
            return phive()->ob(function () use ($type, $extra_classes) {
                $this->rgLogo($type, 'margin-four-top ' . $extra_classes);
            });
        }
        return false;
    }

    /**
     * @return string
     */
    public function getRgLogosMobileType(): string
    {
        return phive('Pager')->isLanding() ? self::RG_LOGO_TYPE_LANDING :  self::RG_LOGO_TYPE_BLACK;
    }

    public function rgLogo($type = 'white', $extra_classes = '', $lang = null)
    {
        $data = $this->getRgLogoData($type, $lang);

    ?>
        <div class="rg-top__item rg-logo vs-sticky-bar__images <?php echo $extra_classes ?>" id="vs-sticky-bar__images">
            <a href="<?= $data->getSpelpausUrl() ?>"
                target="_blank"
                rel="noopener noreferrer">
                <img src="<?= $data->getSpelpausImage(); ?>"
                    id="vs-sticky-bar-image__spelpaus" class="vs-sticky-bar__image">
            </a>
            <a href="javascript:void(0)"
                onclick="licFuncs.doGamTest('<?= $data->getGamTestUrl() ?>')">
                <img src="<?= $data->getGamTestImage() ?>"
                    id="vs-sticky-bar-image__sjalvtest"
                    class="vs-sticky-bar__image">
            </a>
            <a href="<?= $data->getRespGamingUrl() ?>">
                <img src="<?= $data->getRespGamingImage() ?>"
                    id="vs-sticky-bar-image__spelgranser"
                    class="vs-sticky-bar__image">
            </a>
        </div>
    <?php
    }

    /**
     * @return bool
     */
    public function hasRgLogo(): bool
    {
        return true;
    }

    /**
     * @param string $type
     * @param string|null $lang
     *
     * @return \Videoslots\User\RgData\RgLogoForSE
     */
    public function getRgLogoData(string $type, string $lang = null): RgLogoForSE
    {
        $user = cu();

        return new RgLogoForSE(
            $this->getLicSetting('spelpaus_btn_url'),
            $this->imgUri("spelpaus-small-$type.png"),
            $this->getGamTestUrl($user, $lang),
            $this->imgUri("sjalvtest-small-$type.png"),
            $this->getRespGamingUrl($user, $lang),
            $this->imgUri("spelgranser-small-$type.png")
        );
    }

    /**
     * @return \Videoslots\RgLimits\Builders\Locks\LockInterface
     */
    public function createLockBuilder(): LockInterface
    {
        return new \Videoslots\RgLimits\Builders\Locks\SE();
    }

    /**
     * @deprecated
     */
    function rgLockSection($box)
    { ?>
        <?php dbInput('lock-hours', '', 'text', 'input-normal') ?>
        <br clear="all" />
        <br clear="all" />
        <strong>
            <?php et("or") ?>
        </strong>
        <br clear="all" />
        <br clear="all" />
        <input id="se-indefinite" type="checkbox" name="indefinitely" value="" />
        <?php et("lock.indefinitely") ?>
<?php
        return true;
    }

    public function validateExtVerAndGetNid($data = null)
    {
        $curReqId = $data['cur_req_id'] ?? '';
        $ext_cached_res = $this->getCachedExtVerResult($curReqId);
        if (empty($ext_cached_res)) {
            // Should not happen, means that the player went for a holiday or cleared his cookies in the middle of step 1.
            phive('Logger')->getLogger('bankid')->error('reg-error-missing-ext-data', [$data, $_SESSION]);
            return null; // it's safe to just return null because functions using this are checking for empty()
        }

        return phive()->rmNonNums($ext_cached_res['result']['lookup_res']['nid']);
    }

    /**
     * @param DBUser $user
     * @return mixed
     */
    function lookupNid($user)
    {
        return $this->getDataFromNationalId($user->getCountry(), $user->getNid());
    }

    /**
     * Returns the BE dependencies in order to start the BankID verification
     * @param string $sessionId
     * @param string $isApi
     *
     * @return array
     */
    public function getIntermediaryStepParameters(string $sessionId = "", bool $isApi)
    {
        $alias = "start.extverify.app";
        $sessionId = $sessionId == "" ? null : $sessionId; // hotfix - change default value to null

        return [
            "socket_url" => phive('UserHandler')->wsUrl('nid_verification', true, [], '', $sessionId),
            "message" => $isApi ? $alias : t($alias),
        ];
    }

    public function getItermediaryStepMethod(string $context): string
    {
        if ($context == 'registration') {
            //if one click registration
            if ($this->passedExtVerification()) {
                if ($this->oneStepRegistrationEnabled()) {
                    return 'licFuncs.showExternalIntermediaryStep1CCValidation';
                } elseif (isBankIdMode()) {
                    return 'licFuncs.showBankIdAccountVerificationPopup';
                }
            } else {
                return 'licFuncs.showExternalIntermediaryStep1Registration';
            }
        }

        return 'showLoginBox';
    }

    /**
     * Detect if external verification was already completed
     *
     * @param array $data
     * @return bool
     */
    public function passedExtVerification($data = [])
    {
        $curReqIdKey = 'cur_req_id';
        // in case of API call, curReqId can be passed through request params
        $r = phMgetArr($_SESSION[$curReqIdKey] . '.result');
        if (empty($r)) {
            $r = phMgetArr($data[$curReqIdKey] . '.result');
        }
        $passed = !empty($r) && $r['success'] !== false;

        return !empty($data[$curReqIdKey]) || $passed;
    }

    /**
     * Return the correct suffix in new registration
     *
     * @param $string
     * @param bool $translate
     *
     * @return mixed
     */
    public function getSuffixContext($string, bool $translate = true)
    {
        return $translate ? t($string) : $string;
    }

    /**
     * For SE we are only interested on NID matching customers so we filter as well by country.
     *
     * @param DBUser $user
     * @param string $remote
     *
     * @return
     */
    public function matchInBrand($user, $remote)
    {
        return toRemote($remote, 'matchUser', [
            $user->getId(),
            'ByAttribute',
            ['attribute' => 'nid', 'user_data' => ud($user), 'jurisdiction' => $user->getJurisdiction()]
        ], 3);
    }

    /**
     * If this jurisdiction has deposit limit madatory before we bet
     *
     * @return bool
     */
    public function isDepositLimitMandatorySportsbook()
    {
        return true;
    }

    /**
     * Alias used for the RG Lock Account section
     * - Specific for SE
     *
     * @return array
     */
    public function getLockAccountMessages(): array
    {
        $iso = $this->getIso();

        $messages = parent::getLockAccountMessages();

        $messages['description'] = "lock.account{$iso}.info.html";

        return $messages;
    }

    /**
     * Make an external verification
     * user_data must match zingsec data
     *
     * @param array $user_data
     * @param array $errors
     *
     * @return array
     */
    public function extraValidationForStep2(array $user_data, array $errors): array
    {
        $validation_errors = [];
        $user = cu($user_data, 'user_id');
        $lookup_data = [];

        if ($user->hasSetting('nid_data')) {
            $lookup_data = json_decode($user->getSetting('nid_data'), true);
        } elseif (!empty($user->getNid())) {
            // the user has registered with nid so we can get the data for registration step 2
            $lookup_data = lic('lookupNid', [$user]);
            if (!empty($lookup_data)) {
                $lookup_data = $lookup_data->getResponseData();
            }
        }

        $external_validation_error = 'errors.user.external_validation';
        if (!$lookup_data) {
            return [
                'generic' => $external_validation_error
            ];
        }

        $zs_data = $this->getPersonLookupHandler()->mapLookupData($lookup_data);

        //prefilling missed data
        if ($this->oneStepRegistrationEnabled() || isBankIdMode()) {
            $zs_data['nationality'] = self::DEFAULT_NATIONALITY;
            $user->setSetting('nationality_update_required', 1);
        }

        foreach ($zs_data as $key => $zs_value) {
            if (array_key_exists($key, $user_data) && $zs_value != $user_data[$key]) {
                $validation_errors[$key] = $external_validation_error;
            }
        }

        return $errors + $validation_errors;
    }

    /**
     * Registration step 2 setup
     *
     * @return array
     */
    public function registrationStep2Fields(): array
    {
        if (isPNP()) {
            return parent::registrationStep2Fields();
        }
        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'bonus_code', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'email_code', 'eighteen']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'preferred_lang', 'bonus_code'],
            'right' => ['birthdate', 'currency', 'sex', 'email_code', 'eighteen']
        ];
    }

    /**
     * Makes possible to disable prefilling of a Nationality Value
     * @return string
     */
    public function getNationalityValue(): string
    {
        if ($this->oneStepRegistrationEnabled() || isBankIdMode()) {
            return self::DEFAULT_NATIONALITY;
        }

        return '';
    }

    /**
     * @param string|null $key
     * @return bool
     */
    public function shouldCheckCheckbox(?string $key): bool
    {
        $oneStep = $this->oneStepRegistrationEnabled();
        if (($oneStep || isBankIdMode()) && $key === 'eighteen') {
            return true;
        }

        return false;
    }

    /**
     * Check self-exclusion restrictions of a user on another brand by NID
     *
     * @param DBUser $user
     *
     * @return bool
     */
    public function checkRemoteSelfExclusionByNid(DBUser $user): bool
    {
        if (!$this->getLicSetting('check_remote_self_exclusion_by_nid')) {
            return false;
        }

        $remote = $this->getLicSetting('check_self_exclusion_remote_brand');
        if (empty($remote)) {
            return false;
        }

        $nid = $user->getNid();
        if (!$nid) {
            return false;
        }

        $response = toRemote($remote, 'remoteCheckSelfExclusionByNid', [$nid, $user->getCountry()], 2);
        if (empty($response)) {
            phive('UserHandler')->logAction(
                $user,
                "Remote self exclusion check failed due to no response from {$remote}",
                "remote-self-exclusion-by-nid"
            );
            return false;
        }

        $settings = [];
        foreach ($response['result']['settings'] as $setting => $value) {
            $settings[] = $setting . ': ' . $value;
        }
        $settings = implode('; ', $settings);
        phive('UserHandler')->logAction(
            $user,
            "Remote self exclusion from {$remote} - {$response['result']['excluded']}. Settings: $settings",
            "remote-self-exclusion-by-nid"
        );

        return $response['result']['excluded'] ?? false;
    }

    /**
     * Check for external self exclusions
     *
     * @return void
     */
    public function runExternalSelfExclusionChecks(): void
    {
        if ($this->getLicSetting('gamstop')['is_active'] !== true) {
            return;
        }

        $query = "
            SELECT
                u.*,
                us.user_id,
                IFNULL(TIMESTAMPDIFF(HOUR, us.value, NOW()),0) AS last_spelpaus_diff
            FROM
                users u
                LEFT JOIN users_settings us ON us.user_id = u.id AND setting = 'last-spelpaus-check'
            WHERE
                u.country = 'SE'
            GROUP BY us.user_id
            HAVING last_spelpaus_diff >= 23
        ";
        $users = phive('SQL')->shs()->loadArray($query);

        foreach (array_chunk($users, $max_number_of_users_per_list = 1000) as $list) {
            try {
                $blocked_user_ids = $this->getMarketingBlockedUsers($list);
                $this->updateGamstopStatusFromBulkRequest($list, $blocked_user_ids);
            } catch (\Exception $e) {
                error_log($e->getCode() . ':' . $e->getMessage());
            }
        }
    }

    /**
     * Update users setting with the result of bulkGamstop
     * - last-spelpaus-check: for daily check
     * - cur-spelpaus: for self-exclusion status
     *
     * @param array $list - list of users
     * @param array $res - bulk gamstop result contains an array of blocked user ids
     * @return void
     */
    public function updateGamstopStatusFromBulkRequest(array $list, array $res): void
    {
        foreach ($list as $elem) {
            $user = cu($elem['user_id']);

            if (in_array($elem['user_id'], $res)) {
                $gamstop_res = 'Y';
                $ext_excluded = $this->hasExternalSelfExclusion($user, $gamstop_res);
                if ($ext_excluded !== false) {
                    phive('UserHandler')->logoutUser($elem['user_id'], 'external self exclusion');
                }
            }
        }
    }

    /**
     * Triggered by cron command
     *
     * @param string $date
     *
     * @return void
     * @throws Exception
     */
    public function onEveryday(string $date = ''): void
    {
        $this->runExternalSelfExclusionChecks();
    }

    /**
     * show sponsorship logos
     *
     * @return bool
     */
    public function getSponsorshipLogos(): bool
    {
        return (bool) $this->getLicSetting('hide_sponsorship_logos');
    }
}
