<?php

use ClosedLoop\ClosedLoopHelper;
use Videoslots\HistoryMessages\BetHistoryMessage;
use Videoslots\HistoryMessages\BetsRollbackHistoryMessage;
use Videoslots\HistoryMessages\BonusCancellationHistoryMessage;
use Videoslots\HistoryMessages\BonusHistoryMessage;
use Videoslots\HistoryMessages\CashTransactionHistoryMessage;
use Videoslots\HistoryMessages\EndSessionHistoryMessage;
use Videoslots\HistoryMessages\TournamentCashTransactionHistoryMessage;
use Videoslots\HistoryMessages\WinHistoryMessage;
use Videoslots\HistoryMessages\WinsRollbackHistoryMessage;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use DBUserHandler\Session\WinlossBalance;

require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/../Raker/Parser.php';
require_once __DIR__ . '/../Former/Validator.php';
require_once __DIR__ . '/traits/ExternalGameSessionTrait.php';
require_once __DIR__ . '/../../traits/ObfuscateTrait.php';

class Casino extends PhModule
{
    use ExternalGameSessionTrait;
    use \ObfuscateTrait;

    private const SPORTSBOOK_PRODUCT = 'sportsbook';
    // Common variables defined in the code
    /**
     * @var DBUser|null
     */
    protected $user = null; // Current user
    public $uid = null; // Current user "id"
    protected $t_eid = null; // Tournament entry_id
    protected $t_entry = null; // Tournament entry

    // These setting will be used to create the launchUrl
    protected $launch_url = null; // The launch url that will be used by the game (setLaunchUrl parse it from the config, getLaunchUrl add the querystring parameters)
    protected $iso = null; // MT / SE / GB ...
    protected $fallback_iso = 'MT'; // default value to fallback to, if nothing is specified for the current iso in the configs
    protected $platform = null; // desktop / mobile
    protected $demo_or_real = null; // demo / real

    protected $standard_txn_format = ['NET_', 'TXN'];
    protected $standard_txn_format_with_jur = ['NET_', 'JUR_', 'TXN'];

    // Limit error variables
    protected $limit_reached = false;

    // populated in lgaMobileBalance if lgaLimitsCheck != 'OK', can be used to return a custom error based on the reached limit (Ex. RC)
    protected $limit_msg = '';

    // Inactive game: used to set a custom error when a game is not active
    protected bool $inactive_game = false;

    // used in array_filter to filter and return only the params required, needs to be overridden for GP specific params
    protected $rc_keys_required = ['rcLobbyUrl', 'rcHistory', 'rcInterval','jurisdiction', 'rcElaspedTime', 'rcTotalBet', 'rcTotalWin'];

    protected bool $send_winnings_to_bonus_balance = false;

    /**
     * Deposit id, after insert into DB
     * TODO review current implementation of this variable "add_deposit + TestCasinoCashier" /Paolo
     *
     * @var null
     */
    public $did = null;

    /**
     * Will contain the current deposit amount converted in EURO.
     * This is used only for the tracking we do on google platforms (GTM with GA + E-commerce)
     *
     * @var null
     */
    private $dep_in_euro = null;

    /**
     * @var SQL $db
     */
    protected $db = null;

    /**
     * @var Logger $logger
     */
    protected Logger $logger;

    protected string $logger_name = 'game_providers';
    protected int $old_balance = -1;

    function __construct(){
        $this->db         = phive('SQL');
        $this->exp_time	  = 20000;
        $this->device_map = phive()->getDeviceMap(true);
        $this->chg_calls  = 0;
        $this->logger     = phive('Logger')->getLogger($this->logger_name);
        $this->logger->addIntrospectionProcessor();
    }

    /**
     * @param $tag
     * @return bool
     */
    public function isSlotGameType($tag)
    {
        return in_array($tag, $this->getSetting('slot_game_types', ['videoslots', 'slots', 'casino-playtech']));
    }

    /**
     * Used in admin2
     * @return mixed|null
     */
    public function getSlotsType()
    {
        return $this->getSetting('slot_game_types', ['videoslots', 'slots', 'casino-playtech']);
    }
    /**
     * @param $tag
     * @return bool
     */
    public function isBoosterGameType($tag)
    {
        return in_array($tag, $this->getSetting('booster_game_types', ['videoslots', 'slots', 'casino-playtech']));
    }

    /**
     * @param $tag
     * @return bool
     */
    public function isXpGameType($tag)
    {
        return in_array($tag, $this->getSetting('xp_game_types', ['videoslots', 'slots', 'casino-playtech']));
    }

    /**
     * @param $tag
     * @return bool
     */
    public function isWheelContributionGameType($tag)
    {
        return in_array($tag, $this->getSetting('wheel_contribution_game_types', ['videoslots', 'slots', 'casino-playtech']));
    }

    /**
     * @param $tag
     * @return bool
     */
    public function isWheelOnBetType($tag)
    {
        return in_array($tag, $this->getSetting('wheel_on_bet_game_types', ['videoslots', 'slots', 'casino-playtech']));
    }

    /**
     * @param $tag
     * @return bool
     */
    public function isClashGameType($tag)
    {
        return in_array($tag, $this->getSetting('clash_game_types', ['videoslots', 'slots', 'casino-playtech']));
    }

    function dumpTst($tag, $content, $uid = 0){
        if($this->getSetting('test') === true || $this->getSetting('debug') === true)
            phive()->dumpTbl($tag, $content, $uid);
    }

    function getNetworkName($module){
        $module = strtolower($module);
        if($module == 'microgaming'){
            // NOTE, this will have to be defined as QuickFire in the gps_via_gpr setting, not
            // all lower case like the rest.
            $module = 'QuickFire';
        }
        if(in_array($module, $this->getSetting('gps_via_gpr'))){
            $module = 'gpr';
        }
        return ucfirst($module);
    }

    function getLegacyNetworkName($module){
        $module = strtolower($module);
        if($module == 'microgaming'){
            // NOTE, this will have to be defined as QuickFire in the gps_via_gpr setting, not
            // all lower case like the rest.
            $module = 'QuickFire';
        }

        return ucfirst($module);
    }

    function getNetworkModule($game){
        $obj = phive($this->getNetworkName(trim($game['network'])));
        if(method_exists($obj, 'init')){
            $obj->init();
        }
        return $obj;
    }


    function getGpFromGref($gref, $as_obj = true){
        $game = phive("MicroGames")->getByGameRef($gref);
        $module = $this->getNetworkModule($game);
        if($as_obj){
            return $module;
        }
        return $module;
    }

    function activateFreeSpin(&$entry, $na, $bonus, $ext_id) {
        $entry['status'] = 'approved';
    }

    /**
    *  GET THE BASE PATH for mobile/desktop
    *  @param string $lang - optional for cases when we need to enforce it (like api calls)
    *  @param string $platform (desktop | mobile) optional for cases when we must enforce it (like api calls)
    *  @param bool $has_appended_path - if true it means some content will be appended and we want to prevent double slash on mobile
    *  @return string The base path for mobile/desktop and lang
    */
    public function getBasePath($lang = null, $platform = null, $has_appended_path = false)
    {
        $trailing_slash = '';
        if (!empty($has_appended_path)) {
            $trailing_slash = (phive()->isMobile() || $platform == 'mobile' ? '' : '/');
        }

        if (empty($platform)) {
            $prefix = !phive()->isMobile() ? '' : '/mobile/';
        } else {
            $prefix = $platform == 'desktop' ? '' : '/mobile/';
        }

        // We are enforcing the getSiteUrl() inside "llink" otherwise if no $prefix is passed it will fallback to the current page using getRawPathNoTrailing()
        return llink(phive()->getSiteUrl() . $prefix, $lang) . $trailing_slash;
    }

    /**
     * Generates the lobby URL with optional encoding, language, and platform.
     *
    *  @param bool $url_encode      if the returned url should be url encoded (like api calls)
    *  @param string|null $lang     optional for cases when we need to enforce it (like api calls)
    *  @param string|null $platform optional for cases when we must enforce it (desktop | mobile)
     *
    *  @return string               site url for desktop or mobile
    */
    public function getLobbyUrl(bool $url_encode = true, string $lang = null, string $platform = null): string
    {
        /** @var URL $p_url */
        $p_url = phive('Http/URL');
        $uri = $p_url->prependMobileDirPart($this->getBasePath($lang, $platform));
        $lobby_url =  $url_encode ? urlencode($uri) : $uri;

        // Append mobile-specific query if accessed from mobile app
        if (phive()->isMobileApp()) {
            $separator = (str_contains($lobby_url, '?')) ? '&' : '?';
            $lobby_url .= $separator . 'go_to_home_mobile=1';
        }

        return $lobby_url;
    }

    /**
     * Adds to the lobby URL the parameter that will force redirection to top frame
     *
     * @param bool $url_encode
     * @param string $lang
     * @param string $platform
     * @param string $extra_parameters
     * @return string site url for desktop or mobile with redirection parameter
     * @see getHomeRedirectInIframe
     *
     */
    public function getLobbyUrlForGameIframe($url_encode = true, $lang = null, $platform = null, $extra_parameters = null)
    {
        $params = ['go_to_main_website' => true];
        if (!empty($extra_parameters)) {
            $params = array_merge($params, $extra_parameters);
        }

        $lobby_url = $this->getLobbyUrl($url_encode, $lang, $platform);
        $separator = (str_contains($lobby_url, '?')) ? '&' : '?';

        return $lobby_url . $separator . http_build_query($params);
    }

    /**
    *  URL of the registration page
    *  @param bool urlEncode (bool) - if the returned url should be url encoded
    *  @param string lang - optional for cases when we need to enforce it (like api calls)
    *  @param string platform (desktop | mobile) optional for cases when we must enforce it (like api calls)
    *  @return string registration url
    */
    public function getRegistrationUrl($url_encode = true, $lang = null, $platform = null)
    {
        if (empty($platform)) {
            $platform = !phive()->isMobile() ? 'desktop' : 'mobile';
        }

        if($platform == 'mobile') {
            $url = $this->getBasePath($lang, $platform) . 'register/';
        } else {
            $url = $this->getBasePath($lang, $platform) . '?signup=true';
        }
        return $url_encode ? urlencode($url) : $url;
    }

    public function getLoginUrl($url_encode = true, $lang = null, $platform = null) {
        if (empty($platform)) {
            $platform = !phive()->isMobile() ? 'desktop' : 'mobile';
        }

        if($platform == 'mobile') {
            $url = $this->getBasePath($lang, $platform) . 'login/';
        } else {
            $url = $this->getBasePath($lang, $platform) . '?show_login=true';
        }
        return $url_encode ? urlencode($url) : $url;
    }

    /**
    *  URL of the deposit page (currently just the account page as we can't fire a popup)
    *  @param bool urlEncode - if the returned url should be url encoded
    *  @param string lang - optional for cases when we need to enforce it (like api calls)
    *  @param string platform (desktop | mobile) optional for cases when we must enforce it (like api calls)
    *  @return string Deposit url
    */
    public function getCashierUrl($url_encode = true, $lang = null, $platform = null)
    {
        $url = $this->getBasePath($lang, $platform, true) . 'cashier/deposit/';
        return $url_encode ? urlencode($url) : $url;
    }

    /**
     * URL of the users game history
     * @param bool urlEncode (bool) - if the returned url should be url encoded
     * @return string Game history url
     *
     * TODO @Antonio, @Alex, @Johnathan - replace in the GP code the current "manual implementation" to use this function. /Paolo
     * Ex.
     * phive()->getSiteUrl() . "/account/" . $user['id'] . "/game-history/";
     * becomes
     * phive('Casino')->getHistoryUrl(false, $user);
     */
    public function getHistoryUrl($url_encode = true, $user = null, $lang = null)
    {
        $uid = uid($user);
        if (empty($uid)) {
            return $this->getLobbyUrl($url_encode);
        } else {
            $url = phive('UserHandler')->getUserAccountUrl('game-history', $lang, false, $uid);
            return $url_encode ? urlencode($url) : $url;
        }
    }

    /**
     * This function return the URL for the box that needs to be loaded inside the iframe overlay in the new game mode.
     *
     * @param $page - Available pages defined in ajaxBoxesForMobileGame
     * @param string $extra_qs_params
     * @return string
     */
    public function getAjaxBoxInIframeUrl($page, $extra_qs_params = ''): string
    {
        $path = [
            'deposit' => llink('/mobile/deposit/')
        ][$page];

        if (empty($path)) {
            $path = '/phive/modules/BoxHandler/html/ajaxBoxesForMobileGame.php';
            $lang = cLang();
            $extra_qs_params = "load_box={$page}&lang={$lang}&{$extra_qs_params}";
        }

        return phive()->getSiteUrl() . $path . '?' . $extra_qs_params;
    }

    /**
     * If the GP doesn't provide us a way to enforce the redirection on the parent level (our site) from the iframe
     * we can achieve the same result wrapping the provided return URL into this JS.
     * Be sure to test this properly cause if the GP do some encoding on the string it will not work.
     *
     * @param $url
     * @return string
     * @deprecated use getLobbyUrlForGameIframe
     */
    public function wrapUrlInJsForRedirect($url)
    {
        return "javascript:top.location.href='" . $url . "'";
    }

    function getRcCountries(){
        return rgLimits()->getRcCountries();
    }

    /**
     * Gets the common parameters used for reality checks
     * If user is not logged in it returns only the rcLobbyUrl (usually called exitUrl)
     *
     * @param $user ->  DBUser
     * @param $encode ->  if the history url need to be encoded
     * @return array
     */
    public function getRcParamsCommon($user, $encode = true)
    {
        $rg = rgLimits();
        $rcInterval = $rg->getRcLimit($user)['cur_lim'];

        if ($rcInterval) {
            return [
                'rcInterval' => $rcInterval,
                'rcHistoryUrl' => $this->getHistoryUrl($encode, $user),
                'rcLobbyUrl' => $this->getLobbyUrl($encode)
            ];
        }
        return [];
    }

    /**
     * Get Value of reality checks interval
     * If User comes from $aAllowedCountries array, this method must return
     * a reality checks interval value (INTEGER).
     * Method return an INTEGER (interval value) or boolean false.
     * 3 possible values for reality check interval with different priorities.
     * 1-user settings from responsible gaming section or setting popup (just shown only the first time)
     * 2-rcperiod - default value set in database
     * 3-hardcoded 60 minutes (default value)
     *
     * @param null $uid
     * @param string|null $game_ext_name
     * @param int $default
     * @return bool
     */
    function startAndGetRealityInterval($uid = null, ?string $game_ext_name = null, int $default = 60){
        $u_obj        = cu($uid);

        if(empty($u_obj)){
            return false;
        }

        $rg           = rgLimits();
        $rc_countries = $rg->getRcCountries();

        if (in_array($u_obj->getCountry(), $rc_countries)) {
            $rc_limit = $rg->getRcLimit($u_obj);
            $intv = $rc_limit['cur_lim'];
            if(empty($intv)){
                $rc_configs = lic('getRcConfigs');

                // We must ALWAYS return something for people from RC countries
                $default = empty($default) ? 60 : $default;
                $intv    = !empty($rc_configs['rc_default_interval']) ? $rc_configs['rc_default_interval'] : $default;
            } else {
                // We start RC
                $rg->startRc($u_obj, $rc_limit);
            }

            // SET Current game start time to calculate game duration for RC
            if ($game_ext_name) {
                phMsetShard("cur-game-start-time-{$game_ext_name}", time(), $u_obj->getId());
            }

            return $intv;
        }

        return false;
    }

    function setParams($amount, $id, $tr_id){
        $this->params 			= array();
        $this->params['betreferencenum'] 	= $id;
        $this->params['winreferencenum'] 	= $id;
        $this->params['transactionid'] 	= $tr_id;
        $this->params['betamount']		= $amount;
        $this->params['winamount']		= $amount;
        $this->params['refundamount']	= $amount;
    }

    /**
     * Here we are getting the bonus balance by reference of the game_id and the user_id using an old method where
     * we're extracting them from the tokens as strings.
     *
     * @return mixed
     */
    protected function getBonusBalance()
    {
        return phive('Bonuses')->getBalanceByRef($this->new_token['game_ref'], $this->token['user_id']);
    }

    /**
     * Function used in child classes to override the FS bonus config, by default is disabled so we return false
     *
     * @param $user_id
     * @param $bonus
     * @return false
     */
    public function fsBonusOverride($user_id, $bonus)
    {
        return false;
    }

    /**
     * We're using a combo of params and properties (strings) inserted to call the lgaMobileBalance which will do a
     * couple of things, namely:
     * 1. check the limits of the player and return a balance 0 if reached
     * 2. check if the game is active otherwise will return a balance of 0
     * 3. return the total balance of the player of all conditions have been met
     * 4. Return External Game Session balance if we are in a country supporting it
     *
     * @param $user array
     * @param $balance int
     * @param $bonus_balances int
     * @return int
     */
    protected function getTotalBalance($user, $balance, $bonus_balances)
    {
        $balance = $this->lgaMobileBalance($user, $this->new_token['game_ref'], $balance + $bonus_balances, $this->token['device_type']);
        if (!empty($balance) && $this->hasSessionBalance()) {
            $balance = $this->getSessionBalance($user);
        }

        return $balance;
    }

    function getBalance($as_string = true, $from_cache = true){
        $user 		= $this->getUsr();
        if(is_string($user))
            return $user;

        if(!empty($this->t_entry))
            return (int)$this->tEntryBalance();

        if(!$from_cache)
            $user = ud($user['id']);

        if ($this->hasSessionBalance()) {
            return $this->getSessionBalance($user);
        }

        $balance = $user['cash_balance'];

        $bonus_balances = $this->getBonusBalance();

        $total_balance = $this->getTotalBalance($user, $balance, $bonus_balances);

        if(!$as_string)
            return (int)$total_balance;

        return $this->buildResponse(array('Balance' => $total_balance));
    }


    function getPlayUid($uid){
        if(!empty($this->t_eid))
            return "{$uid}e{$this->t_eid}";
        return $uid;
    }

    function registerUser($uid = '', $t_eid = '', $test = false){
        return false;
    }

    // Battle, this is where we assign the entry id and fetches the Battle entry.
    function getUsrId($uid){
        if (!is_numeric($uid)) {
            return $uid;
        }
        $tmp = explode('e', (string)$uid);
        if(empty($this->t_eid))
            $this->t_eid = $tmp[1];
        if(!empty($this->t_eid)) {
            $this->t_entry = phive('Tournament')->entryById($this->t_eid, $tmp[0]);
            $GLOBALS['t_eid'] = $this->t_eid;
        }

        return $tmp[0];
    }

    // Battle, in case we need to re-create the user id as it was first being sent when the Battle play started.
    function mkUsrId($uid, $eid = ''){
        if(!empty($eid))
            return "{$uid}e{$eid}";
        if(!empty($this->t_eid))
            return "{$uid}e{$this->t_eid}";
        return $uid;
    }

    /**
     * TODO this needs to be get rid of as this was introduced only for certain GPs and is kind of duplicated from isTournamentMode
     *
     * @param $uid
     * @return bool
     */
    public function isTournament($uid){
        return strpos($uid, 'e') === false ? false : true;
    }

    /**
     * Do we treat the current GP request in the tournament mode or not
     * @return bool
     */
    public function isTournamentMode()
    {
        return !empty($this->t_entry);
    }

    function getEid($uid){
        $tmp = explode('e', $uid);
        return $tmp[1];
    }

    function getMobileDeviceInfo(){
        return array('html5', 1);
    }

    function getDeviceNum($str){
        return $this->device_map[$str];
    }

    function getDeviceStr($num){
        $strs = array_keys($this->device_map);
        return $strs[$num];
    }

    function uuid(){
        return phive()->uuid();
    }

    // Battle, used to set the Battle info.
    function setTournament($uid){
        if(empty($this->t_eid)){
            $this->t_eid = $this->token['t_eid'];
            if(!empty($this->t_eid))
                $this->t_entry = phive('Tournament')->entryById($this->t_eid, $uid);
        }
    }

    /**
     * protected class variable access
     * @return array|null
     */
    public function getTournamentEntry()
    {
        return $this->t_entry;
    }

    function getUsr(&$req = null){
        $user = cu($this->getUsrId($this->token['user_id']));
        $this->setTournament($user->getId());
        if(!is_object($user))
            return $this->buildError($this->method, 'nouser', 0);
        else
            $this->user = $user;
        $GLOBALS['mg_username'] = $user->data['username'];
        return $user->data;
    }

    /**
     * Will return the correct table suffix depending on the gameplay scenario:
     * - Normal game ""
     * - Tournament "_mp"
     * @return string
     */
    private function getTableSuffix()
    {
        return !empty($this->t_entry) ? '_mp' : '';
    }

    /**
     * !! BEWARE - This is not getUsrId, that function will extract the uid from a string like "{user_id}e{entry_id}" !!
     *
     * Will return the user_id from the class properties depending on the gameplay scenario:
     * - Normal game $this->uid (Except if $uid is passed as param)
     * - Tournament $this->t_entry['user_id']
     *
     * @param $uid - OPT - used to force a specific $uid in normal game play ONLY
     * @return mixed
     */
    private function getUserId($uid = '')
    {
        if(!empty($this->t_entry)) {
            return $this->t_entry['user_id'];
        }

        if (empty($uid)) {
            return $this->uid;
        }

        return $uid;
    }

    function getWinOpFee($amount, $cur_game, $bonus_bet){
        if($bonus_bet == 3)
            return 0;
        return $amount * $cur_game['op_fee'];
    }

    function insertWin($user, $cur_game, $balance, $tr_id, $amount, $bonus_bet, $ext_id, $award_type, $stamp = '', $bonus_bet_type_from_bets = null){
        if(empty($bonus_bet) && empty($user['cash_balance'])) {
            if (is_null($bonus_bet_type_from_bets)) {
                $bonus_bet = 1;
            } else {
                // Override the value so it matches from Bets table
                $bonus_bet = (int)$bonus_bet_type_from_bets;
            }
        }

        $device_type = $this->device_map[strtolower($cur_game['device_type'])];

        $tbl         = empty($this->t_entry) ? 'wins' : 'wins_mp';
        $currency    = empty($this->t_entry) ? $user['currency'] : phive('Tournament')->curIso();

        $user_id = (int) $user['id'];
        $get_session_params =[
            'user_id' => $user_id,
            'game_ref' => $cur_game['ext_game_name'],
            'device_type' => $device_type,
            'balance' => empty($balance) ? $user['cash_balance'] : $balance,
        ];
        $game_session_id = $this->getGsess($get_session_params, $user)['id'];

        $ins = array(
            'balance'      => empty($balance) ? $user['cash_balance'] : $balance,
            'trans_id'     => $tr_id,
            'amount'       => $amount,
            'game_ref'     => $cur_game['ext_game_name'],
            'mg_id'        => $ext_id,
            'award_type'   => $award_type,
            'user_id'      => $user_id,
            'bonus_bet'    => $bonus_bet,
            'op_fee'       => $this->getWinOpFee($amount, $cur_game, $bonus_bet),
            'currency'     => $currency,
            'device_type'  => $device_type
        );

        if(empty($device_type))
            $device_type = 0;

        if(!empty($stamp))
            $ins['created_at'] = $stamp;

        if(!empty($this->t_entry)){
            $ins['t_id'] = $this->t_entry['t_id'];
            $ins['e_id'] = $this->t_entry['id'];
        }

        $related_bet = $this->getLatestBetBySessionID($game_session_id, $user_id, $tr_id, $cur_game['network']);
        $related_bet_creation = $related_bet['created_at'] ?? null;

        if (!empty($related_bet_creation)) {
            $related_bonus_entry = phive('Bonuses')->getLatestCashableForfeitedBonus($user_id, $related_bet_creation);
                
            if (!empty($related_bonus_entry)) {
                if ($related_bonus_entry['status'] === 'failed') {   
                    phive('Bonuses')->reactivateBonusEntry($related_bonus_entry['id'], true);
                } 
    
                $ins['bonus_bet'] = 1;
                $this->send_winnings_to_bonus_balance = true;
            }
        }

        $ins['win_id'] = $this->win_id = $res = phive('SQL')->sh($user['id'], '', $tbl)->insertArray($tbl, $ins);

        // If win wasn't successfully inserted we abort, GP will retry or roll the win back,
        // in either case, the below should not execute.
        if($res === false){
            phive()->dumpTbl('failed-win', $ins, $user['id']);
            $this->logger->error("failed-win",
                [
                    'action' => 'win',
                    'data' => $ins,
                    'user' => $user['id'],
                ]
            );
            return false;
        }
        $user_game_session_wins =[
            "win_id" => $this->win_id,
            "session_id" => $game_session_id,
            "bet_id" => $related_bet["bet_id"] ?? 0
        ];
        phive('SQL')->sh($user['id'])->insertArray("user_game_session_wins", $user_game_session_wins);
        if (empty($this->t_entry)) {
            realtimeStats()->onWin($user['id'], $ins);
        }

        phive()->pexec('Casino', 'handleWin', [$ins, $user, $this->t_entry, $cur_game, phive('Bonuses')->entries, $bonus_bet, $this->won_already, $this->session_entry], 500, $user['id']);

        $this->won_already = true;
        $this->is_booster_win_game_type = phive('Casino')->isBoosterGameType($cur_game['tag']);

        return $res;
    }

    /**
     * @param $ins
     * @param $user //TODO fix this /Ricardo
     * @param $t_entry
     * @param $cur_game
     * @param $b_entries
     * @param int $bonus_bet
     * @param bool $won_already
     * @param array $participation
     */
    public function handleWin($ins, $user, $t_entry, $cur_game, $b_entries, $bonus_bet = 0, $won_already = false,
        $participation = [])
    {
        $this->t_entry = $t_entry;
        $amount        = $ins['amount'];
        $u_obj = cu($user);
        $win_history_data = [];
        phive('Logger')->getLogger('casino')->debug('HandleWin->BEGIN',
        [
            'user' => $user,
            't_entry' => $this->t_entry,
            'insert' => $ins,
            'game' => $cur_game,
            'bonus_entries' => $b_entries,
            'bonus_bet' => $bonus_bet,
            'won' => $won_already
        ]);
        if(!empty($this->t_entry)){
            $get_trophy = !empty($this->t_entry['get_trophy']);
            $get_race = !empty($this->t_entry['get_race']);
        }else{
            rgLimits()->onWin($user, $amount);
            if(mc($amount, $user['currency'], 'div') > 99){
                uEvent('woningame', $amount, $cur_game['game_name'], $cur_game['game_id'], $user['id']);
            }

            if(empty($b_entries) && $won_already !== true && $bonus_bet !== 3) {
                if (phive()->moduleExists('Trophy')) {
                    $get_trophy = true;
                }
                if (phive()->moduleExists('Race')) {
                    $get_race = true;
                }
            }
        }

        $win_history_data = $ins;
        $win_history_data['balance'] =(int) $ins['balance'];
        $win_history_data['trans_id'] = (int) $ins['trans_id'];
        $win_history_data['amount'] = (int) round($ins['amount']);
        $win_history_data['game_ref'] = (string) $ins['game_ref'];
        $win_history_data['mg_id'] = (string) $ins['mg_id'];
        $win_history_data['award_type'] = (int) $ins['award_type'];
        $win_history_data['user_id'] = (int) $ins['user_id'];
        $win_history_data['bonus_bet'] = (int) $ins['bonus_bet'];
        $win_history_data['op_fee'] = (int) $ins['op_fee'];
        $win_history_data['currency'] = (string) $ins['currency'];
        $win_history_data['device_type'] = (int) $ins['device_type'];
        $win_history_data['event_timestamp'] = time();

        if (!empty($this->t_entry)) {
            $win_history_data['tournament_id'] = (int) $this->t_entry['t_id'];
        }

        try {
            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', ['win', new WinHistoryMessage($win_history_data)], $user);
        } catch (InvalidMessageDataException $e) {
            phive('Logger')
                ->getLogger('history_message')
                ->error(
                    $e->getMessage(),
                    [
                        'topic'             => 'win',
                        'validation_errors' => $e->getErrors(),
                        'trace'             => $e->getTrace(),
                        'data'              => $win_history_data
                    ]
                );
        }

		phive('Logger')->getLogger('casino')->debug('HandleWin->raceBegin',
            [
				'get_trophy' => $get_trophy ?? false,
				'get_race' => $get_race ?? false,
				'bonus_entries' => $b_entries,
				'bonus_bet' => $bonus_bet,
				'won' => $won_already,
                'is_country_excluded' => phive('Race')->countryIsExcluded($user)
            ]);

        if (empty($this->t_entry)) {
            // We sleep for quarter of a second to prevent double inserts of game sessions.
            usleep(250000);
            $ugs = lic('getGsessByParticipation', [$ins, $u_obj, $participation, $cur_game, $this->isTournamentMode()], $u_obj);
            if (empty($ugs)) {
                $ugs = $this->getGsess($ins, $user);
            }
            phive('Logger')->getLogger('casino')->debug('HandleWin->Updating gamesession balance',
                [
                    'user' => $user,
                    't_entry' => $this->t_entry,
                    'insert' => $ins,
                    'game' => $cur_game,
                    'bonus_entries' => $b_entries,
                    'bonus_bet' => $bonus_bet,
                    'won' => $won_already,
                    'game_session' => $ugs
                ]);

            phive('SQL')->sh($ugs)->query("UPDATE users_game_sessions SET win_amount = win_amount + {$ins['amount']}, win_cnt = win_cnt + 1 WHERE id = {$ugs['id']}");
            $u_obj->winLossBalance()->refresh($ugs['id'], WinlossBalance::TYPE_WIN, $amount);
            phive('MicroGames')->udgsOnWin($user, $ins, $cur_game);

            /**
             * Update the result_amount and balance_end.
             * @see ES::gSessionHandleLateCall()
             */
            $isLateCall = lic('gSessionHandleLateCall', ['win', $ugs, $ins, $u_obj, $participation, $cur_game, $this->isTournamentMode()], $u_obj);
        }

        if($get_trophy){
            usleep(100000);
            phive('Trophy')->onWin($user, $cur_game, (int)$amount);
        }

        if ($get_race && phive('Race')->countryIsExcluded($user) === false) {
            //$race_multi = $this->getMulti($uid, $uobj, 'race-multiply');
            // We handle the Race logic for this bet without real time updates (false on the end).
            phive('Race')->onWin($user, $cur_game, (int)$amount);
        }
//        $ins['bet_id'] = (int) $bet_id;
        $this->getLoyaltyPoints('wins', $ins, $cur_game, $t_entry);

        /**
         * If our system is processing the last win that comes late for live casino games, we shouldn't
         * send this message to ws to avoid display the 'game_session_limit_reached_popup' popup. Because
         * we can not get the information about users_game_sessions and ext_game_participations (both are already closed).
         *
         * @see \ES\Services\SessionService::wsUpdateSessionAfterBet()
         * @see ES::gSessionHandleLateCall()
         */
        if (empty($isLateCall)) {
            lic('wsUpdateExtGameSessionInfo', [$u_obj, $cur_game], $u_obj);
        }

        phive('Cashier/Arf')->invoke('onWin', $user, $ins, $cur_game);
    }

    function handlePriorFail($user, $tr_id, $balance, $win_deduction){
        $prior_fail = phive('Bonuses')->getFailedBonus($user['id'], phive()->hisMod('-3 seconds'));
        if(!empty($prior_fail)){
            $balance                          = $this->changeBalance($user, "-{$win_deduction}", "Failed win due to failed bonus", 15);
            $prior_fail['win_deduction']      = $win_deduction;
            phive('SQL')->sh($user, 'id', 'failed_bonuses')->save('failed_bonuses', $prior_fail);
        }
        return $balance;
    }

    function bonusBetType(){
      if ($this->frb_win === true)
        $bonus_bet = 3;
      else
        $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
      return $bonus_bet;
    }


    /**
     * Finalizes the free spins bonus for a user.
     *
     * This function handles the completion of a free spins bonus by updating the bonus entry,
     * setting the wagering requirements (if applicable), and adjusting the user's balance based
     * on the total winnings.
     *
     * This method, along with Casino::failFreespinsBonus(), aims to replace the legacy function
     * Casino::handleFspinWin(), which has many side effects and does not return any information
     * about the success of the operation.
     *
     * @param int $total_winnings The total amount won from the free spins.
     * @param array $bonus_entry The current bonus entry details.
     * @param array $bonus_type The type of the bonus being processed.
     * @param array $game The game details associated with the bonus.
     * @param object $user The user object representing the player.
     *
     * @return bool|string Returns false if the bonus entry update fails, otherwise returns the result of the balance change operation.
     */
    public function finishFreeSpinsBonus(int $total_winnings, array $bonus_entry, array $bonus_type, array $game, object $user) {
        phive()->pexec('Casino', 'propagateFreeSpinsBets', [$bonus_entry['id'], $user->getId()]);
        $this->extSessionFreespinsFinished($user);

        $user_game_session = $this->getGsess(
            ['user_id' => $user->getId(), 'game_ref' => $game['ext_game_name']],
            $user,
            true,
            false
        );

        $updates['frb_remaining'] = 0;
        $updates['game_session_id'] = $user_game_session['id'];
        $wagering_requirement = phive('Bonuses')->getTurnover($user, $bonus_type);

        if (empty($wagering_requirement)) {
            // no wagering requirement, finalize the bonus and change the user's balance
            $updates['balance'] = 0;
            $updates['status'] = 'approved';

            $bonus_entry = array_merge($bonus_entry, $updates);
            if (!phive("SQL")->sh($bonus_entry)->save('bonus_entries', $bonus_entry)) {
                return false;
            }
            return $this->changeBalance($user, $total_winnings, 'FRB Win', 2, '', $bonus_type['id'], $bonus_entry['id']);
        }

        // wagering requirement exists, update bonus entry with the wagering requirements, log in cash transactions
        $updates['status'] = 'active';
        $updates['reward'] = $total_winnings;
        $updates['balance'] = $total_winnings;
        $updates['cost'] = ($wagering_requirement / 100) * $total_winnings;
        if (!empty(licSetting('minimum_fs_wager', $user)) && empty($bonus_type['rake_percent'])) {
            $updates['progress_type'] = 'both';
        }

        $bonus_entry = array_merge($bonus_entry, $updates);
        if (!phive("SQL")->sh($bonus_entry)->save('bonus_entries', $bonus_entry)) {
            return false;
        }

        phive()->pexec('Bonuses', 'wsOnProgress', [$bonus_entry['user_id']]);
        return phive('Bonuses')->handleFspinShift($bonus_entry);
    }

    /**
     * Handles the failure of a free spins bonus.
     *
     * This function processes the failure of a free spins bonus by marking the bonus entry as failed
     * and propagating the end of the free spins session.
     *
     * @param int $total_winnings The total amount won from the free spins (usually 0 in case of failure).
     * @param array $bonus_entry The current bonus entry details.
     * @param array $bonus_type The type of the bonus being processed.
     * @param array $game The game details associated with the bonus.
     * @param object $user The user object representing the player.
     */
    public function failFreespinsBonus(int $total_winnings, array $bonus_entry, array $bonus_type, array $game, object $user) {
        phive('Bonuses')->fail(
            $bonus_entry['id'],
            'Free spin bonus without winnings',
            $user->getId(),
            ['frb_remaining' => $bonus_entry['frb_remaining']]
        );

        phive()->pexec('Casino', 'propagateFreeSpinsEndSession', [
            $user->getId(),
            $game['game_id'],
            $game['device_type_num'],
            strtotime($bonus_entry['activated_time']),
            time()
        ]);

        $this->extSessionFreespinsFinished($user);
    }

    /**
     *
     *
     * @param array $e
     * @param int $amount
     * @param $user
     * @param $descr
     * @param false $is_final In case like Playngo that we store the balance in the bonus entry we need to force
     *                          to check if final and if there is any balance. This is like this to avoid problems with
     *                          legacy usage of the function
     */
    function handleFspinWin($e, $amount, $user, $descr, $is_final = false){
        if(empty($e))
            return;
        $b = phive('Bonuses')->getBonus($e['bonus_id']);
        if(empty($b))
            return;


        $device_type = isset($this->cur_game['device_type'])
            ? ($this->device_map[strtolower($this->cur_game['device_type'])] ?? 0)
            : 0;

        $game = phive('MicroGames')->getByGameIdByDevice($b['game_id'], $device_type);

        //if game is for mobile and cur game is desktop we need to update the cur game
        $this->cur_game = (empty($this->cur_game) || ($this->cur_game['ext_game_name'] != $game['ext_game_name'] && $this->cur_game['mobile_id'] == $game['id']))
            ? $game : $this->cur_game;
        $user_game_session = $this->getGsess([
            'user_id' => (int) uid($user),
            'game_ref' => $game['ext_game_name'],
            'device_type' => $device_type
        ], $user);

        $e['game_session_id'] = $user_game_session['id'];

        $this->extSessionFreespinsFinished($user);

        phive()->pexec('Casino', 'propagateFreeSpinsBets', [$e['id'], (int) uid($user)]);

        if ($is_final === true) {
            if (empty($e['balance']) && empty($amount)) {
                //Trigger end session message here so we can record a session in the reporting service.
                //In case of a free spins session without any wins we don't record that anywhere in Phive,
                //apart from zeroing remaining spins in the bonus_entries table.
                phive()->pexec('Casino', 'propagateFreeSpinsEndSession', [(int) uid($user), $b['game_id'], $device_type, strtotime($e['activated_time']), time()]);

                phive('Bonuses')->fail($e, 'Free spin bonus without winnings', '', ['frb_remaining' => $e['frb_remaining']]);
                return;
            }
        } else {
            if(empty($amount)){
                //Trigger end session message here so we can record a session in the reporting service.
                //In case of a free spins session without any wins we don't record that anywhere in Phive,
                //apart from zeroing remaining spins in the bonus_entries table.
                phive()->pexec('Casino', 'propagateFreeSpinsEndSession', [(int) uid($user), $b['game_id'], $device_type, strtotime($e['activated_time']), time()]);

                phive('Bonuses')->fail($e, 'Free spin bonus without winnings', '', ['frb_remaining' => $e['frb_remaining']]);
                return;
            }
        }

        $e['frb_remaining'] = 0;
        $gpname = $gamedata = '';
        if(isset($e['gpname'])){
            $gpname = $e['gpname'];
            $gamedata = $e['gamedata'];
            unset($e['gpname'], $e['gamedata']);
        }

        $wager_turnover = phive('Bonuses')->getTurnover($user, $b);

        //No turnover requirements, set to approved and credit the player
        if(empty($wager_turnover)){
            //TODO we need to capture a false return here to be 100% safe
            $balance = $this->changeBalance($user, $amount, $descr, 2);
            $e['status'] = 'approved';
        }else{
            //We have a bonus with turnover requirements, we set the reward amount (amount to be released upon completion of wagering reqs)
            //to the won amount, we also set the balance to the won amount, this is the balance that can now be used for further game play
            //and towards the wager reqs. Cost is the reward / won amount multiplied by the rake percent divided by 100, 10 eur example
            //with 40 times req: (4000 / 100) * 1000 = 40000
            $e['reward']  += (int)$amount;
            $e['balance'] += (int)$amount;
            $e['cost']    =  ($wager_turnover / 100) * $e['reward'];
            if(!empty(licSetting('minimum_fs_wager', $user)) && empty($b['rake_percent'])){
                // We have an FRB turnover override, eg for Italy AND the bonus normally does not need to be turned over, this means that
                // we make it as easy as possible for the player to turn it over by setting the progress_type in the entry to "both" so
                // that he can use his normal cash balance too.
                $e['progress_type'] = 'both';
            }
            phive('Bonuses')->handleFspinShift($e);
	    phive()->pexec('Bonuses', 'wsOnProgress', array($e['user_id']));
        }

        $entry_update_res = phive("SQL")->sh($e)->save('bonus_entries', $e);

        if($entry_update_res && phive()->getSetting('inhousefrb') === true && in_array($gpname, phive()->getSetting('inhousefrb_network'))){
            // if yes show a popup message telling that frb's are finished and gameplay switches to money-play
            // If we ever are going to use this cLang will probably return null here
            $this->wsInhouseFrb($e['user_id'], cLang(), 'frb.end-msg.html', array_merge($gamedata, ['frb_remaining' => 0,'total_won_amount' => $amount]));
        }
    }

    /**
     * - Used on normal scenarios -
     * Return the transaction (bet/win) from the mg_id.
     * This will search for a single specific "mg_id".
     *
     * @param $mg_id
     * @param string $table
     * @param string $col
     * @param string $uid
     * @return bool|array
     */
    public function getBetByMgId($mg_id, $table = 'bets', $col = 'mg_id', $uid = '')
    {
        $table .= $this->getTableSuffix();
        $uid = $this->getUserId($uid);

        // mg_id is the only column that we can use. /Henrik
        if ($col != 'mg_id') {
            return false;
        }

        $sql_str = "SELECT * FROM $table WHERE $col = '$mg_id'";

        if(empty($uid) && phive('SQL')->isSharded($table))
            return phive('SQL')->shs('merge', '', null, $table)->loadArray($sql_str)[0];
        // TODO deduct amount from balance in order to display correct balance after a retried bet / win if need be.
        return phive('SQL')->sh($uid, '', $table)->loadAssoc($sql_str);
    }

    /**
     * - Used on rollbacks -
     * Return the transaction (bet/win) from the mg_id.
     * This one will search between all the possible format for "mg_id" and return only 1 result
     *
     * Ex. on rollbacks we don't know from the mg_id alone which format was used, so we search for all combinations.
     * GP: Qspin
     * MG_ID: 123
     * - qspin_123 - DEFAULT (standard)
     * - qspin_GB_123 - JUR (standard)
     * - qspin123 - DEFAULT (with custom "old_format")
     * - qspin123-GB - JUR (with custom "old_format")
     *
     * @param array $mg_ids
     * @param string $table
     * @param string $uid
     * @return mixed
     */
    public function getBetByNormalizedMgId($mg_id, $table = 'bets', $uid = '')
    {
        $table .= $this->getTableSuffix();
        $uid = $this->getUserId($uid);

        $mg_ids = [
            $this->getNormalizedTxnId($mg_id, $this->getLicSetting('old_format', $uid)),
            $this->getNormalizedTxnId($mg_id, $this->standard_txn_format),
            $this->getNormalizedTxnId($mg_id, $this->standard_txn_format_with_jur)
        ];

        $sql_mg_ids_in = phive('SQL')->makeIn($mg_ids);
        $sql_str = "SELECT * FROM $table WHERE mg_id IN ($sql_mg_ids_in)";

        return phive('SQL')->sh($uid)->loadAssoc($sql_str);
    }

    function betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $tr_id, $ext_id){
        if(!empty($this->t_entry))
            return $balance;
        $b 			= phive("Bonuses");

        $failed_bonus1 = $b->failByWrongGame($user, $cur_game['ext_game_name']);

        $failed_bonus2 	= $b->progressBonuses($user, $cur_game['ext_game_name'], $bet_amount, $balance, $bonus_bet);
        if($failed_bonus2 === true || $failed_bonus1 === true){
            $new_balance = $this->_getBalance($user);
            $new_balance = is_array($new_balance) ? array_sum($new_balance) : $new_balance;

            phive('SQL')->sh($user, 'id', 'failed_bonuses')->insertArray('failed_bonuses', array(
                'user_id'     => $user['id'],
                'trans_id'    => $tr_id,
                'bonus_bet'   => $bonus_bet,
                'fail_amount' => $balance - $new_balance,
                'game_ref'    => $cur_game['ext_game_name'],
                'mg_id'	      => $ext_id,
                'bet_amount'  => $bet_amount));

            return $new_balance;
        }

        return $balance;
    }

    function getBetOpFee($bet_amount, $cur_game, $jp_contrib){
        return ($bet_amount - $jp_contrib) * $cur_game['op_fee'];
    }

    //can only have one at a time
    function getMulti($uid, &$uobj, $type){
        $multi = 1;
        if(phive()->moduleExists('Trophy'))
            $multi = phive('Trophy')->progressAward($uid, $uobj, $type);
        return empty($multi) ? 1 : $multi;
    }

    /**
     * By default we don't confirm by round id
     * @return bool
     */
    public function doConfirmByRoundId() {
        return false;
    }

    /**
     * Function to insert a round id from a GP that will be related with a bet as we cannot use trans id from bets right now
     * This round link will be use to confirm bets for wins and link them as some feature requires connection between bets and wins
     *
     * @param int $user_id the $user_id of the user
     * @param int $bet_id the bet id in bets table
     * @param string $ext_round_id the round id
     * @param int $win_id the id in the wins or wins_mp table. Defaults to 0.
     * @param int $is_finished whether or not the round is finished..
     * @return int|bool the id of the row inserted or false if didn't insert
     */
    public function insertRound($user_id, $bet_id, $ext_round_id, int $win_id = 0, ?bool $is_finished = false)
    {
        try {
            if ($this->isTournamentMode()) { //We don't support tournament rounds for the time being
                return false;
            }
            $round_id = phive('SQL')->sh($user_id)->insertArray('rounds', compact('user_id', 'bet_id', 'ext_round_id', 'win_id', 'is_finished'));

            if ($this->hasSessionBalance()) {
                phive('SQL')->sh($user_id)->insertArray('ext_game_participations_rounds', ['ext_game_participation_id' => $this->session_entry['id'], 'round_id' => $round_id]);
            }
            return $round_id;
        } catch (Exception $e) {
            error_log("Round insert failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Update the win_id when there is a win related to the round_id
     *
     * @param int $user_id the user id
     * @param string | null $round_id the round id. If round is null, we close the last
     * @param int $win_id the win id
     * @param bool $is_finished whether or not the round is finished
     * @return boolean true or false if the win_id is missing
     */
    public function updateRound($user_id, $round_id = null, $win_id = null, $is_finished = true)
    {
        if ($this->isTournamentMode()) { //We don't support tournament rounds for the time being
            return false;
        }

        // TODO we should also probably pass the game here to double check that the win belongs to the same game as the round
        $round = $this->getLastUnfinishedRound($user_id, $round_id);
        if (empty($round)) {
            // if we don't have unfinished rounds for the player, there's nothing to update
            return false;
        }

        $to_update['is_finished'] = $is_finished;
        if (!empty($win_id)) {
            $to_update['win_id'] = $win_id;
        }

        if ($updated = phive('SQL')->sh($user_id)->updateArray('rounds', $to_update, ['id' => $round['id']])) {
            $this->onGameRoundFinished($user_id);
        }

        return $updated;
    }

    /**
     * Gets the last Finished round for the player
     *
     * @param int $user_id
     * @param string|null $round_id
     * @return array
     */
    public function getLastFinishedRound(int $user_id, ?string $round_id = null): array
    {
        return $this->getRound($user_id, $round_id, true, true, true);
    }

    /**
     * Gets the last unfinished round for the player
     * @param int $user_id
     * @param string|null $round_id
     * @return array
     */
    public function getLastUnfinishedRound(int $user_id, ?string $round_id = null): array
    {
        return $this->getRound($user_id, $round_id, false, true, true);
    }

    /**
     * Main method for getting a single game round
     *
     * @param int $user_id
     * @param string|null $round_id
     * @param bool|null $is_finished
     * @param bool $order
     * @param bool $limit
     * @return array
     */
    public function getRound(int $user_id, ?string $round_id = null, ?bool $is_finished = null, bool $order = false, bool $limit = false, ?int $bet_id = null): array
    {
        $where = [
            'user_id' => $user_id,
        ];

        $force_index = '';
        if (!empty($round_id)) {
            $where['ext_round_id'] = $round_id;
            $force_index = 'FORCE INDEX (rounds_ext_round_id_index)';
        }

        if (!is_null($is_finished)) {
            $where['is_finished'] = $is_finished;
        }

        if(!empty($bet_id)){
            $where['bet_id'] = $bet_id;
        }

        $orderBy = $order ? "ORDER BY id desc" : "";
        $limit = $limit ? " LIMIT 1" : "";

        $sql = "SELECT * FROM rounds ".$force_index. phive('SQL')->makeWhere($where) . "{$orderBy}{$limit}";
        return phive('SQL')
            ->sh($user_id)
            ->loadAssoc($sql) ?: [];
    }

    /**
     * Updates a round by id and allows either the bet_id and/or win_id to be updated.
     * This is necessary because some game providers, e.g. Pariplay, have multiple bets and/or multiple wins for
     * the same external round id and we sometimes need to update a specific row.
     *
     * @param int $user_id. The user id.
     * @param int $row_id. The id (primary key) of the row to update.
     * @param int|null $bet_id. The new bet_id value, or null to leave the bet_id unchanged. Defaults to null.
     * @param int|null $win_id. The new win_id value, or null to leave the win_id unchanged. Defaults to null.
     * @return bool|int
     */
    public function updateRoundById(int $user_id, int $row_id, int $bet_id = null, int $win_id = null)
    {
        $row = [];
        if (!is_null($bet_id)) {
            $row['bet_id'] = $bet_id;
        }
        if (!is_null($win_id)) {
            $row['win_id'] = $win_id;
        }
        if (empty($row)) {
            return false;
        }
        return phive('SQL')->sh($user_id)->updateArray('rounds', $row, ['id' => $row_id]);
    }

    /**
     * Confirms that the round_id (of bet) has been actually inserted in the rounds table
     *
     * @param int $user_id the user id
     * @param string $ext_round_id the round id
     * @return boolean true if the round is found, else false
     */
    public function confirmWin($user_id, $ext_round_id)
    {
        $this->cur_user = cu($user_id);
        $res = $this->getUserGameSessionBets($ext_round_id);
        if (empty($res)) {
            return false;
        }

        $res = reset($res);
        return !empty($res) ? $res : false;
    }

    /**
     * @param $user
     * @param $cur_game
     * @param $tr_id
     * @param $ext_id
     * @param $bet_amount
     * @param $jp_contrib
     * @param $bonus_bet
     * @param $balance
     * @param string $stamp
     * @param int $bonus_bet_amount
     * @return int|bool Bet id or false
     */
    public function insertBet($user, $cur_game, $tr_id, $ext_id, $bet_amount, $jp_contrib, $bonus_bet, $balance, $stamp = '', $bonus_bet_amount = 0){
        if(empty($bonus_bet) && $bet_amount > $user['cash_balance'])
            $bonus_bet = 1;

        $tbl           = empty($this->t_entry) ? 'bets' : 'bets_mp';
        $uid           = $user['id'];
        $device_type   = $this->device_map[ $cur_game['device_type'] ];
        $currency      = empty($this->t_entry) ? $user['currency'] : phive('Tournament')->curIso();

        if(empty($device_type))
            $device_type = 0;

        $ins = array(
            'balance'        => $balance,
            'trans_id'       => $tr_id,
            'amount'         => $bet_amount,
            'game_ref'       => $cur_game['ext_game_name'],
            'mg_id'          => $ext_id,
            'user_id'        => $user['id'],
            'bonus_bet'      => $bonus_bet,
            'op_fee'         => $this->getBetOpFee($bet_amount, $cur_game, $jp_contrib),
            'jp_contrib'     => $jp_contrib,
            'currency'       => $currency,
            'device_type'    => $device_type
        );
        if(!empty($stamp))
            $ins['created_at'] = $stamp;

        if(!empty($this->t_entry)){
            $ins['t_id'] = $this->t_entry['t_id'];
            $ins['e_id'] = $this->t_entry['id'];
        }

        $bet_id = phive('SQL')->sh($uid, '', $tbl)->insertArray($tbl, $ins);
        $ugs = phive('Casino')->getGsess($ins, $uid, true);
        $round_prefix = $cur_game['network'];
        $user_game_session_bets =[
            "bet_id" => $bet_id,
            "session_id" => $ugs['id'],
            "ext_round_id" => strpos($tr_id, $round_prefix) === false ? "{$round_prefix}_{$tr_id}" :  $tr_id,
        ];
        phive('SQL')->sh($ugs)->insertArray("user_game_session_bets", $user_game_session_bets);
        // If Bet couldn't be inserted because of DB being down or Bet already processed
        // we log and return false to avoid everything below being executed as GP will try again.
        // In case of DB being down and in case Bet was already processed the below has been executed
        // already and should not run again.
        if($bet_id === false){
            phive()->dumpTbl('failed-bet', $ins, $uid);
            $this->logger->error("failed-bet",
                [
                    'action' => 'bet',
                    'data' => $ins,
                    'user' => $uid,
                ]
            );
            return false;
        }
        
		$this->forfeitUnactivatedWelcomeBonuses($uid);

        if (empty($this->t_entry)) {
            realtimeStats()->onBet($user['id'], $ins);
        }

        lic('gamePlaySession', [$uid, $ins], $uid);
        // We fork once with complex args
        phive()->pexec('Casino', 'handleBet', [$uid, $this->t_entry, phive('Bonuses')->entries, $cur_game, $ins, $tbl, $bet_id, $this->session_entry, $bonus_bet_amount], 500, $uid);


        $this->setPlayerIsPlayingAGame($uid);

        return $bet_id;
    }

    public function handleBet($uid, $t_entry, $b_entries, $cur_game, $ins, $tbl, $bet_id, $participation = [], $bonus_bet_amount = 0)
    {
        $this->t_entry            = $t_entry;
        $bet_amount               = $ins['amount'];
        $currency                 = $ins['currency'];
        $b                        = phive('Bonuses');
        $b->entries               = $b_entries;
        $is_tournament            = !empty($this->t_entry);
        $uobj                     = cu($uid);
        $transactionId            = $ins['trans_id'];
        
        $bet_history_data = $ins;
        $bet_history_data['balance'] = (int) $ins['balance'];
        $bet_history_data['trans_id'] = (int) $ins['trans_id'];
        $bet_history_data['amount'] = $bonus_bet_amount !== 0 ? (int) $bonus_bet_amount : (int) $ins['amount'];
        $bet_history_data['user_id'] = (int) $ins['user_id'];
        $bet_history_data['bonus_bet'] = (int) $ins['bonus_bet'];
        $bet_history_data['op_fee'] = (int) $ins['op_fee'];
        $bet_history_data['jp_contrib'] = (int) $ins['jp_contrib'];
        $bet_history_data['device_type'] = (int) $ins['device_type'];
        $bet_history_data['event_timestamp'] = time();

        if($is_tournament){
            $get_race    = !empty($this->t_entry['get_race']);
            $get_loyalty = !empty($this->t_entry['get_loyalty']);
            $get_trophy  = !empty($this->t_entry['get_trophy']);
            $bet_history_data['tournament_id'] = (int) $this->t_entry['t_id'];
        }else{
            $get_race    = $b->canRace($uid) === true;
            $get_loyalty = true;
            $get_trophy  = phive()->isEmpty($b->entries);
            rgLimits()->onBetInc($uobj, $bet_amount); //TODO this is here temporary to stop inc/dec limits with BOS, but login progress will not work

            $ugs = lic('getGsessByParticipation', [$ins, $uobj, $participation, $cur_game, $this->isTournamentMode()], $uobj);
            if (empty($ugs)) {
                $ugs = $this->getGsess($ins, $uobj);
            }

            $ins['balance'] = (int) $ins['balance'];
            $ins['trans_id'] = (int) $ins['trans_id'];
            $ins['amount'] = (int) $ins['amount'];
            $ins['user_id'] = (int) $ins['user_id'];
            $ins['bonus_bet'] = (int) $ins['bonus_bet'];
            $ins['op_fee'] = (int) $ins['op_fee'];
            $ins['jp_contrib'] = (int) $ins['jp_contrib'];
            $ins['device_type'] = (int) $ins['device_type'];
            $ins['event_timestamp'] = time();
            $uobj->winLossBalance()->refresh($ugs['id'], WinlossBalance::TYPE_LOSS, $bet_amount);

            phive('SQL')->sh($ugs)->query("UPDATE users_game_sessions SET bet_amount = bet_amount + {$ins['amount']}, bet_cnt = bet_cnt + 1 WHERE id = {$ugs['id']}");

            phive('MicroGames')->udgsOnBet($uobj->data, $ins, $cur_game);

            /**
             * Update the result_amount and balance_end.
             * @see ES::gSessionHandleLateCall()
             */
            lic('gSessionHandleLateCall', ['bet', $ugs, $ins, $uobj, $participation, $cur_game, $this->isTournamentMode()], $uobj);

        }

        try {
            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', ['bet', new BetHistoryMessage($bet_history_data)], $uobj);
        } catch (InvalidMessageDataException $e) {
            phive('Logger')
                ->getLogger('history_message')
                ->error(
                    $e->getMessage(),
                    [
                        'topic'             => 'bet',
                        'validation_errors' => $e->getErrors(),
                        'trace'             => $e->getTrace(),
                        'data'              => $bet_history_data
                    ]
                );
        }

        $gref          = $cur_game['ext_game_name'];
        $has_deposited = phive('Cashier')->hasDeposited($uid);
        $is_slot       = $this->isSlotGameType($cur_game['tag']);
        $ins['bet_id'] = (int) $bet_id;

        if($has_deposited){
            // Current game might have different RTP etc based on which jurisdiction the player is from.
            if ($is_tournament) {
                $override_game = phive('MicroGames')->overrideGameForTournaments($uobj, $cur_game);
            }else{
                $override_game = phive('MicroGames')->overrideGame($uobj, $cur_game);
            }

            if($get_race && $this->isClashGameType($cur_game['tag']) && phive()->moduleExists('Race') && phive('Race')->countryIsExcluded($uobj) === false){
                $race_multi = $this->getMulti($uid, $uobj, 'race-multiply');
                // We handle the Race logic for this bet without real time updates (false on the end).
                phive('Race')->raceBet($uobj->data, $cur_game, $bet_amount, $currency, $race_multi, false);
            }

            if (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) {
                phive('Logger')->getLogger('casino')->debug('MGR_ACTIVE_BONUS_TROPHIES->RACE', [
                    'user_id' => $uid,
                    'game_ref' => $gref,
                    'bet_amount' => $bet_amount,
                    'currency' => $currency,
                    'override_game' => $override_game,
                    'get_race' => $get_race,
                    'is_clash_game' => $this->isClashGameType($cur_game['tag']),
                    'clash_country_excluded' => phive('Race')->countryIsExcluded($uobj),
                    'is_tournament' => $is_tournament,
                    'should_user_get_race' => ($get_race && $this->isClashGameType($cur_game['tag']) && phive()->moduleExists('Race') && phive('Race')->countryIsExcluded($uobj) === false),
                    'no_tournaments' => empty($this->t_entry)
                ]);
            };

            $this->getLoyaltyPoints('bets', $ins, $cur_game, $t_entry, $override_game);

            if (empty($this->t_entry) && phive()->moduleExists('Trophy')) {

                if($this->isWheelContributionGameType($cur_game['tag']) && phive('Trophy')->getSetting('wheel_add_contribution')){
                    phive('Trophy')->giveJackpotContribution($cur_game, $uobj, $bet_amount);
                }

                if (
                    (!$is_tournament ? true : $get_trophy) && //if not tournament check only on active bonus allow_xp_calc logic and XpGameType
                    $this->isXpGameType($cur_game['tag']) &&
                    phive('Bonuses')->isUserAllowToGetXP((int) $ins['user_id'])
                ) {
                    $xp_points = phive('Trophy')->incXp($uobj, $bet_amount, $currency, $override_game);
                }

                if (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) {
                    phive('Logger')->getLogger('casino')->debug('MGR_ACTIVE_BONUS_TROPHIES->WHEEL', [
                        'user_id' => $uid,
                        'game_ref' => $gref,
                        'bet_amount' => $bet_amount,
                        'currency' => $currency,
                        'override_game' => $override_game,
                        'xp_points' => $xp_points ?? null,
                        'is_wheel_contribution' => $this->isWheelContributionGameType($cur_game['tag']),
                        'wheel_add_contribution' => phive('Trophy')->getSetting('wheel_add_contribution'),
                        'get_trophy' => $get_trophy,
                        'is_tournament' => $is_tournament,
                        'is_xp_game_type' => $this->isXpGameType($cur_game['tag']),
                        'is_user_allow_to_get_xp' => phive('Bonuses')->isUserAllowToGetXP((int) $ins['user_id']),
                        'should_user_get_xp' => ($this->isXpGameType($cur_game['tag']) && phive('Bonuses')->isUserAllowToGetXP((int) $ins['user_id'])),
                    ]);
                };

                $this->handleWheelOnBet($uobj, $cur_game, $ins, $xp_points);
            }
        }

        if(phive()->moduleExists('Trophy') && $get_trophy){
            phive('Trophy')->onBet($uobj->data, $cur_game, $bet_amount);
        }

        lic('wsUpdateExtGameSessionInfo', [$uobj, $cur_game], $uobj);

        //phMset(strtolower(get_class($this)).'-betstamp', time());
        mCluster('uaccess')->expire(mKey($uid, 'uaccess'), lic('getSessionTimeout', [], $uobj));


        phive('Cashier/Arf')->invoke('onBet', $uobj, $ins, $cur_game);
    }

    /**
     * getLoyaltyByWin() is used to check if loyalty point should be calculated based on wns and not bets.
     * true = yes
     * false = no
     * @return bool
     */
    public function getLoyaltyByWinAndCountries($uid = null): bool
    {
        if (!phive("Config")->getValue('loyalty-based-on-countries', 'loyalty-by-wins')) {
            return false;
        }

        $countries = array_filter(
            explode(' ', phive("Config")->getValue('loyalty-based-on-countries', 'loyalty-by-wins'))
        );

        $user = cu($uid);
        if(in_array($user->getCountry(), $countries)) {
            return true;
        }
        return false;
    }

    /**
     * Function used to calculate loyalty points and save and return it
     * Managed saving loyalty points based on bets or by wins only
     * If we have config with name = loyalty-by-wins and tags = loyalty-based-on-countries and values = countries,
     * then we will save loyalty points based on wins not bets
     *
     * @deprecated Booster Migration
     * Once all jurisdictions are moved to the new booster, references to this function
     * should be removed to ensure we don't credit loyalty twice
     *
     * @param $bet
     * @param $cur_game
     * @param $t_entry
     * @param $override_game
     * @return float $lpoints
     *
     * */
    public function getLoyaltyPoints($type = 'bets', $bet, $cur_game, $t_entry, $override_game = null)
    {
        $uid = $bet['user_id'];
        $uobj = cu($uid);

        $bet_id         = $bet['bet_id'];
        if ($this->isBoosterGameType($cur_game['tag']) &&
            licSetting('enable_legacy_booster', $uobj) &&
            phive('DBUserHandler/Booster')->hasBoosterVault($uobj) === false) {

            if (($this->getLoyaltyByWinAndCountries($uid) && $type == 'bets') || (!$this->getLoyaltyByWinAndCountries($uid) && $type == 'wins')) {
                return false;
            }

            $gref           = $bet['game_ref'];
            $bet_amount     = $bet['amount'];

            $b              = phive('Bonuses');
            $b->game_tag    = phive('MicroGames')->getGameTagByRef($gref);
            $tbl            = empty($t_entry) ? 'bets' : 'bets_mp';
            $is_tournament  = !empty($t_entry);

            if (isset($bet['win_id']) && !empty($bet['win_id']) && $type == 'wins') {
                $has_win = phive('SQL')->sh($uid)->loadArray("SELECT id, amount, trans_id FROM $tbl WHERE trans_id = {$bet['trans_id']}")[0];
                if ($has_win) {
                    $bet_id     = $has_win['id'];
                    //$bet_amount = $has_win['amount']; // if we need loyalty calculation based on bet amount under win bets then uncomment it.
                }
            }

            if($is_tournament) {
                $get_loyalty = !empty($t_entry['get_loyalty']);
                $override_game = phive('MicroGames')->overrideGameForTournaments($uobj, $cur_game);
            }else{
                $get_loyalty = true;
                $override_game = phive('MicroGames')->overrideGame($uobj, $cur_game);
            }

            $loyalty_p   = $b->getLoyaltyPercentByRef($gref, $uid);
            if(!empty($loyalty_p) && !empty($this->t_entry) && $get_loyalty){
                $t = phive('Tournament')->byId($this->t_entry['t_id']);
                $loyalty_p /= $t['spin_m'];
            }

            $lpoints   = $bet_amount * $loyalty_p;

            // 99.5% is max RTP if you play perfect Black Jack.
            $game_rtp  = empty($override_game['payout_percent']) ? 0.995 : $override_game['payout_percent'];
            if(!empty($lpoints) && $get_loyalty){
                //lpoints is the boosted RTP
                //below is the formula for calculating weekend booster/rainbow fridays in each bet made.
                //$uobj->getLoyaltyDeal() refers data in config table which config_name = 'casino-loyalty-percent', and it is the value that differs in weekend booster and rainbow fridays
                $lpoints = ($uobj->getLoyaltyDeal() * (1 - $game_rtp) * $lpoints) * $this->getMulti($uid, $uobj, 'cashback-multiply');
                $lpoints = dedPc($lpoints, $uobj);

                if(!empty($override_game['payout_extra_percent'])){
                    // For an extra 10% payout_extra_percent needs to be 1.1
                    $lpoints *= $override_game['payout_extra_percent'];
                }

                //TODO use users game settings data in redis for displaying what people make in notifications instead, the below is confusing if people are playing more than one game at the same time
                phMinc(mKey($uid, 'earned-loyalty'), round($lpoints * 100));
            }else{
                $lpoints = 0;
            }
        }

        if (!empty($lpoints)) {
            phive('SQL')->sh($uid, '', $tbl)->updateArray($tbl, ['loyalty' => $lpoints], ['id' => $bet_id]);
        }

        return $lpoints;
    }

    function getRtpProgress($amount, $game, $u_info = null){
        $rtp_game = phive('MicroGames')->overrideGame($u_info, $game);
        $payout   = 0.96 - $rtp_game['payout_percent']; // 0.97 -> -0.01 : 0.95 -> 0.01
        $mod      = $payout * 25;                   // -0.01 * 25 -> -0.25; : 0.25
        return $amount * min(1 + $mod, 2.5);     // 100 * (1 + -0.25) -> 75 : 100 * (1 + 0.25) -> 125
    }

    /**
     * @param DBUser $uobj
     * @param array $cur_game
     * @param array $ins
     * @param int $loyalty
     */
    public function handleWheelOnBet($uobj, $cur_game, $ins, $xp_points = 0)
    {
        if (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) {
            phive('Logger')->getLogger('casino')->debug('MGR_ACTIVE_BONUS_TROPHIES->WHEEL->handleWheelOnBet->1', [
                'user_id' => $uobj->getId(),
                'cur_game' => $cur_game,
                'ins' => $ins,
                'xp_points' => $xp_points ?? null,
                'is_tournament' => $this->isTournamentMode(),
            ]);
        };

        if($this->isTournamentMode()){
            // We can't hand out this stuff during BoS play, that would bankrupt us very quickly.
            return;
        }

        $lic_jur  = licJur($uobj);
        $woj_confs = phive('UserHandler')->getSetting('woj_on_bet')[$lic_jur] ?? null;

        if (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) {
            phive('Logger')->getLogger('casino')->debug('MGR_ACTIVE_BONUS_TROPHIES->WHEEL->handleWheelOnBet->2', [
                'user_id' => $uobj->getId(),
                'lic_jur' => $lic_jur,
                'woj_confs' => $woj_confs ?? null,
                'is_wheel_on_bet' => $this->isWheelOnBetType($cur_game['tag']),
                'is_tournament' => $this->isTournamentMode(),
            ]);
        };

        if (empty($woj_confs) || !$this->isWheelOnBetType($cur_game['tag'])) {
            return;
        }

        if(empty($woj_confs[0])){
            // Standard conf of a 2D assoc array, so we make a new one with it as the first element.
            $woj_confs = [$woj_confs];
        }

        $rand_max = 1000000;

        foreach($woj_confs as $woj_conf){

            $max_per_day = $woj_conf['max_per_day'] ?? null;

            switch ($woj_conf['type']) {
                case 'onBet':
                    $calculated_probability = (1 / $woj_conf['prob']) * $rand_max;
                    if($ins['amount'] >= max($woj_conf['min_bet'], 1) && mt_rand(1, $rand_max) <= $calculated_probability){
                        phive('Trophy')->giveAward($woj_conf['award_id'], $uobj, 0, $max_per_day);
                    }
                    break;
                case 'onXp':
                    $calculated_probability = (1 - ((1 - 1 / $woj_conf['prob']) ** $xp_points)) * $rand_max;

                    if (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) {
                        phive('Logger')->getLogger('casino')->debug('MGR_ACTIVE_BONUS_TROPHIES->WHEEL->handleWheelOnBet->2', [
                            'user_id' => $uobj->getId(),
                            'calculated_probability' => $calculated_probability,
                            'xp_points' => $xp_points,
                            'rand_max' => $rand_max,
                            'woj_conf' => $woj_conf,
                            'should_user_get_xp' => !empty($xp_points) && phive('Bonuses')->isUserAllowToGetXP((int) $uobj->getId()),
                            'max_per_day' => $max_per_day,
                        ]);
                    };

                    if (!empty($xp_points) && mt_rand(1, $rand_max) <= $calculated_probability) {
                        phive('Trophy')->giveAward($woj_conf['award_id'], $uobj, 0, $max_per_day);
                    }
                    break;
                case 'onXpRec':
                    // Award will be given on every interval based on settings
                    // Example every 5th level so, it will be 5, 10, 15, 20 and so on.
                    // Once reward is given it will not repeat for same level.
                    $userCurLevel = $uobj->getSetting('xp-level');
                    $db_xp_points = $uobj->getSetting('xp-points');

                    $userNewLevel = phive('Trophy')->getXpLevel($db_xp_points);
                    $awardId = $woj_conf['award_id'];
                    $userId = $uobj->getId();

                    $trophyAwarded = phive('SQL')->sh($userId)->loadAssoc(
                        "SELECT count(id) AS total FROM trophy_award_ownership tao WHERE award_id = {$awardId} AND user_id = {$userId};"
                    );

                    if (!empty($db_xp_points) &&
                        $userNewLevel > $userCurLevel &&
                        $userNewLevel % $woj_conf['recur'] === 0 &&
                        $userNewLevel / $woj_conf['recur'] > $trophyAwarded['total']) {

                        $ud = $uobj->data;
                        phive('SQL')->sh($ud, 'id')->save('users_settings', [
                            'user_id' => $userId, 'setting' => 'xp-level', 'value' => $userNewLevel
                        ]);
                        uEvent('climbedlevel', $userNewLevel, '', '', $ud);

                        /*
                           |--------------------------------------------------------------------------
                           | Checking if we have trophies available and giving awards
                           | Else give award directly based on config file
                           |--------------------------------------------------------------------------
                         */
                        $thresholdTrophies = phive('Trophy')->getWithTypeThold('xp');
                        if (!empty($thresholdTrophies)) {
                            foreach($thresholdTrophies as $t) {
                                phive('Trophy')->awardOrProgress($t, $ud, $userNewLevel);
                            }
                        } else {
                            phive('Trophy')->giveAward($woj_conf['award_id'], $uobj);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * TODO we need to do this properly passing the game version in cur game and if not available then query it /Ricardo
     *
     * I've done a temporary solution to start logging now
     *
     * @param $ins
     * @param null $user
     * @param bool $insert_if_missing
     * @return mixed
     */
    function getGsess($ins, $user = null, $insert_if_missing = true, $check_device_type = true){

        // We get the current session.
        $str = "
            SELECT * FROM users_game_sessions
            WHERE user_id = {$ins['user_id']}
            AND end_time = '0000-00-00 00:00:00'
            AND game_ref = '{$ins['game_ref']}'";

        if ($check_device_type) {
            $str .= " AND device_type_num = {$ins['device_type']}";
        }

        $current = phive('SQL')->sh($ins)->loadAssoc($str);

        /** @var DBUser $u */
        $u = cu($user);

        // We create the row if it doesn't already exist.
        if(empty($current) && $insert_if_missing){
            $current = phive()->mapit([
                'user_id'         => 'user_id',
                'game_ref'        => 'game_ref',
                'device_type_num' => 'device_type',
                'balance_start'   => 'balance' // Should contain bonus balance too but is that OK or?
            ], $ins);
            $current['session_id']    = $u->getCurrentSession()['id'];
            $current['ip']            = $u->data['cur_ip'];

            // This should fix the balance_start after bet bug
            if (!empty($current['balance_start'])) {
                $current['balance_start'] += $ins['amount'];
            } else {
                $current['balance_start'] = $u->data['cash_balance'] + $ins['amount'];
            }
            // Currently ignores potential bonus balances, ok or?
            //$current['balance_start'] = $u->data['cash_balance'];

            $id = phive('SQL')->sh($ins)->insertArray('users_game_sessions', $current);
            if($id === false){
                $this->logger->error("failed-gsess",
                    [
                        'action' => 'game_session',
                        'data' => $current,
                        'user' => $user->getId(),
                    ]
                );
            }

            $current['id'] = $id;

            lic('handleGameVersion', [$id, $ins], $u);
        }

        return $current;
    }

    // todo ugs
    // finish the database row by writing end stamp etc
    // Parameter $secondCall is added to prevent circular calls between this method and finishExternalGameSession
    public function finishUniqueGameSession($ugs, $ud = [], $secondCall = false)
    {
        if (empty($ugs['id']) || $secondCall) {
            // We don't want to end up inserting new rows in case the callee somehow is sending us rows that are
            // missing the primary key.
            return null;
        }
        $ud                   = empty($ud) ? ud($ugs['user_id']) : $ud;
        $ugs['end_time']      = phive()->hisNow();
        $ugs['result_amount'] = $ugs['win_amount'] - $ugs['bet_amount'];
        $ugs['balance_end']   = $ud['cash_balance'];

        phive('SQL')->sh($ugs)->save('users_game_sessions', $ugs);

        if (method_exists($this, 'finishExternalGameSession')) {
            $this->finishExternalGameSession($ud, true);
        }

        // We don't want to fire ARF check when it's an empty game session (Ex. user loading the game without doing any action)
        if($ugs['bet_amount'] > 0 || $ugs['win_amount'] > 0) {
            phive()->pexec('Cashier/Arf', 'invoke', ['onGameSessionEnd', $ud['id'], $ugs['id']]);
        }
        $user = cu($ud['id']);
        $user->winLossBalance()->refresh($ugs['id'], WinlossBalance::TYPE_RESET);

        if($ugs['bet_amount'] > 0 || $ugs['win_amount'] > 0) {
            /** @uses Licensed::addRecordToHistory() */
            lic(
                'addRecordToHistory',
                [
                    'end_session',
                    new EndSessionHistoryMessage(
                        [
                            'game_session_id' => (int)$ugs['id'],
                            'user_id' => (int)$ud['id'],
                            'game_ref' => $ugs['game_ref'],
                            'device_type' => (int)$ugs['device_type_num'],
                            'start_time' => $ugs['start_time'],
                            'end_time' => $ugs['end_time'],
                            'is_tournament' => $this->isTournamentMode(),
                            'event_timestamp' => time(),
                        ]
                    )
                ],
                $user
            );
        }
    }

    /**
     * Finishes All open User Game Sessions by a given user ($uid the user ID), let's say a user has several open game sessions, then, when this method is called
     * it will finish all those open game session
     * If a set of game references is passed using the game_refs argument, for instance ['playngo286', 'whateverprovider9090'] it will
     * finish the sessions for those specified games.
     *
     * @param mixed $uid User id
     * @param array $game_refs Game references e.g. ['playngo286', 'whateverprovider9090']
     */
    public function finishGameSession($uid, $game_refs = [], $isApi = false){
        $ud = ud($uid);
        /** @var SQL $sql */
        $sql = phive('SQL');

        // We start with getting all open game sessions.
        $str = "SELECT * FROM users_game_sessions WHERE user_id = {$ud['id']} AND end_time = '0000-00-00 00:00:00'";
        if(!empty($game_refs)) {
            $game_refs_in_sql = $sql->makeIn($game_refs);
            $str .= " AND game_ref IN ({$game_refs_in_sql})";
        }
        $open = $sql->sh($ud, 'id')->loadArray($str);
        if (empty($open) && $isApi){
            return ['error' => 'No session found for this game_id'];
        }
        foreach($open as $ugs)
            $this->finishUniqueGameSession($ugs, $ud);
    }

    /*
    function getSessKey($arr, $prefix){
        return mKey($arr['user_id'], "gsess-$prefix-{$arr['device_type']}-{$arr['game_ref']}");
    }
    */

    function tEntryBalance(){
        if($this->t_entry['status'] != 'open')
            return 0;
        else
            return $this->t_entry['cash_balance'];
    }

    function getPlayCurrency($ud, $eid = ''){
        if(!empty($eid))
            return phive('Tournament')->getSetting('currency');
        // Battle, here we return the battle currency if we're playing a Battle.
        return empty($this->t_entry) ? $ud['currency'] : phive('Tournament')->getSetting('currency');
    }

    /*
       Example rollback of wager:
       1. Player makes a bet of 1 and wins 10.
       2. Some week later GP wants to roll back both bet and win.
       3. Player (probably) first gets credited 1, at this point in time his balance is 5, after credit: 6, a transaction of 1 EUR is logged with type 7.
       4. GP now sends rollback to debit player 10 EUR, but he has got only 6 left. Balance is deducted by 6 EUR without a transaction before we get here.
       Type 60 can happen in VERY RARE cases in case something updates the balance while we're executing here.
     */
    function doRollbackUpdate($mg_id, $tbl, $balance, $amount, $old = array())
    {
        $args = func_get_args();
        $extra = $tbl == 'wins' ? '' : ", jp_contrib = 0, loyalty = 0";

        $trans = $this->getBetByMgId($mg_id, $tbl);
        // No transaction to roll back, ie bet / win never reached us.
        if(empty($trans))
            return false;
        $u     = cu($trans['user_id']);
        if(empty($u))
            return false;

        // If we're rolling back a BoS bet / win we need to pic the correct table
        if(!empty($this->t_entry)){
            $tbl .= '_mp';
            $play_mode = "tournament";
        }else
            $play_mode = 'normal';

        try {
            phive()->dumpTbl('rollback-update', [
                'betwin' => $trans,
                'gp' => get_class($this),
                'args' => $args,
                'new_balance' => $u->getBalance(),
                'req_data' => ['get' => $_GET, 'post' => $_POST, 'body' => file_get_contents('php://input')]
            ], $trans['user_id']);
        } catch (Exception $e) {
            phive()->dumpTbl('rollback-update', ['fatal.error' => $e->getMessage()]);
        }

        // In case we have the bet / win in question we insert the 7 transaction for logging.
        $my_name = ucfirst(get_class($this));
        if(!empty($trans)){
            $descr = "$my_name rollback adjustment of {$amount} cents, balance after: $balance cents, " .
                "play mode: $play_mode, rollback type: $tbl";
            phive('UserHandler')->logAction($trans['user_id'], $descr, 'rollback');
            $type = 7;
            if ($tbl == "wins") {
                if($balance == 0) {
                    $amount = (-1) * $this->old_balance;
                }
            }
            phive('Cashier')->insertTransaction($trans['user_id'], $amount, $type, $descr, 0, '', 0, $trans['id']);
            $amount = abs($amount);
        }

        $sql = phive("SQL");
        $result = $sql->sh($trans, 'user_id', $tbl)
            ->query("UPDATE $tbl SET op_fee = 0, mg_id = '{$mg_id}ref' $extra WHERE mg_id = '$mg_id'");
        if(!empty($old)){
            unset($old['id']);
            unset($old['amount']);
            unset($old['op_fee']);
            unset($old['jp_contrib']);
            unset($old['loyalty']);
            $sql->sh($trans, 'user_id', $tbl)->insertArray($tbl, $old);
        }

        $ugs       = $this->getGsess($trans, $u);
        $rollback_data = [
            'user_id'           => (int) $u->getId(),
            'amount'            => (int) $amount,
            'currency'          => $u->getCurrency(),
            'mg_id'             => $mg_id,
            'game_ref'          => $ugs['game_ref'],
            'device_type'       => (int) $ugs['device_type_num'],
            'event_timestamp'   => time(),
        ];

        if (!empty($this->t_entry)) {
            $rollback_data['tournament_id'] = (int) $this->t_entry['id'];
        }

        if(strpos($tbl, 'bets') === false) {
            $key = 'wins_rollback';
            $rollback_data = new WinsRollbackHistoryMessage($rollback_data);
        } else {
            $key = 'bets_rollback';
            $rollback_data = new BetsRollbackHistoryMessage($rollback_data);
        }
        phive('SQL')->sh($ugs)->query("UPDATE users_game_sessions SET $key = $key + {abs($amount)} WHERE id = {$ugs['id']}");

        if ($play_mode == 'normal') {
            realtimeStats()->onRollback($u, $tbl, $amount);
        }

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
            $key,
            $rollback_data
        ],
            $u);

        phive()->pexec('na', 'lic', ['cancelReportSession', [$ugs['id'], $u->getId(), $tbl, $amount], $u->getId()], 500, true);

        return $result;
    }

    function bonusChgBalance($user, $amount, $descr, $type, $bid, $entry_id = 0){
        return $this->changeBalance($user, $amount, $descr, $type, '', $bid, $entry_id);
    }

    /**
     * Changes a player's main balance or BoS balance.
     *
     * Battle, this update method must be used in order to update tournament balance properly.
     *
     * Note that We pre-initialize the pexec Redis connection for this user on the bet debit, if Redis is down we break execution
     * with fatal error here to avoid a debit without corresponding credit in case of a win. This only
     * applies to calls that do the bet and the win in the same call, like Netent's withdrawAndDeposit.
     *
     * @param array|DBuser $ud The player object / array.
     * @param int $amount The debit / credit amount in cents, negative for debit.
     * @param string $description TODO refactor this, it is unused by the subsequent Casino::changeBalance() call.
     * @param int $type Transaction type, will be 0 (bet), 1 (win) or 7 (rollback) in this case.
     * @param int $bonus_id Bonus id for reference in case game play was with bonus.
     * @param int|null $bonus_bet_type_from_bets Bonus type from Bets table - 0 (regular win), 1 (bonus win), 3 (FRB)
     *
     * @return mixed
     */
    function playChgBalance($ud, $amount, $description = '', $type = 0, $bonus_id = 0, $bonus_bet_type_from_bets = null){
        if(is_object($ud))
            $ud = $ud->data;
        $this->old_balance =  cu($ud)->getBalance();
        // If we're looking at a bet
        if((int)$type === 1){
            // The Redis pre-initialization
            mCluster('pexec')->getJson(mKey($ud['id'], 'na'));
        }

        if(empty($this->t_entry)){
            if((int)$type === 4)
                $type = 2;
            return $this->changeBalance($ud, $amount, '', $type, '', $bonus_id, 0,
                false, 0, "", 0, $bonus_bet_type_from_bets);
        }else{
            $th = phive('Tournament');
            if(empty($this->mp))
                $this->mp = $th->getByEntry($this->t_entry);
            $t = $this->mp;

            $this->t_entry = phive('Tournament')->playChgEntry($this->t_entry, $amount, $ud, $t, $this->multi_call, $this);
            return $this->t_entry['cash_balance'];
        }
    }

    /**
     * Gets the micro_games.ext_game_name value that is possibly being cached in misc. member variables.
     *
     * @return string The unique game identifier.
     */
    public function getCurGameRef(){
        return $this->cur_game['ext_game_name'] ?? $this->new_token['game_ref'];
    }

    /**
     * Will update the players balance based on the args presented.
     *
     * @param $user
     * @param $amount
     * @param string $description
     * @param int $type
     * @param string $deduct_amount
     * @param int $bonus_id
     * @param int $entry_id
     * @param false $fresh
     * @param int $parent_id
     * @param string $product this is used if we want to update the rglimit that isn't the default one, and also use in case of sportsbook
     * @param int $tournament_id used for tournament cash transaction history message
     * @param int $bonus_bet_type_from_bets this is used to compare bonus_bet from bets table with win table
     * @return false|int
     */
    function changeBalance($user, $amount, $description = '', $type = 0, $deduct_amount = '', $bonus_id = 0, $entry_id = 0,
                           $fresh = false, $parent_id = 0, $product = "", int $tournament_id = 0, $bonus_bet_type_from_bets = null){
      $this->chg_calls++;

      $user = cu($user);
      if (empty($user)) {
          error_log('Fatal error: no user to credit / debit: '.json_encode(func_get_args()));
          exit;
      }

      $deduct_amount = empty($deduct_amount) ? $amount : $deduct_amount;
      $user          = cu($user);

      // This will generate a fatal error if the user does not exist, should be picked up by the logparser

      try{
          $user_id = $user->getId();
          $old_balance = $user->getBalance();
      }
      catch (Exception $e){
            error_log('Fatal error: no user to credit / debit: '.$e->getTraceAsString().' :: '.json_encode(func_get_args()));
            exit;
      }

        // We have a bet
      if($type === 1){

        $game_ref          = $this->getCurGameRef();
        $udata             = $fresh ? phive('UserHandler')->getRawUser($user_id) : $user->data;
        $bonus_balances    = phive('Bonuses')->getBalanceByRef($game_ref, $user_id, true);
        $cash_balance      = $this->hasSessionBalance() ? $this->getSessionBalance($user) : (int)$udata['cash_balance'];
        $this->new_balance = $cash_balance + $bonus_balances;

        // if the user does not have enough money to cover the bet, return false
        if ($this->new_balance < abs($deduct_amount)) {
            return false;
        }

        $remaining_amount = (int) $deduct_amount; // The remaining amount we have to take after each step

        if ($cash_balance + $remaining_amount >= 0) { // if all the money is available in the cash balance
            $inc_result = $user->incrementAttribute('cash_balance', $remaining_amount, true);

            if ($inc_result && $this->hasSessionBalance()) {
                $inc_result = $this->incrementSessionBalance($remaining_amount);
            }

            if (!$inc_result) { // if deducting the amount from cash balance or session balance failed
                return false;
            }

            if (lic('hasTaxOnBet', [], $user)) { // this is not being used, was for Germany only and needs to go outside of this method, as $this->new_balance will be overwritten
                $tax_result = lic('deductBetTaxAmount', [$user, $remaining_amount, $cash_balance], $user);
                if (!empty($tax_deducted)) {
                    $this->new_balance = $tax_result + $bonus_balances;
                }
            }

            $remaining_amount = 0;
        } else if ($cash_balance > 0) { // if some cash balance available, not all
            $inc_result = $user->incrementAttribute('cash_balance', -$cash_balance, true);

            if ($inc_result && $this->hasSessionBalance()) {
                $inc_result = $this->incrementSessionBalance(-$cash_balance);
            }

            if (!$inc_result) { // if deducting the amount from cash balance or session balance failed
                return false;
            }

            $remaining_amount += $cash_balance;
        }

        if ($remaining_amount !== 0 && $bonus_balances > 0) { // if we still have some amount to deduct, try to take it from bonuses
            $this->bonus_bet = 1;

            $bonus_entries = phive('Bonuses')->onlyBonusBalanceEntries($this->bonus_bet, $user_id, true);
            if (empty($bonus_entries)) { // prevent fraud when the active bonuses are not included in the bonus balance
                return false;
            }

            foreach($bonus_entries as $bonus_entry) {
                $bonus_balance = (int) $bonus_entry['balance']; // 200

                if ($bonus_balance + $remaining_amount >= 0) { // Bonus has enough to cover the remaining amount
                    $inc_result =  phive('Bonuses')->increaseBonusBalance($remaining_amount, $bonus_entry, $user_id);
                    if ($inc_result) {
                        $remaining_amount = 0; break;
                    }
                } else { // Bonus balance covers the partial amounts
                    $inc_result =  phive('Bonuses')->increaseBonusBalance(-$bonus_balance, $bonus_entry, $user_id);
                    $remaining_amount += $bonus_balance;
                }

                if (!$inc_result) {
                    return false;
                }
            }
        }

        // The following conditional should rarely be executed.
        if($remaining_amount !== 0) { // not enough money on cash balance and bonus balance, declare loses
            foreach (phive('Config')->valAsArray('emails', 'win-rollback') as $to) {
                phive('MailHandler2')->saveRawMail('Not enough money for bet', $user->getUserName()." made a wager of ". abs($deduct_amount) ." but only had real $cash_balance and bonus $bonus_balances in his account", '', $to);
                phive('Cashier')->insertTransaction($user, $remaining_amount, 82, phive('Cashier')->getTransactionTypes()[82]);
            }
        }

        $this->new_balance = $cash_balance + $bonus_balances + $deduct_amount - $remaining_amount;

        // We have a win (2) or rollback of a bet (7).
    } elseif ($type == 2 || $type == 7) {
          $udata = $fresh ? phive('UserHandler')->getRawUser($user_id) : $user->data;
          $bonus_balances = phive('Bonuses')->getBalanceByRef($this->getCurGameRef(), $user_id);

          $cash_balance = $this->hasSessionBalance() ? $this->getSessionBalance($user) : (int)$udata['cash_balance'];
          $this->new_balance = $bonus_balances + $cash_balance + $amount;
          // GPR is sending proper value for bonus_bet
          if (!is_null($bonus_bet_type_from_bets)) {
              $this->bonus_bet = (int)$bonus_bet_type_from_bets;
          }
          // Keep legacy logic - non GPR integration
          if ($cash_balance == 0 && is_null($bonus_bet_type_from_bets)) {
              $this->bonus_bet = 1;
          }

          $bonus_entries = phive("Bonuses")->onlyBonusBalanceEntries($this->bonus_bet, $user_id, true);

          if(
            ($this->bonus_bet == 1 && !empty($bonus_entries) && $product !== self::SPORTSBOOK_PRODUCT) || 
            ($this->send_winnings_to_bonus_balance === true && !empty($bonus_entries))
          ) {
              $bonus_entry = reset($bonus_entries);
              phive('Bonuses')->increaseBonusBalance($amount, $bonus_entry, $user_id); // Add the win amount to the first bonus entry
          } else {
              $inc_result = $user->incrementAttribute('cash_balance', $amount);
              if ($inc_result && $this->hasSessionBalance()) {
                  $this->incrementSessionBalance($amount);
              }
              if ($type == 7 && $inc_result !== false && lic('hasTaxOnBet', [], $user)) {
                  $tax_result = lic('deductBetTaxAmount', [$user, $deduct_amount, $cash_balance, $type], $user);
                  if (!empty($tax_deducted)) {
                      $this->new_balance = $tax_result;
                  }
              }

              try {
                  if ($type == 2 && $inc_result !== false && $this->is_booster_win_game_type) { //We only do it on wins that successfully change the balance
                      phive('DBUserHandler/Booster')->transferWinAmount($user, $amount, $this->win_id);
                  }
              } catch (Exception $e) {
                  error_log("Booster win logic failed silently: {$e->getMessage()}");
              }
          }

      }else{
          $insert = array(
              'user_id'         => $user_id,
              'currency'        => $user->getAttr('currency'),
              'amount'          => $amount,
              'description'     => $description,
              'bonus_id'        => $bonus_id,
              'entry_id'        => $entry_id,
              'parent_id'       => $parent_id,
              'session_id'      => $user->getCurrentSession()['id'],
              'transactiontype' => $type
          );

          $cash_transaction = phive('Cashier')->getCashTransaction($parent_id, $type, $user_id);
          if (!empty($cash_transaction)) {
              phive('Cashier')->logCashTransaction($insert, $cash_transaction);
              return false;
          }

          $inc_result = $user->incAttr('cash_balance', $amount, true);
          if ($inc_result === false) {
              $context = [
                  'uid' => $user_id,
                  'cash_balance' => $user->data['cash_balance'],
                  'amount' => $amount
              ];
              phive('Logger')->getLogger('payments')->error("Failed to update cash balance.", $context);
              if ($product === self::SPORTSBOOK_PRODUCT) {
                  phive('Logger')->getLogger('sportsbook')->error("Failed to update cash balance.", $context);
              }

              return false;
          }

          $insert['balance'] = $user->getAttribute('cash_balance');

          $result            = phive('SQL')->sh($insert, 'user_id', 'cash_transactions')->insertArray('cash_transactions', $insert);
          $this->cur_tr_id   = phive('SQL')->sh($insert, 'user_id', 'cash_transactions')->insertBigId();
          $this->new_balance = $insert['balance'];
          if($result === false){
              error_log("Fatal error: failed transaction on change balance function. Data: ". json_encode($insert));
              $this->logger->error("failed-cash_transactions",
                  [
                      'action' => 'cash_transactions',
                      'data' => $insert,
                      'user' => $user_id,
                  ]
              );
            return false;
          }

          $history_message = [
              'user_id'          => (int) $user_id,
              'transaction_id'   => (int) $this->cur_tr_id,
              'amount'           => (int) $amount,
              'currency'         => $user->getCurrency(),
              'transaction_type' => (int) $type,
              'parent_id'        => (int) $parent_id,
              'description'      => $description,
              'event_timestamp'  => time(),
          ];
          if (!$bonus_id) {// if it has a bonus_id, we come from creating a bonus
              if ($tournament_id === 0) {
                  $report_type = 'cash_transaction';
                  $history_message = new CashTransactionHistoryMessage($history_message);
              } else {
                  $history_message['tournament_id'] = $tournament_id;
                  $history_message['device_type'] = phive()->isMobile() ? 1 : 0;
                  $report_type = 'tournament_cash_transaction';
                  $history_message = new TournamentCashTransactionHistoryMessage($history_message);
              }
          } else {
              if ($amount >= 0) {
                  $report_type = 'bonus';
                  $history_message = new BonusHistoryMessage($history_message);
              } else {
                  $report_type = 'bonus_cancellation';
                  $history_message = new BonusCancellationHistoryMessage($history_message);
              }
          }
          /** @uses Licensed::addRecordToHistory() */
          lic('addRecordToHistory', [
              $report_type,
              $history_message,
          ], $user);

          realtimeStats()->onCashTransaction($user, $type, $amount);
          $this->updateLossLimit($user, $type, $amount, $product);

          if($tournament_id !== 0) {
            //update wager limits for buyin, fees, rebuy, etc
            Phive('Tournament')->updateWagerLimit($user, $type, $amount);
          }
      }

        if(lic('hasBalanceTypeLimit', [], $user)) {
            rgLimits()->onBalanceChanged($user, (int) $this->new_balance);

            if($user->hasExceededBalanceLimit()) {
                toWs(['new_balance' => (int) $this->new_balance], "exceeded_balance_limit", $user->getId());
            }
        }

        if ($product === self::SPORTSBOOK_PRODUCT) {
            phive('Logger')->getLogger('sportsbook')->info("Update cash balance has been successfully processed",
            [
                'user_id'          => (int) $user_id,
                'amount'           => (int) $amount,
                'currency'         => $user->getCurrency(),
                'transaction_type' => (int) $type,
                'old_balance'      => (int) $old_balance,
                'new_balance'      => (int) $this->new_balance,
                'event_timestamp'  => time(),
            ]);
        }

      return (int)$this->new_balance;
    }

    function updateLossLimit($user, $type, $amount, $product = "") {
        $rg_lim = rgLimits();
        $loss_type = phive('DBUserHandler/RgLimits')->getProductLimitType('loss', $product);
        try {
            $user   = cu($user);
            $amount = abs($amount); //I work with ABS amounts as right now the +/- can mislead
            if (in_array($type, [31, 32, 38, 61, 63, 64, 66, 69, 77, 80, 82, 84, 86, 90])) { //reward
                $rg_lim->decType($user, $loss_type, $amount);
            } elseif (in_array($type, [34, 52, 54, 67, 72])) { //fail reward - //I remove 74/75 fro now
                $rg_lim->incType($user, $loss_type, $amount);
            }
        } catch (Exception $e) {
            error_log("Lga increase failed: {$e->getMessage()}");
        }

    }

    /**
     * Wrapper for the object passed via Redis used for Google tracking.
     *
     * @param $deposit_id
     * @param $type
     * @param string $deposit_type [Can be first_deposit, first_month_deposit]
     * @return array
     */
    private function getDepositInfoRedis($deposit_id, $type, $user_id, string $deposit_type = '')
    {
        $result = phive('SQL')->sh($user_id, '', 'deposits')->loadArray("SELECT * FROM deposits WHERE id = {$deposit_id}");
        $deposit = $result[0];

        $user       = cu($user_id);
        $bonus_code = !phive()->isEmpty(phive('Bonuses')->getCurReload($user)) ? phive('Bonuses')->getCurReload($user) : 'no-bonus-code';

        $amountInCent = $deposit['amount'] / 100;
        $key        = "gtm_deposit_{$user_id}_{$deposit['dep_type']}_{$amountInCent}";
        $groupTransactionId = $deposit_id;
        if ($user->hasSetting($key)) {
            $userSetting = $user->getSetting($key);
            $userSetting = json_decode(base64_decode($userSetting), true);
            $groupTransactionId = $userSetting['random'];
        }

        return [
            'triggered' => 'yes',
            'id' => $deposit_id,
            'amount' => $this->dep_in_euro,
            'type' => $type,
            'payment_method' => $deposit['dep_type'],
            'bonus_code' => $bonus_code,
            'deposit_type' => $deposit_type,
            'deposit_data' => $deposit,
            'group_transaction_id' => $groupTransactionId,
            'model' => 'deposits',
            'model_id' => $deposit_id
        ];
    }

    /*
       Returns new balance
     */
    function depositCash($username, $cents, $type, $ext_id, $scheme = '', $card_hash = '', $loc_id = '', $deduct = false, $status = 'approved', $deduct_cents = null, $mts_id=0, $display_name = '', $real_cost = 0, $to_credit = true, $extraParams = []){
        $user       = cu($username);
        if(empty($user))
            return 'no_user';
        $user_id    = $user->getId();
        $orig_cents = $cents;
        $c          = phive('Cashier');
        if($deduct_cents === null)
            $deduct_cents = round($c->getInDeduct($cents, $type, '', $user, false));

        if($deduct)
            $cents -= $deduct_cents;

        $count  = count($c->getFirstTimeDeposits($user_id));

        //$user->setAttribute('bust_treshold', floor($cents * 0.1), false);
        $currency = $user->getCurrency();

        if(!empty($card_hash) && empty($scheme) && !in_array($type, ['paysafe']))
            $scheme = phive('WireCard')->getCardType($card_hash);

        //If the display name is empty and we have a card transaction we want VISA or MC, otherwise Trustly or whatever
        if(empty($display_name) && $c->typeIsCard($type, $scheme))
            $display_name = strtoupper($scheme);
        else if(empty($display_name))
            $display_name = ucfirst($type);

        $insert = array(
            'user_id'       => $user_id,
            'amount' 	    => $cents,
            'dep_type'      => $type,
            'ext_id' 	    => $ext_id,
            'scheme' 	    => $scheme,
            'card_hash'     => $card_hash,
            'loc_id'	    => $loc_id,
            'status'        => $status,
            'currency'      => $currency,
            'display_name'  => $display_name,
            'ip_num'        => $user->getAttr('cur_ip'),
            'mts_id'        => $mts_id   // MTS transaction ID
        );

        $insert['deducted_amount'] 	        = $deduct_cents;

        if (!empty($real_cost)) {
            $insert['real_cost'] = $real_cost;
        } else {
            $real_cost = $deduct ? $orig_cents : $orig_cents + $insert['deducted_amount'];
            $insert['real_cost'] = $c->getInFee($real_cost, $type, $scheme, $user);
        }

        $res = phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $insert);
        if (phive('CasinoCashier')->getSettingArrayIntersect('mts_debug.log.payment_provider', ['*', 'instadebit'], true)) {
            phive()->dumpTbl('info ' . __METHOD__ . '::' . __LINE__, ['count_first_time_deposits' => $count, 'transaction' => $insert, 'res' => $res, 'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)], $user_id);
        }
        if($res === false){
            phive()->dumpTbl('deposit-exists-or-db-error', $insert, $insert);
            return 'deposit_exists';
        }

        $this->did  = $did = $res;
        if ($to_credit === true) {
            $result = $this->changeBalance($user, $cents, 'Deposit', Cashier::CASH_TRANSACTION_DEPOSIT, '', 0, 0, false, $did);
        } else {
            $result = true;
        }

        rgLimits()->incType($user, 'deposit', $insert['amount']);
        rgLimits()->incType($user, 'net_deposit', $insert['amount']);
        rgLimits()->incType($user, 'customer_net_deposit', $insert['amount']);

        // TODO format with new function from Alex when branch is merged, twoDec force value to (int) so it cannot be used. /Paolo
        $this->dep_in_euro = round(chg($currency, 'EUR', $insert['amount'], 1) / 100, 2);

        $deposit_type = '';

        if($count === 0){
            $this->handleFirstTimeDeposit($user, $cents, $type, $currency, $orig_cents, $did);
            $deposit_type = 'first_deposit';
            if (!isPNP($user)) {
                phive()->pexec('Cashier/Arf', 'invoke', ['onFirstDeposit', $user_id]);
            }
            lic('affordabilityCheck', [$user->getId()], $user->getId());
            if ($net_deposit_limit_month = lic('getNetDepositMonthLimit', [$user], $user)) {
                lic('setNetDepositMonthLimit', [$user, $net_deposit_limit_month], $user);
            }

            if (lic('getLicSetting', ['cross_brand'], $user)['cndl_includes_first_deposit']) {
                rgLimits()->incType($user, 'net_deposit', $insert['amount']);
            }

        }else{
            if(!empty($card_hash)) {
                $c->handleCardDeposit($user);
            }
            if(phive()->moduleExists('Trophy')){
                phive('Trophy')->execDepositAward($user->getId(), array('cents' => $cents));
            }
            phive('Bonuses')->handleReloadDeposit($user, $cents, $orig_cents, $currency);
        }

        lic('onDeposit', [$user, $did, $extraParams], $user);

        if ($result === false) {
            return false;
        }

        (new ClosedLoopHelper($c))->setClosedLoopStartTimestamp($user, $did);

        if(empty($deposit_type)) {
            $user->setTrackingEvent('enod', $this->getDepositInfoRedis($did, $type, $user_id, $deposit_type));
        }

        if($c->checkDepLimitPlayBlock($user_id) !== true) {
            $user->playBlock();
        }

        phive('UserHandler')->resetOnDeposit($user);

        if(phive()->moduleExists('Trophy')){
            phive('Trophy')->onDeposit($user, $type, $scheme);
        }

        // ARF checks on every deposit
        phive()->pexec('Cashier/Arf', 'invoke', ['onDeposit', $user_id, $did]);

        rgLimits()->removePendingDeposit($user, $cents);

        return $result;
    }

    /**
     * @param DBUser $user
     * @param $cents
     * @param $type
     * @param $currency
     * @param $orig_cents
     * @param $deposit_id - the ID on deposits table for the same deposit (to retrieve extra info Ex. deposit stats)
     * @return bool|string
     */
    function handleFirstTimeDeposit($user, $cents, $type, $currency, $orig_cents, $deposit_id){
        $user_id = $user->getId();

        $finsert = [
            'user_id' => $user_id,
            'amount' => $cents,
            'currency' => $currency,
            'deposit_id' => $deposit_id,
        ];

        $free_m      = phive('Config')->getByTagValues('free-money');
        $countries   = explode(' ', strtolower($free_m['countries']));

        //We start out without bonus errors
        $first_bonus = [];
        //Have we overridden with a custom bonus? If not we do default.
        $first_bonus = phive('Bonuses')->handleReloadDeposit($user, $cents, $orig_cents, $currency);
        if(!$first_bonus)
            $first_bonus = phive('Bonuses')->handleFirstDeposit($user, $cents);
        $bid                            = $first_bonus['id'];
        $finsert['bonus_id']            = $bid;
        $finsert['dep_type']            = $type;
        $fdid = phive('SQL')->sh($finsert, 'user_id', 'first_deposits')->insertArray('first_deposits', $finsert);

        $user->setTrackingEvent('made-first-deposit', $this->getDepositInfoRedis($deposit_id, $type, $user_id));
        $can_free = ($cents >= mc(1000, $currency) && $free_m['on'] == 1 && in_array(strtolower($user->getAttr('country')), $countries));
        $bcode    = $user->getAttr('bonus_code');

        if(phive()->moduleExists('Trophy')){
            //They already got the award designated by the bonus setting so we skip this part to avoid duplicate awards
            if(empty($first_bonus['award_id'])){
                $aff_award = phive('Trophy')->awardByBonusCode(trim($bcode));
                if(!empty($aff_award))
                    phive('Trophy')->giveAward($aff_award, $user->data);
                else if($can_free){
                    if(!empty($free_m['bonus-id-first-deposit']))
                        phive('Trophy')->giveAward($free_m['bonus-id-first-deposit'], $user->data);
                    else if(!empty($free_m['amount-first-deposit']))
                        phive('Trophy')->giveAward($free_m['amount-first-deposit'], $user->data);
                }
            }
        }else{
            $first_non_dep = array_shift(phive('Bonuses')->getNonDepsByBcode($bcode));
            if(!empty($first_non_dep)){
                phive('Bonuses')->addUserBonus($user_id, $first_non_dep['id'], true);
            }else if($can_free){
                if(!empty($free_m['bonus-id-first-deposit']))
                    phive('Bonuses')->addUserBonus($user_id, $free_m['bonus-id-first-deposit'], true);
                else if(!empty($free_m['amount-first-deposit']))
                    $this->bonusChgBalance($user, mc($free_m['amount-first-deposit'], $user), '#welcome.deposit', 14, $bid);
            }
        }

        if ($user->hasSetting('show_ps_after_first_dep')) {
            $user->deleteSetting('show_ps_after_first_dep');
        }

        if($first_bonus !== false)
            return 'bonus';
        return true;
    }

    /**
     * @param $tbl - bets/wins
     * @param DBUser $user
     * @param string $limit - num of rows to return
     * @param false $bonuses_too - when false exclude bonus bets
     * @param array $category_game_refs - list of game_ref filtered by "expanded_category"
     * @return array|string
     */
    function getBetsOrWins($tbl, $user, $limit = '', $bonuses_too = false, $category_game_refs = [], string $provider= "", string $game_name= "", string $starting_date = "", string $end_date = "")
    {
        $user_id = empty($user) ? $_SESSION['mg_id'] : $user->getId();
        $where_bonus_bet = $bonuses_too ? "" : "AND $tbl.bonus_bet = 0";
        $provider_query = empty($provider) ? "" : "AND LOWER(games.network) = LOWER('$provider')";
        $game_name_query= empty($game_name) ? "" : "AND LOWER(games.game_name) = LOWER('$game_name')";
        $date_query = (!empty($starting_date) && !(empty($end_date))) ? "AND $tbl.created_at BETWEEN '$starting_date' AND '$end_date'" : "";
        $where_game_refs = '';
        if (!empty($category_game_refs)) {
            $game_refs_in = phive('SQL')->makeIn($category_game_refs);
            $where_game_refs = " AND games.ext_game_name IN ({$game_refs_in})";
        }

        $sql = "SELECT $tbl.amount AS amount, $tbl.created_at, $tbl.mg_id, games.game_name FROM $tbl
                INNER JOIN micro_games AS games ON $tbl.game_ref = games.ext_game_name AND $tbl.device_type = games.device_type_num
                    $provider_query
                    $game_name_query
                WHERE $tbl.user_id = $user_id
                $where_bonus_bet
                $where_game_refs
                $date_query
                ORDER BY $tbl.id DESC " . phive('SQL')->getLimit($limit);

        $res = $this->db->readOnly()->sh($user_id)->loadArray($sql);
        if (empty($res)) {
            $this->db->prependFromNodeArchive($res, $user_id, null, $sql, $tbl);
        }
        return $res;
    }

    /**
     * Wrapper around getBetsOrWins for wins
     * @see Casino::getBetsOrWins()
     *
     * @param $user
     * @param string $limit
     * @param false $bonuses_too
     * @param array $game_refs
     * @return array|string
     */
    function getWins($user, $limit = '', $bonuses_too = false, $game_refs = [], string $provider_name = "", string $game_name = "", string $start_date = "", string $end_date = "")
    {
        return $this->getBetsOrWins('wins', $user, $limit, $bonuses_too, $game_refs, $provider_name, $game_name, $start_date, $end_date);
    }

    /**
     * Wrapper around getBetsOrWins for bets
     * @see Casino::getBetsOrWins()
     *
     * @param $user
     * @param string $limit
     * @param false $bonuses_too
     * @param array $game_refs
     * @return array|string
     */
    function getBets($user, $limit = '', $bonuses_too = false, $game_refs = [], string $provider_name = "", string $game_name = "", string $start_date = "", string $end_date = "")
    {
        return $this->getBetsOrWins('bets', $user, $limit, $bonuses_too, $game_refs, $provider_name, $game_name, $start_date, $end_date);
    }

    /**
     * Get all losses by joining rounds table with
     * win and bet table. If there is no matching
     * row in round table for the win it is a loss,
     * and if there is matching win row but the bet
     * amount was higher than the win amount it is
     * also counted as a loss.
     * @param DBUser $user
     * @param string $provider
     * @param string $game_name
     * @param string $starting_date
     * @param string $end_date
     * @return array
     */
    public function getRoundsLosses(
        DBUser $user,
        string $provider = '',
        string $game_name = '',
        string $starting_date = '',
        string $end_date = ''
    ): array
    {
        $user_id = $user->getId();
        $provider_query = empty($provider) ? "" : "AND LOWER(mg.network) = LOWER('$provider')";
        $game_name_query = empty($game_name) ? "" : "AND LOWER(mg.game_name) = LOWER('$game_name')";
        $date_query = (!empty($starting_date) && !(empty($end_date))) ? "AND b.created_at BETWEEN '$starting_date' AND '$end_date'" : "";

        $query = "SELECT IFNULL(w.amount, 0) - b.amount AS net
            FROM rounds r
            INNER JOIN bets b ON b.id = r.bet_id
            LEFT JOIN wins w ON w.id = r.win_id
            INNER JOIN micro_games mg ON b.game_ref = mg.ext_game_name
            WHERE r.user_id = $user_id
            AND IFNULL(w.amount, 0) < b.amount
            AND b.bonus_bet != 3
            $provider_query
            $game_name_query
            $date_query
            GROUP BY r.id";

        $result = $this->db->readOnly()->sh($user_id)->loadArray($query);

        return $result;
    }

    /**
     * get data on games a user have played
     *
     * @param int $user_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    function getUserGameData($user_id, $start_date = '', $end_date = '')
    {
        return phive('SQL')->sh($user_id)->loadArray(
                "SELECT
                DISTINCT mg.id AS game_id,
                mg.device_type_num AS mobile,
                ugs.game_ref AS game_ref,
                mg.network AS provider,
                mg.game_name AS game_name,
                mg.operator AS operator
                FROM users_game_sessions AS ugs
                JOIN micro_games AS mg
                    ON ugs.game_ref = mg.ext_game_name
                        AND ugs.device_type_num = mg.device_type_num
                        AND mg.active = 1
                WHERE ugs.user_id = $user_id
                    AND ugs.start_time BETWEEN '$start_date' AND '$end_date'
                    AND (ugs.bet_amount > 0 OR ugs.win_amount > 0)
                    "
        );
    }

    /**
     * get sum bets and sum wins from users_game_sessions
     *
     * @param int $user_id
     * @param string $start_date
     * @param string $end_date
     * @param string $game_ref - filter a single game
     * @param int $mobile - device_type_num 0/1
     * @param string $network - filter by micro_games.network
     * @param array $category_game_refs - list of game_ref filtered by "expanded_category"
     * @return array
     */
    function getSumsBetsWinsByUserId(
        $user_id,
        $start_date = '',
        $end_date = '',
        $game_ref = '',
        $mobile = 0,
        $network = '',
        $category_game_refs = []
    ) {
        $join = [];
        $where = [
            "ugs.user_id = $user_id",
            "ugs.start_time BETWEEN '$start_date' AND '$end_date'",
        ];
        if (!empty($game_ref)) {
            $where[] = "ugs.game_ref = '$game_ref'";
            $where[] = "ugs.device_type_num = $mobile";
        } elseif (!empty($network)) {
            $join[] = "JOIN micro_games AS mg
                ON ugs.game_ref = mg.ext_game_name
                AND ugs.device_type_num = mg.device_type_num
                AND mg.network = '$network'
            ";
        }

        if (!empty($category_game_refs)) {
            $where_game_refs = phive('SQL')->makeIn($category_game_refs);
            $where[] = "ugs.game_ref IN ($where_game_refs)";
        }

        $sql = "SELECT
            SUM(ugs.bet_amount) AS sum_bets,
            SUM(ugs.win_amount) AS sum_wins
            FROM users_game_sessions AS ugs
            " . implode(" ", $join) . "
            WHERE " . implode(" AND ", $where);

        return phive('SQL')->sh($user_id)->loadAssoc($sql);
    }

    function getComplStatsPerDay($table, $sdate, $edate, $sel_extra = '', $from_extra = '', $where_extra = '', $group_by_extra = '', $group_by_day = true){

        if($table == 'bets' || $table == 'bets_tmp')
            $sel_extra .= ', SUM( tbl.jp_contrib ) AS jp_sum';

        $day_group = $group_by_day ? 'day_date' : '';

        // NOTE this query requires bets, wins and users to all be sharded
        $sql = "SELECT
                    tbl . * ,
                    SUM( tbl.amount ) AS amount_sum,
                    SUM( tbl.op_fee ) AS op_fee_sum,
                    DATE( tbl.created_at ) AS day_date,
                    u.username,
                    u.firstname,
                    u.lastname
                    $sel_extra
                FROM $table AS tbl $from_extra
                LEFT JOIN users AS u ON u.id = tbl.user_id
                WHERE tbl.created_at >= '$sdate'
                    AND tbl.created_at <= '$edate'
                    $where_extra
                GROUP BY $group_by_extra $day_group
                    ORDER BY tbl.created_at";

        $col = (empty($group_by_extra) && $group_by_day) ? 'day_date' : str_replace('tbl.', '', $group_by_extra);

        //echo "\nsql: $sql, \n col: $col\n";

        //return phive('SQL')->loadArray($sql, 'ASSOC', $col);
        return $this->db->shs('merge', '', null, $table)->loadArray($sql, 'ASSOC', $col);
    }

    /*
    //all good, micro games exists on each node already
    function getComplStatsPerDayGame($table, $sdate, $edate){
        return $this->getComplStatsPerDay($table, $sdate, $edate, ", g.game_name", ", micro_games g", "AND tbl.game_ref = g.ext_game_name", "tbl.game_ref");
    }
    */

    /*
    function getComplStatsPerDayUser($table, $sdate, $edate){
        //$res = $this->getComplStatsPerDay($table, $sdate, $edate, ", u.username, u.id", ", users u", "AND tbl.user_id = u.id", "tbl.user_id");
        $res = $this->getComplStatsPerDay($table, $sdate, $edate, "", "", "", "tbl.user_id");
        phive('SQL')->joinTbl($res, 'users', 'user_id', ['username']);
        return $res;
    }
    */

    function getComplStatsPerUser($table, $sdate, $edate, $where_extra = ''){
        //return $this->getComplStatsPerDay($table, $sdate, $edate, ", u.username, u.id, u.firstname, u.lastname", ", users u", "AND tbl.user_id = u.id $where_extra", "tbl.user_id", false);
        return $this->getComplStatsPerDay($table, $sdate, $edate, "", "", $where_extra, "tbl.user_id", false);
        //phive('SQL')->joinTbl($res, 'users', 'user_id', ['username', 'firstname', 'lastname']);
        //return $res;
    }

    //all good, no join
    function getComplBonusPlayStatsPerUser($table, $sdate, $edate){
        return $this->getComplStatsPerUser($table, $sdate, $edate, " AND tbl.bonus_bet = 1 ");
    }

    /*
    function getComplStatsPerDayUserGame($table, $sdate, $edate){
        //return $this->getComplStatsPerDay($table, $sdate, $edate, ", g.game_name, u.username", ", micro_games g, users u", "AND tbl.user_id = u.id AND tbl.game_ref = g.ext_game_name", "tbl.game_ref, tbl.user_id");
        $res = $this->getComplStatsPerDay($table, $sdate, $edate, ", g.game_name", ", micro_games g", "AND tbl.game_ref = g.ext_game_name", "tbl.game_ref, tbl.user_id");
        phive('SQL')->joinTbl($res, 'users', 'user_id', ['username']);
        return $res;
    }
    */


    function getBetsOrWinSumForUser($table, $user_id, $sdate, $edate, $bonus_bet = ''){
        $edate 	= empty($edate) ? date('Y-m-d H:i:s') : $edate;
        $bwhere = $bonus_bet == '' ? '' : "AND bonus_bet = $bonus_bet";
        $sql 	= "SELECT SUM(amount) FROM $table WHERE user_id = $user_id AND created_at >= '$sdate' AND created_at <= '$edate' $bwhere";
        $sum 	= phive('SQL')->sh($user_id, '', $table)->getValue($sql);
        //if(phive("SQL")->settingExists('archive'))
        //  return $sum + phive('SQL')->doDb('archive')->getValue($sql);
        //else
        return $sum;
    }

    function getBetsOrWinsForUser($table, $user_id, $sdate, $edate, $bonus_bet = '', $extra = '', $do_archive = true, $add = array()){
        $bwhere = $bonus_bet == '' ? '' : "AND bonus_bet = $bonus_bet";
        $sql = "SELECT * FROM $table WHERE user_id = $user_id AND created_at >= '$sdate' AND created_at <= '$edate' $bwhere $extra";
        //$res = phive('SQL')->loadArray($sql);
        $res = phive('SQL')->readOnly()->sh($user_id, '', $table)->loadArray($sql);
        /*
        if(phive("SQL")->settingExists('archive') && $do_archive)
            $res = phive()->sort2d(array_merge($res, phive('SQL')->doDb('archive')->loadArray($sql)), 'id');
        */
        if(!empty($add)){
            foreach($res as &$el)
                $el = array_merge($el, $add);
        }
        return $res;
    }

    // TODO henrik remove this
    function archive($tbl = 'bets', $date = ''){
        phive('SQL')->archiveTable($tbl, $date);
    }

    function bigWinners($col = 'amount', $limit = 30, $date = '', $tbl = 'wins_tmp'){
        $str = "
      SELECT w1.game_ref, w1.user_id, w1.amount, w1.balance, w1.currency, w1.created_at
      FROM $tbl w1
          INNER JOIN(
      SELECT max($col) max_num, user_id
      FROM $tbl
      GROUP BY user_id
      ) AS w2 ON w1.user_id = w2.user_id AND w1.$col = w2.max_num
          ORDER BY w1.$col DESC
      LIMIT 0, $limit";
        //$res = phive('SQL')->loadArray($str);
        $res = phive('SQL')->shs('merge', $col, 'desc', $tbl)->loadArray($str);
        if(!empty($date))
            phive()->miscCache("$date-bigwin-$col", json_encode($res));
        return $res;
    }


  function okResult($result){
    if($result === false || !isset($result))
      return false;
    return true;
  }

    public function parentBalance($user_id = null, $format = true)
    {
        $user_id = empty($user_id) ? $_SESSION['mg_id'] : $user_id;
        if (!empty($user_id)) {
            if (empty($this->cur_balance)) {
                $user = cu($user_id);
                if (empty($user)) {
                    jsRedirect('/');
                }
                $this->cur_balance = $user->getAttr('cash_balance');
            }

            if ($format) {
                return $this->cur_balance / 100;
            } else {
                return $this->cur_balance;
            }
        }
        return false;
    }

  function validate($fields){
    $arr = array();
    foreach($fields as $field => $conf)
      $arr[] = array('field' => $field, 'value' => $_POST[$field], 'func' => $conf[1], 'params' => $conf[2]);
    return PhiveValidator::start()->validateArr($arr);
  }

    function validateV2($fields, $post)
    {
        $arr = [];
        foreach ($fields as $field => $conf) {
            $arr[] = ['field' => $field, 'value' => $post[$field], 'func' => $conf[1], 'params' => $conf[2]];
        }
        return PhiveValidator::start()->validateArr($arr);
    }

  function balances($user = null, $no_ext = false){
    $user = empty($user) ? cu($_SESSION['mg_id']) : $user;
    if(empty($user))
      return array();
    $uid = $user->getId();
    if (!$no_ext) {
      if(!empty($this->ext_balances[$uid]))
        return $this->ext_balances[$uid];
    }
    $this->ext_balances[$uid] = array(
      'bonus_balance' => (int)phive('Bonuses')->getBalanceByUser($uid),
      'casino_wager' => (int)phive("Bonuses")->getRewards($uid, 'casinowager'),
      'cash_balance' => (int)$user->getBalance()
    );
    return $this->ext_balances[$uid];
  }

    function zeroBalance($uid, $zero_cash = true)
    {
        $user_old = cu($uid);

        if ($zero_cash) {
            $to_update = ['cash_balance' => 0];
            phive('SQL')->sh($uid, '', 'users')->updateArray('users', $to_update, array('id' => $uid));
            lic('onUserCreatedOrUpdated', [$uid, $to_update, $user_old ? $user_old->getData() : []]);
        }

        return phive('SQL')->sh($uid, '', 'bonus_entries')->updateArray('bonus_entries', ['status' => 'failed'], array('user_id' => $uid, 'status' => 'active'));
    }

    /**
     * Called on bet to determine if RG limits have been reached.
     *
     * Also handles BoS related limits.
     *
     * @param array $ud Player info from the users table in an assoc array.
     * @param string $game_ref This is micro_games.ext_game_name
     * @param int $balance Player balance.
     * @param mixed $device_type The device type 0/1 or flash/html5.
     * @param int $amount The bet amount.
     *
     * @return int The balance, 0 if limit has been hit.
     * TODO RG
     */
    function lgaMobileBalance($ud, $game_ref, $balance, $device_type = '', $amount = 0){
        if (empty($ud['active'])) {
            return 0;
        }
        $user = cu($ud);
        if ($user->isPlayBlocked()) {
            return 0;
        }

        if(!empty($this->t_entry)){
            $th = phive('Tournament');
            $t = $th->getByEntry($this->t_entry);

            $game = phive('MicroGames')->getByGameRef($game_ref);

            if(!$th->checkForCorrectGame($game, $t)){
                return 0;
            }

            //if($t['category'] == 'freeroll')
            //return $balance;
            //phive()->dumpTbl('mp-limit-error', array($amount, $t, $amount));
            if($amount > 0){
                if($amount > $t['max_bet'] && !empty($t['max_bet']))
                    $msg = 'TournamentBox.prMpOverLimit.func';
                if($amount < $t['min_bet'] && !empty($t['min_bet']))
                    $msg = 'TournamentBox.prMpUnderLimit.func';

                // handling variable bet levels
                if(!empty($t['min_bet']) && !empty($t['max_bet']) && empty($msg)){
                    $bet_lvls = explode(',', $t['bet_levels']);
                    //Bet is not an accepted bet level
                    if(count($bet_lvls) > 1 && !in_array($amount, $bet_lvls))
                        $msg = 'TournamentBox.prMpWrongBetSize.func';

                    //if($amount % $t['min_bet'] > 0)
                        //    $msg = 'TournamentBox.prMpWrongBetSize.func';
                }
            }

            if(!empty($t['pause_calc']))
                $msg = 'TournamentBox.prMpPausedCalc.func';

            if (str_contains($msg ?? '', 'prMpUnderLimit') || str_contains($msg ?? '', 'prMpOverLimit')) {
                $module = $this->getNetworkName($game['network']);
                if (phive()->methodExists($module, 'handleBetOutsideLimits')) {
                    phive($module)->handleBetOutsideLimits();
                }
            }

            if(!empty($msg)){
                $this->pexecLimit($ud, $msg, $t['game_ref'], 'no', $this->t_eid);
                return 0;
            }

            if(!$th->entryIsOpen($this->t_entry))
                $th->wsEntryFinished($this->t_entry, $ud, $t, 'mplimit', 'yes');

        }else{
            /*
               TODO fix this for WMS and Qspin
            if(!empty($device_type)){
                if(!phive('MicroGames')->hasSession($ud, ['ext_game_name' => $game_ref, 'device_type_num' => $this->getDeviceNum($device_type)]))
                    return 0;
            }
            */

            if(!empty(lic('hasGameSessionRestrictions', [$user], $user))) {
                toWs(['popup' => 'game_session_temporary_restriction', 'msg' => 'true'], 'extgamesess', $user->getId());
                return 0;
            }

            if (lic('hasGameplayWithSessionBalance', [], $user)  === true) {
                $game['game_id'] = $game_ref;

                if (lic('hasExceededTimeLimit', [$user], $user) === true && !lic('skipPopupDueToFreeSpins', [$user, $game], $user)) {
                    $participation = lic('getOpenParticipation', [$user], $user);
                    //if user has active limits and he has exceed them
                    if ($participation['time_limit']){
                        //prevent customers to manually hide the popup to continue playing
                        toWs(['popup' => 'game_session_limit_reached_popup', 'msg' => 'true'], 'extgamesess', $user->getId());
                    } else {
                        //if game session was started with freespin bonus and user hasn't set limits yet - restart game session
                        toWs(['popup' => 'game_session_restart', 'msg' => 'true'], 'extgamesess', $user->getId());
                    }

                    return 0;
                }
            }

            if (!empty(lic('gamePlayPaused', [$user], $user))) {
                return 0;
            }

            if (lic('geoComplyPlayBlock', [$user], $user)) {
                return 0;
            }

            $user_id = $ud['id'];

            if($amount > 0){
                $failed_bonus 	= phive('Bonuses')->failByRequirements($ud, $game_ref, $amount);
                if($failed_bonus === true){
                    $msg = 'bonus.betmax.reached.html';
                    phMset(mKey($ud, 'lgalimit-msg'), $msg);
                    $this->pexecLimit($ud, $msg, $game_ref, 'no', '', '');
                    return 0;
                }
            }

            if(phive()->getSetting('lga_reality') !== true)
                return $balance;
            if($this->game_action == 'win')
                return $balance;
            if(empty($this->game))
                $this->game = phive('MicroGames')->getByGameRef($game_ref, $device_type);
            if(empty($this->game))
                return $balance;
            // Added extra check to check if game is not active
            //TODO refactor this properly /Ricardo
            if ($this->game['game_id'] != 'egt_999' && empty($this->game['active'])){
                $this->inactive_game = true;
                return 0;
            }

            if(is_object($user) == false)
                return $balance;
            // small improvement to store the error message if some limit are reached and have the error available globally
            $limitCheckMsg = $this->lgaLimitsCheck($user, false, $amount, $game_ref, $device_type);
            if($limitCheckMsg != 'OK'){
                $this->limit_reached = true;
                $this->limit_msg = $limitCheckMsg;
                return 0;
            }

            // The balance needs to be lower than the threshold
            $thold = (int)phive('Cashier')->getSetting('fast_deposit_popup_thold');
            if(!empty($thold) && $amount > 0 && $amount > $balance && $balance < mc($thold, $ud['currency'])){
                $fast_psp = phive('Cashier')->getFastPsp($ud);
                if(!empty($fast_psp) && phive('Cashier')->getDepositCount($ud['id'], $fast_psp) > 0){
                    $this->pexecLimit($ud, "showFastDepositPopup___$fast_psp,cash.low.html", $game_ref, 'no', '', '');
                }
            }

        }

        return $balance;
    }

  function wsLga($uid, $lang, $alias, $game_ref, $go_home = 'yes', $t_eid = 0, $wstag = 'mplimit'){
    phive('Localizer')->setLanguage($lang, true);
    setCur($uid);
    //$GLOBALS['cur_t_eid'] = $t_eid;
    if(strpos($alias, '.func') !== false){
      list($cls, $func, $ext) = explode('.', $alias);
      $msg = phive('BoxHandler')->getRawBoxHtml($cls, $func, $t_eid);
    } else {
        // We don't translate if the message is on the someJsFunc___arg1,arg2 format.
        $msg = strpos($alias, '___') !== false ? $alias : t($alias);
    }
    $tournament = empty($t_eid) ? 'no' : 'yes';
    if($tournament == 'yes'){
      $entry = phive('Tournament')->_getEntryWhere(array('id' => $t_eid), $uid);
      $tag = $wstag.$entry['t_id'];
    } else {
        $tag = 'lgalimitmsg' . $game_ref;
    }
    $arr = array(
      'eid'        => $t_eid,
      'msg'        => $msg,
      'gohome'     => $go_home,
      'game_ref'   => $game_ref,
      'source'     => $alias,
      'wstag'      => $wstag,
      'tournament' => $tournament);
    toWs($arr, $tag, $uid);
  }

    /**
     * Send in-house frb status data to web socket to trigger a initial/end popup to inform the player about current
     * freespins remaining
     * @param $uid
     * @param $lang
     * @param $alias
     * @param $gameData An array with gamedata + frb_remaining key
     */
    function wsInhouseFrb($uid, $lang, $alias, array $gameData = array()){
        phive('Localizer')->setLanguage($lang, true);
        setCur($uid);

        toWs(array(
            'eid'        => 0,
            'msg'        => tAssoc($alias, $gameData),
            'gohome'     => 'no',
            'gamedata'   => $gameData,
            'source'     => $alias,
            'wstag'      => 'mplimit',
            'tournament' => 'no'
        ), 'inhousefrb', $uid);
    }

    function lgaLimitsCheck($user = '', $translate = true, $amount = 0, $game_ref = '', $device_type = ''){
        if(phive()->getSetting('lga_reality') !== true)
            return 'OK';

        $rg_lims = rgLimits();

        if(empty($user))
            $user = cu();

        if(is_object($user)){
            $u       = $user->data;
            $msg     = '';
            $go_home = 'yes';
            if(empty($msg)){
                $res = $rg_lims->onBetCheck($user, $amount);
                if(is_array($res)){

                    switch($res['type']){

                        case 'betmax':
                            phMset("{$u['id']}-betmax-reached", 'yes');
                            $msg     = 'betmax.reached.html';
                            $go_home = 'no';
                            break;

                        case 'timeout':
                            $rg_lims->resetTimeLimit($user, $res);
                            $msg = 'lgatime.reached.html';
                            break;

                        case 'rc':
                            $rg_lims->resetTimeLimit($user, $res);

                            if($this->getSetting('do_backend_rc') === true){
                                // TODO Jonathan, do what you want here.
                                // Set a member variable that you can check further down the execution tree in order to return custom error to GP if that is required.
                                // Ex. rcReached is implemented inside Bsg.php, and it just return a custom error message that will be used to check the value returned by lgaMobileBalance
                                $msg = $this->rcReached($user, $res, $device_type);
                            }
                            break;

                        case 'login':
                            $msg = 'rg.login.reached.html';
                            break;

                        default:
                            $msg = "lga{$res['type']}.reached.html";
                            break;
                    }
                }
            }

            if(empty($msg)){
                return 'OK';
            }

            $this->pexecLimit($u, $msg, $game_ref, $go_home, '', '');

            $ret = $translate ? t($msg) : $msg;
            phMset(mKey($u, 'lgalimit-msg'), $ret);
            return $ret;
        }

        return 'OK';
    }

    function pexecLimit(&$u, $msg, $game_ref, $go_home = 'yes', $t_eid = '', $wstag = 'mplimit', $sleep = 500)
    {
        if (hasWs()) {
            $args = [$u['id'], $u['preferred_lang'], $msg, $game_ref, $go_home, $t_eid, $wstag];
            phive('Events/EventPublisher')->single('casino', 'casinoLimitEvent', $args, 0);
        }
    }

    function setTmp ( $suffix ) {
        if ( $suffix === true )
            $this->suffix = '_tmp';
        if ( $suffix === false )
            $this->suffix = '';
        return $this;
    }

    function makeBetWinTmpForDate ( $date, $suffix = '' ) {
        $sstamp = $date . ' 00:00:00';
        $estamp = $date . ' 23:59:59';
        $this->makeBetWinTmp( $sstamp, $estamp, $suffix );
    }

    // $suffix could be _mp for instance to do the battles.
    function makeBetWinTmp($sstamp, $estamp, $suffix = ''){
        $this->delBetWinTmp($suffix);
        //TODO looks like the bets_mp_tmp table is not needed, if that's the case we remove this
        if($suffix == '_mp'){
            $in = phive('SQL')->makeIn(phive('Tournament')->getDailyEntryIds($sstamp));
            $where = "e_id IN($in)";
        }else
            $where = "created_at >= '$sstamp' AND created_at <= '$estamp'";
        //We create the tmp table on each shard
        phive('SQL')->makeTmp('bets'.$suffix, $where);
        phive('SQL')->makeTmp('wins'.$suffix, $where);
        $this->setTmp(true);
        return $this;
    }

    function delBetWinTmp($suffix) {
        phive('SQL')->delTmp('bets'.$suffix);
        phive('SQL')->delTmp('wins'.$suffix);
        return $this;
    }

    function doProxy($ud = ''){
        if($ud['country'] == 'AU')
            return true;
        $get_cached = phive()->getSetting('do_proxy_with_country_attribute') === true ? true : false;
        return in_array(cuCountry('', $get_cached), ['AU']);
    }

    function getProxySetting($normal, $ud = '', $proxy = ''){
        $ud = ud($ud);

        // If we don't want to proxy and if player is not Australian
        if(!$this->doProxy($ud)) {
          return $this->getSetting($normal);
        }

        // If we want to proxy or if the player is Australian
        // Proxying so we use the proxy version and if it doesn't exist we just prepend proxy_ to the normal version.
        if(empty($proxy))
          $proxy = "proxy_{$normal}";

        phive()->dumpTbl("proxy", [
            'action' => "Proxyed url",
            'url' => $proxy,
            'user id' => $ud['id'],
            'username' => $ud['username'],
            'country' => $ud['country']
          ]
        );

        $res = $this->getSetting($proxy);
        if(empty($res))
            $res = $this->getSetting($normal);
        return $res;
    }

    function getCountry($ud){
        $ud = ud($ud);
        if($ud['country'] == 'AU')
            return 'FI';
        return in_array(cuCountry('', false), ['AU']) ? 'FI' : $ud['country'];
    }

    function getCity($ud){
        $ud = ud($ud);
        if($ud['country'] == 'AU')
            return 'Turku';
        return in_array(cuCountry('', false), ['AU']) ? 'Turku' : $ud['city'];
    }

    // Scenarios:
    // 1.) Non Australian with non Australian IP
    // 2.) Australian with Australian IP
    // 3.) Australian with non Australian IP
    // 4.) Non Australian with Australian IP
    function getUid($ud, $uid = ''){
        $ud = ud($ud);
        if($ud['country'] == 'AU')
            $postfix = 'ST';
        else
            $postfix = in_array(cuCountry('', false), ['AU']) ? 'ST' : $ud['country'];
        if(empty($uid))
            $uid = $this->getPlayUid($ud['id']);
        if($postfix == 'AU'){
            phive()->dumpTbl('proxy-error', ['ud' => $ud, 'ip' => remIp(), 'uid' => "$uid-$postfix"]);
        }
        return "$uid-$postfix";
    }

    /**
     * Shortcut for getting country / jurisdiction settings
     *
     * @param string $key The config setting to fetch.
     * @param DBUser $user The user object to work with.
     *
     * @return mixed The setting.
     */
    // TODO This is what we replace the above getLicSetting with once the configs have been fixed.
    public function getLicSetting($key, $user = null){
        $lic_jur = licJur($this->getUsrId($user));
        $lic_province = phive('Licensed')->getLicCountryProvince(cu($user), null, 'province_regulated_gameplay');
        $def_jur = 'DEFAULT';
        $def_ss  = (array)$this->getSetting('licensing')[$def_jur];
        $jur_ss  = (array)$this->getSetting('licensing')[$lic_jur];
        $province_ss = (array)$this->getSetting('licensing')[$lic_province];

        return $province_ss[$key] ?? $jur_ss[$key] ?? $def_ss[$key];
    }

    /**
     * Get jurisdiction of the player or the tournament jurisdiction for URL parameter
     *
     * @param user_id user id to fetch settings
     *
     * @return mixed
     */
    public function getJurisdiction($user_id, $key = 'environment')
    {
        if ($this->isTournamentMode()) {
            return $this->getLicSetting('bos-country', $user_id);
        }
        return $this->getLicSetting($key, $user_id);
    }

    /**
    *   @param setting String
    *   @param user    Mixed
    *   @param platform boolean
    *
    *   Helper function to get Lic settings with platform
    *   @return mixed The value of the setting or false
    */
    public function getLicSettingWithPlatform($setting, $platform = 'desktop', $user)
    {
        $setting = self::getLicSetting($setting, $user);
        if ($platform === false) {
            $platform = !phive()->isMobile() ? 'desktop' : 'mobile';
        }
        return $setting[$platform] ?? $setting;
    }

    /**
     *  GET REALITY CHECK PARAMETERS
     *  Adds common parameters, extra regulator parameters and maps the names
     *  You should not modify this function, instead modify the helpers in the providers class
     * @param DBUser $user
     * @param bool $encode
     *
     * @return array|bool|mixed
     */
    public function getRealityCheckParameters($user, $encode = true, $params_required = [])
    {
        $regulator = licJur($user); // The License
        $commonRcParameters = $this->getRcParamsCommon($user, $encode); // rcInterval, rcHistoryUrl, rcLobbyUrl

        // if no rcInterval is returned it means that no reality check should be in place
        if ( empty($commonRcParameters) ) {
            return  [];
        }

        if(isLogged($user)){
            $lic_params = lic('getRcParams', [$user], $user);
            $lic_params = !$lic_params ? [] : (array)$lic_params;
        } else {
            $lic_params = [];
        }

        $rcParams = array_merge($commonRcParameters, $lic_params, (array)$this->addExtraLicenseRcParams($regulator));

        $rcParams = $this->addCustomRcParams($regulator, $rcParams);
        $rcParams = $this->mapRcParameters($regulator, $rcParams);

        // here we're checking if we want to immediately filter the params withour having to do it into the gp class directly
        $rcParams = $this->filterRcParameters($rcParams, $params_required);

        return $rcParams;
    }

    /**
     * Define this function inside a "Provider" class if you need to add extra parameter for that provider (Ex. Thunderkick.php, "sga links")
     * This function is just a fallback to not have to define this on every GP.
     *
     * @param $regulator
     * @return array
     */
    protected function addExtraLicenseRcParams($regulator) {
        return [];
    }

    /**
     * Define this function inside a "Provider" class if you need to override some parameter for that provider (Ex. Thunderkick.php, rcInterval * 60)
     * This function is just a fallback to not have to define this on every GP.
     * @param $regulator
     * @param $rcParams
     * @return mixed
     */
    protected function addCustomRcParams($regulator, $rcParams) {
        return $rcParams;
    }

    /**
     * Define this function inside a "Provider" class if you need to map our system variable names with the one from the provider (Ex. Thunderkick.php, 'spelpausLink' => 'sga-link1')
     * This function is just a fallback to not have to define this on every GP.
     *
     * @param $regulator
     * @param $rcParams
     * @return mixed
     */
    protected function mapRcParameters($regulator, $rcParams) {
        return $rcParams;
    }

    /**
     * This will get the array passed and return a filtered array with only the keys specified in this class's
     * property, rc_keys_required
     * @param $rcParameters
     * @param $params_required The keys to keep.
     * @return array
     */
    public function filterRcParameters($rcParameters, $params_required = [])
    {
        if(empty($params_required)){
            return $rcParameters;
        }

        return array_filter($rcParameters, function($key) use($params_required){
            return in_array($key, $params_required);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Set common settings to be available in all the class
     * @param $forceIso - if this param is passed the "iso" will be forced to the value provided (Ex. Bsg parse jackpot for 2 jurisdiction)
     */
    protected function setCommonInfoForUrl($forceIso = null)
    {
        $this->iso = licJur(cu());
        // checking that iso is not false (License module not loaded)
        if(!empty($this->iso) && phive('Licensed')->isActive($forceIso)) {
            $this->iso = $forceIso;
        }
        $this->platform = phive()->isMobile() ? 'mobile' : 'desktop';
        $this->demo_or_real = isLogged() ? 'real' : 'demo';
        $this->fallback_iso = phive('Licensed')->getSetting('default_jurisdiction'); // we get the value from the config files
    }

    /**
     * Define this function inside a "Provider" class if you need to set specific params specific to that Provider
     * usually params for the launch url (Ex. Bsg.php setBankId() )
     */
    protected function setProviderInfoForUrl()
    {

    }

    /**
     * Determine which launch url should be used.
     * 1) desktop / mobile  ['launch_url'=> ['desktop' => 'url']]
     * 2) real / demo       ['launch_url'=> ['desktop' => ['real' => 'url']]]
     *
     * if extra logic need to be applied this can be extended/overridden into the Provider class
     */
    protected function setLaunchUrl()
    {
        $launchUrl = $this->getLicSetting('launch_url');

        // we want wither the demo/real or desktop/mobile link
        if(is_array($launchUrl)) {

            // 1) some have a desktop and a mobile version
            if (key_exists($this->platform, $launchUrl)) {
                $launchUrl = $launchUrl[$this->platform];
            } else if (key_exists($this->demo_or_real, $launchUrl)) { // 2) other have a different link based on platform for the demo and real version
                $launchUrl = $launchUrl[$this->demo_or_real];
            }

        }

        $this->launch_url = $launchUrl;
    }


    /**
     * Prepare the shared variables that will be used when creating the launcher URL
     * The license iso by default will be taken from the current User in session, but if a command comes via CLI we can force it (Ex. Bsg.php)
     * if some custom parameter need to be overridden / defined for a single Provider use the "setProviderInfoForUrl"
     *
     * @param $forceIso - iso code for the regulation, from User session or forced Ex. 'MT' / 'SE'
     */
    protected function initCommonSettingsForUrl($forceIso = null)
    {
        $this->setCommonInfoForUrl($forceIso); // KEEP THIS FIRST!!!
        $this->setProviderInfoForUrl();
        $this->setLaunchUrl();
    }

    /**
     * Return the proper link with the query string params in the url
     *
     * !!! before calling this function the "initCommonSettingsForUrl" must be called to set the variables
     *
     * @param $params
     * @return string
     */
    protected function getLaunchUrl($params = [], $replacer = [])
    {
        if(!empty($replacer)) {
            if($replacer['type'] === 'sprintf') {
                $this->launch_url = call_user_func_array('sprintf', array_merge([$this->launch_url], $replacer['values']));
            }
        }
        $qs = '';
        if (count($params)) {
            $qs = '?' . http_build_query($params);
        }
        return $this->launch_url . $qs;
    }

    /**
     * Lic router for GP specific methods
     *
     * @param string $country The country / jurisdiction
     * @param string $base_method The method name without the country postfix.
     * @param array $args=[] The args to call the method with.
     *
     * @return mixed The result of the method invocation or null if no method was found.
     */
    public function lic($country, $base_method, $args = []){
        $method = $base_method.$country;
        if(!method_exists($this, $method)){
            return null;
        }
        return call_user_func_array([$this, $method], (array)$args);
    }

    /**
    *  @param $platform  desktop | mobile
    *  @param $user
    *
    *  @return if nothing is specified in the XXXX.config.php (Ex. Thunderkick) we fallback to our own implementation "videoslots"
    *  - ingame - The game will use the implementation from the provider
    *  - videoslots - We will show our popup and stop/resume gameplay via postMessages
    *  - redirect  - We will reload the page to stop the game and then show our popup
    */
    public function getRcPopup($platform = '', $user = null)
    {
        return $this->getLicSettingWithPlatform('rc-popup', $platform, $user) ?: 'videoslots';
    }

    /**
     * Default message to be returned if the RC limit is reached, only for Provider with "do_backend_rc" setting = true
     * The message will be fired even inside the lgalimit WS, in case the game support postMessages we can hook on this to stop the game.
     *
     * This function need to be overridden in the Provider if we want to return an error message (Ex. Bsg.php)
     *
     * Ex.
     * return false = no error
     * return 'rc.limit.reached' = i will check for that string to trigger some error response (Bsg.php)
     *
     * @param $user
     * @param $res
     * @param $device_type
     * @return string
     */
    function rcReached($user, $res, $device_type) {
        return false;
    }

    /**
     * Generate a suitable DOM ID based on an alias.
     *
     * @param string $str The alias name.
     * @param string $parent_suffix The parent suffix to prepend to the ID.
     * @return string The generated DOM ID with BEM methodology.
     */
    function generateDOMId($str, $parent_suffix) {
        $cleaned_str = strtolower(preg_replace('/[^a-z0-9]+/', '-', $str ));
        return $parent_suffix . '__' . $cleaned_str;
    }

    function setJsVars($site_type){ ?>
    <script>
     var cur_lang = '<?php echo phive('Localizer')->getCurNonSubLang() ?>';
     var default_lang = '<?php echo phive('Localizer')->getDefaultLanguage() ?>';
     var cur_country = '<?php echo cuCountry('', false) ?>';
     <? $user = cu(); ?>
     var cur_province = '<?php echo $user ? $user->getProvince() : null ?>';
     var cur_cur = '<?php echo ciso() ?>';
     var siteType = '<?php echo $site_type ?>';
     var cur_domain = '<?php echo strtolower(phive()->getSetting('domain')) ?>';
     var cookie_domain = '<?php echo getCookieDomain() ?>';
     var cookie_secure = <?php echo phive()->getJsBool(phive()->getSetting('cookie_secure')) ?>;
     var cookie_info_notification = '<?= phive('Cookie')->isCookieEnable() ? 'true' : 'false'; ?>';
     var brand_name = '<?= phive('BrandedConfig')->getBrand() ?>';
     var withdrawal_forfeit_brands = '<?= json_encode(phive('BrandedConfig')->getWithdrawalForfeitBrands()) ?>';
     var is_old_design = <?php echo phive()->getJsBool(phive()->getSetting('old_design')) ?>;
     var cur_time = new Date('<?php echo date('Y-m-d').'T'.date('H:i:s') ?>');
     var userId = parseInt('<?php echo $_SESSION['mg_id'] ?>');
     var media_service_url = '<?php echo getMediaServiceUrl(); ?>';
     var mboxMsgTitle = "<?php et('msg.title') ?>";
     var mboxDialogTitle = "<?php et('confirm.title') ?>";
     <?php // Temporary variable, will be removed in the future, see CH22091 for more details. ?>
     var enableMobileSplitMode = <?php echo phive()->getJsBool(phive()->getSetting('enable_mobile_split_game', true)) ?>;
     var registration_step1_url = "/registration-step-1/";
     var registration_step2_url = "/registration-step-2/";
     var idscan_desktop_url = "/registration-idscan";
     var registration_mode = "<?= registrationMode() ?>";
     var is_auth_allowed = '<?php echo phive('DBUserHandler')->isRegistrationAndLoginAllowed() ?>';
    </script>
    <?php
    }

    public function getHomeRedirectInIframe($parameter = false, $prefix = null, $game_ref = null) {
        $key = "go_to_main_website";
        $param = "$key=true";

        if ($parameter) {
            if (empty($this->getSetting('redirect_in_iframe')) || empty($game_ref)) {
                return "";
            }

            $game_data = phive('MicroGames')->getByGameRef($game_ref);

            if (!phive('MicroGames')->gameInIframe($game_data)) {
                return "";
            }

            return !empty($prefix) ? $prefix . $param : $param;
        }

        if(!empty($_GET[$key])) {
            ?>
            <style>
                #wrapper-container {
                    display: none !important;
                }
            </style>
            <script>
                window.top.location.href = window.location.origin + window.location.search.replace(/[\?&]go_to_main_website=[^&]+/, '').replace(/^&/, '?');
            </script>
            <?
        }
    }

    /**
    * @param $rc_interval - minutes
    * @param $elapsed_time - seconds
    * @param user - DBUser
    *
    * @return seconds remaining to display reality check
    */
    public function getTimeRemaining($rc_interval = 0, $elapsed_time = 0, $user = null)
    {
        if (empty($user) && empty($user = cu($user))) {
            return 0;
        }

        if (empty($rc_interval)) {
            $rg = rgLimits();
            $rc_interval = $rg->getRcLimit($user)['cur_lim'];
        }

        if (empty($elapsed_time)) {
            $elapsed_time = lic('rcElapsedTime', [$user], $user);
        }

        return $elapsed_time > 0 ? ($rc_interval * 60) - $elapsed_time % ($rc_interval * 60)  : $rc_interval * 60;
    }

    /**
     *
     * We use this to retrieve a settings under all the different jurisdictions
     * for example: getAllJurSettingsByKey('jp_url') to retrieve all the jps urls throughout all the
     * jurisdictions
     *
     * @param string $key - the key to retrieve from all the jurisdictions
     *
     * @return array - all the key values
     */
    public function getAllJurSettingsByKey($key): array
    {
        if (empty($key)) {
            return [];
        }

        $all_keys = [];
        $all_jurisdictions = $this->getSetting('licensing');

        foreach($all_jurisdictions as $jurisdiction => $val){
            if (!empty($val[$key])) {
                $all_keys[$jurisdiction] = $val[$key];
            }
        }

        return $all_keys;
    }

    public function getSpecificJurSettingsByKey($key, ?array $skip_jurisdictions = []): array
    {
        if (empty($key)) {
            return [];
        }

        $all_keys = [];
        $all_jurisdictions = $this->getSetting('licensing');

        foreach($all_jurisdictions as $jurisdiction => $val){
            if (!empty($val[$key]) && !in_array($jurisdiction, $skip_jurisdictions)) {
                $all_keys[$jurisdiction] = $val[$key];
            }
        }

        return $all_keys;
    }

    /**
     * This setting will apply only for GP that previously had a custom format for the transactions "mg_id".
     *
     * Will return TRUE if we need to apply the Standardized format to the transactions based on a licSetting
     * "standardize_txn_from" - this is a full datetime "2020-02-25 12:00:00".
     *
     * @param $user_id
     * @return bool
     */
    public function isStandardTransactionFormat($user_id)
    {
        $standardize_txn_from = $this->getLicSetting('standardize_txn_from', $user_id);

        return !empty($standardize_txn_from) && (phive()->hisNow() >= $standardize_txn_from);
    }

    /**
     * Used for providers to get the part of the transaction format, typically we want the classname, however
     * instance like yggdrasil require us to override that with some other string.
     *
     * @return mixed|string|null
     */
    public function getNetworkForTransaction()
    {
        return $this->getSetting('override_transaction_network') ?? strtolower(get_called_class());
    }

    /**
     * We check if the current user Jurisdiction is the default one
     *
     * @param null $jur - OPT - if empty we check for class variable "uid"
     * @return bool
     */
    public function isDefaultJurisdiction($jur = null)
    {
        $jur = $jur ?? licJur($this->uid);
        return $jur === phive('Licensed')->getBaseJurisdiction();
    }

    /**
     * Return the format that should be used for the current provider, we check for:
     * - the current user Jurisdiction
     * - if the GP has "standardize_txn_from" setting and isStandardTransactionFormat is:
     *   - TRUE - we use standardized format
     *   - FALSE - we use the old custom format ("old_format")
     * - if "old_format" is empty it means that is a GP that was already on standardized format
     *
     * If a specific format is passed, the format will be returned
     *
     * @param array $format - OPT - if we want to force a format
     * @return array
     */
    private function getTxnFormat($format = [])
    {
        if (!empty($format)) {
            return $format;
        } else if ($this->isStandardTransactionFormat($this->uid)) {
            $txn_format = $this->isDefaultJurisdiction() ? $this->standard_txn_format : $this->standard_txn_format_with_jur;
        } else {
            $old_format = $this->getLicSetting('old_format', $this->uid);
            if (!empty($old_format)) {
                $txn_format = $old_format;
            } else {
                $txn_format = $this->isDefaultJurisdiction() ? $this->standard_txn_format : $this->standard_txn_format_with_jur;
            }
        }

        return $txn_format;
    }

    /**
     * Will return the array with the replacement that we need to do to format the transaction string "mg_id".
     *
     * @param null $raw_provider_txn_id
     * @return array
     */
    private function getTxnFormatData($raw_provider_txn_id = null)
    {
        $jur = licJur($this->uid);

        $txn_format_data = [
            'NET' => $this->getNetworkForTransaction(),
            'TXN' => $raw_provider_txn_id ?? '',
            'JUR' => !$this->isDefaultJurisdiction() ? $jur : ''
        ];

        return array_filter($txn_format_data);
    }

    /**
     * Normalized in the sense that we want to 'uniqueify' the transaction id so as to avoid collisions in the future.
     * This is achieved by getting the format from either the property or lic setting looping through that and
     * replacing specific tags as defined by the $to_replace key and value this way we can create formats like
     *
     * NET_JUR_TXN or NET_TXN or NETTXN-JUR
     *
     * so long as the key words are:
     * NET: network (Ex. qspin)
     * JUR: jurisdiction (Ex. GB)
     * TXN: transaction id (Ex. 123123)
     *
     * These can be in any order (typically NET in the front)
     *
     * @param $raw_provider_txn_id : as it received by us from the provider
     * @param $format - OPT : if we want to force a specific format
     * @return string : the unique id as it will be saved in our db
     */
    public function getNormalizedTxnId($raw_provider_txn_id, $format = [])
    {
        $txn_format = $this->getTxnFormat($format);

        $to_replace = $this->getTxnFormatData($raw_provider_txn_id);
        foreach($txn_format as &$txn_token) {
            $txn_token = str_replace(array_keys($to_replace), array_values($to_replace), $txn_token);
        }

        return implode('', $txn_format);
    }

    /**
     * Extract the raw transaction if from the "mg_id" stored in the DB.
     * Ex. qspin_GB_123 => 123
     *
     * @param $mg_id
     * @return string|string[]
     */
    public function getRawIdFromMgId($mg_id)
    {
        $txn_format = $this->getTxnFormat();

        $raw_txn_id = $mg_id;

        $to_extract = $this->getTxnFormatData();

        foreach ($txn_format as &$txn_token) {
            if($txn_token != 'TXN') {
                $txn_token = str_replace(array_keys($to_extract), array_values($to_extract), $txn_token);
                $raw_txn_id = str_replace($txn_token, '', $raw_txn_id);
            }
        }

        return $raw_txn_id;
    }


    /**
     * This is shared function that will be used for all providers where we
     * check for the user
     * check if the user is in a tournament
     * returns appropriate country for either conditions
     *
     * @param null $demo_code
     * @param null $user
     * @return string
     */
    protected function getPlayerCurrencyForGame($demo_code = null, $user = null)
    {
        $user = ud($user);
        if(!empty($user)) {
            $currency = !empty($this->t_entry) ? phive('Tournament')->curIso() : $user['currency'];
        } else { // demo
            $currency = $demo_code ?? ciso();
        }

        return $currency;
    }

    /**
     * Get user id from the token part, it should be prefixed with u, ex: 123uasdf3244...
     * @param string $token
     * @return string|array
     */
    public function getUidFromToken($token)
    {
        if (strpos($token, 'u') === false) {
            return '';
        }
        return explode('u', $token)[0];
    }

    /**
     * Get UUID version 4 value with dashes removed.
     * @param int $uid The user ID
     * @return string
     */
    public function getGuidv4($uid = null)
    {
        $prefix = empty($uid) ? '' : "u$uid";
        return $prefix . str_replace('-', '', phive()->uuid());
    }

    /**
     * Wrapper where don't want any thousands seperator, we simply want a value such as 1000.00
     *
     * @param $value
     * @return string|null
     */
    public function nf2TwoDec($value)
    {
        return nf2($value, true, 1, '.', ''); // note the empty last param

    }

    /**
     * Acquires the lock
     * Call it at the start of the synchronized block code
     *
     * @param string $key The key to set data under.
     * @param int $uid The user id to use
     * @param int $expire time in seconds. When the lock will be released if releaseLock is not called to deal with dead lock situations
     *
     * @return void
     */
    public function acquireLock($key, $uid = null, $expire = 1)
    {
        $unique_key = mKey($uid, $key);
        $i = 0;
        while (!phMsetNx($unique_key, 1, $expire)) {
            usleep(min(pow(2, $i++) * 10000, 100000));
        }
    }

    /**
     * Releases the lock
     * Call it at the end of the synchronized block code
     *
     * @param string $key The key data is set under.
     * @param int $uid The user id to use
     */
    public function releaseLock($key, $uid = null)
    {
        phMdelShard($key, $uid);
    }

    /**
     * Triggered after the provider sends us the END ROUND flag to be able to run any logic after finishing rounds
     * @param $user
     */
    public function onGameRoundFinished($user)
    {
        $user = cu($user);
        $this->checkNextRound($user);
    }

    /**
     * Use the external session balance if we are not in tournament and License has this feature
     *
     * @param $user
     * @param $action
     * @return bool
     */
    public function useExternalSession($user): bool
    {
        return lic('hasGameplayWithSessionBalance', [$this->isTournamentMode()], $user) === true;
    }

    /**
     * Setting a key on redis for an user is playing a spin, for 20 seconds
     *
     * @param $userId
     * @param $ttl
     * @return void
     */
    public function setPlayerIsPlayingAGame($userId, $ttl = 60): void
    {
        $cur_player = cu($userId);
        cuMset( 'is_playing', 1, $cur_player, $ttl);
    }

    /**
     *  Checking if the player has a spin session on redis running and waiting for 20 seconds after
     * @param $userId
     * @param $ttl
     * @return bool
     *
     */
    public function checkPlayerIsPlayingAGame($userId): bool
    {
        $status = phMgetShard('is_playing', $userId, 0);

        if ($status == '1') {
            return true;
        }
        return false;
    }

    /**
     * This function is used to save the javascripts logs into the file via logger and called after the post call from the javascript.
     *
     * @param array $data
     * @return array
     */
    public function saveFELogs(array $data)
    {
        if (empty($data)) {
            return ['success' => false, 'result' => []];
        }

        $context = $data['context'] ?? [];
        if (!empty($data['context_obfuscation']) && ($data['context_obfuscation']['obfuscation'] === 'true')) {
            $context = $this->obfuscateArray($context, $data['context_obfuscation']['obfuscating_keys'] ?? []);
        }

        $user = cu();
        phive('Logger')->getLogger($data['logger'])->{$data['type']}('JS_LOGS::'.$data['message'], [
                'user_id' => $user ? $user->userId : '',
                'context' => $context
        ]);

        return ['success' => true, 'result' => []];
    }

    /**
     * Generate number of messages equal to number of spins used during Free Spins session.
     *
     * @param int $bonus_entry_id
     * @param int $user
     */
    public function propagateFreeSpinsBets(int $bonus_entry_id, int $user): void
    {
        $user = cu($user);
        $bonus_entry = phive('Bonuses')->getBonusEntry($bonus_entry_id);
        $bonus_type = phive('Bonuses')->getBonus($bonus_entry['bonus_id']);
        $number_of_spins = $bonus_entry['frb_granted'];
        $bonus_amount = $bonus_type['bonus_type'] === 'freespin' ? $bonus_type['frb_cost'] : $bonus_type['reward'];
        $cost_of_spin = $bonus_amount / $number_of_spins;
        $device_type = phive()->isMobile() ? 1 : 0;
        $game_ref = $this->db->readOnly()->getValue("SELECT ext_game_name FROM micro_games WHERE game_id = '{$bonus_type['game_id']}'");

        $message = [
            'balance' => (int) $user->getBalance(),
            'trans_id' => 0,
            'amount' => (int) $cost_of_spin,
            'game_ref' => $game_ref,
            'mg_id' => '',
            'user_id' => (int) $user->getId(),
            'bonus_bet' => 3,
            'op_fee' => 0,
            'jp_contrib' => 0,
            'currency' => $user->getCurrency(),
            'device_type' => $device_type,
            'event_timestamp' => time()
        ];

        for ($i = 0; $i < $number_of_spins; $i++) {
            /** @uses Licensed::addRecordToHistory() */
            lic(
                'addRecordToHistory',
                [
                    'bet',
                    new BetHistoryMessage($message)
                ],
                $user
            );
        }
    }

    /**
     * Generate end session message at the end of free spins session that didn't result in any win.
     * In this scenario game session is not being created and is not being inserted into the table on our side.
     * This method allows us to send message to the reporting service and report a fs session.
     *
     * @param int $user_id
     * @param string $game_id
     * @param int $device_type_num
     * @param string $start_time
     * @param string $end_time
     */
    public function propagateFreeSpinsEndSession(
            int $user_id,
            string $game_id,
            int $device_type_num,
            int $start_time,
            int $end_time
    ): void
    {
        $game_ref = $this->db->readOnly()->getValue("SELECT ext_game_name FROM micro_games WHERE game_id = '{$game_id}'");

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory',
            [
                'end_session',
                new EndSessionHistoryMessage(
                        [
                            'game_session_id' => 0, //No game session in our database
                            'user_id'         => $user_id,
                            'game_ref'        => $game_ref,
                            'device_type'     => $device_type_num,
                            'start_time'      => date('Y-m-d H:i:s', $start_time),
                            'end_time'        => date('Y-m-d H:i:s', $end_time),
                            'is_tournament'   => $this->isTournamentMode(),
                            'event_timestamp' => time(),
                        ]
                )
            ],
            $user_id);
    }

    function getLastGameSession(array $ins)
    {
        $str = "
            SELECT * FROM users_game_sessions
            WHERE user_id = {$ins['user_id']}
            AND game_ref = '{$ins['game_ref']}'
            AND (end_time = '0000-00-00 00:00:00' OR start_time IS NOT NULL)
            AND device_type_num = {$ins['device_type']} ORDER BY start_time DESC";

        return phive('SQL')->sh($ins)->loadAssoc($str);
    }

    private function getLatestBetBySessionID($game_session_id, $user_id, $ext_round_id, $gp)
    {
        $ext_round_id = strpos($ext_round_id, $gp) === false ? "{$gp}_{$ext_round_id}" :  $ext_round_id;
        $str = "
            SELECT 
                ugsb.bet_id, ugsb.created_at 
            FROM user_game_session_bets ugsb
            WHERE 
                ugsb.session_id = {$game_session_id} AND ugsb.ext_round_id = '{$ext_round_id}'
            ORDER BY ugsb.id DESC LIMIT 1
        ";
        $result = phive('SQL')->sh($user_id)->loadAssoc($str);

        // In some cases, a user places a bet within a session but refreshes the game,
        // causing the win result to be associated with the next session.
        // Therefore, if no matching bet is found for the current session,
        // we fall back to retrieving the most recent bet by round ID, regardless of session.
        if (empty($result)) {
            $str = "
                SELECT 
                    ugsb.bet_id, ugsb.created_at 
                FROM user_game_session_bets ugsb
                WHERE 
                    ugsb.ext_round_id = '{$ext_round_id}'
                ORDER BY ugsb.id DESC LIMIT 1
            ";
            $result = phive('SQL')->sh($user_id)->loadAssoc($str);
        }

        return $result;
    }

    public function getUserGameSessionBets($brand_round_id = null, $bet_id = null)
    {
        $user_id = $this->cur_user->getId();

        $conditions = ["ugs.user_id = $user_id"];

        if (!empty($brand_round_id)) {
            $conditions[] = "ugsb.ext_round_id = '$brand_round_id'";
        }

        if (!empty($bet_id)) {
            $conditions[] = "ugsb.bet_id = $bet_id";
        }

        $whereClause = implode(' AND ', $conditions);

        $query = "
            SELECT
                ugsw.win_id win_id,
                ugsb.bet_id bet_id,
                ugs.user_id user_id,
                ugsb.session_id session_id,
                ugsb.ext_round_id ext_round_id
            FROM user_game_session_bets ugsb
            LEFT JOIN users_game_sessions ugs ON ugsb.session_id = ugs.id
            LEFT JOIN user_game_session_wins ugsw ON ugsb.bet_id = ugsw.bet_id
            WHERE $whereClause
        ";

        return $this->db->sh($this->cur_user)->loadArray($query);
    }
    
    private function forfeitUnactivatedWelcomeBonuses(int $user_id) :void
	{
		if (phive('BrandedConfig')->getBrand() !== BrandedConfig::BRAND_MEGARICHES) {
            return;
		}

		// get GB welcome bonus type id, currently 39011
		$ukWelcomeBonusTypeId = phive('Bonuses')->getBonus(phive('Config')->getValue('license-gb', 'first-deposit-bonus-id', 0));
		if (empty($ukWelcomeBonusTypeId) || empty($ukWelcomeBonusTypeId['id'])) {
			return;
		}

		// check if the user has received this bonus and if it has been activated
		$bonus_entry = phive('CasinoBonuses')->getBonusEntriesByUserAndBonusId($user_id, $ukWelcomeBonusTypeId['id'], 1);
		if (empty($bonus_entry['status']) || $bonus_entry['status'] != 'pending') {
			return;
		}

		$message = 'Failing bonus because welcome offer was not activated';
		$result = phive('Bonuses')->fail($bonus_entry['id'], $message, $user_id);
        
        if (!$result) {
            phive('Logger')->getLogger('casino')->error(
                'Failed to fail welcome bonus for user with welcome bonus not activated',
                [
                    'user_id' => $user_id,
                    'bonus_entry_id' => $bonus_entry['id']
                ]
            );
        }
	}
}
