<?php
use Videoslots\HistoryMessages\BetsRollbackHistoryMessage;
use Videoslots\HistoryMessages\WinsRollbackHistoryMessage;

require_once __DIR__ . '/Casino.php';

class Trtype {
    const BET = 'bets';
    const WIN = 'wins';
    const JP_WIN = 'jp_win';
    const FRB_BET = 'frb_bet';
    const BONUS_PAYOUT = 'bonus_payout';
    const OTHER_PAYOUT = 'other_payout';

    /**
     * PARTIAL WINS:
     *
     * Some game providers like Leander sends a request for each win during the free spins rounds.
     */
    const FRB_WIN_PARTIAL = 'frb_partial_win';

    /**
     * WIN PARTIAL OF TOTAL:
     *
     * Some game providers like Pragmatic sends a request for each win during the free spins rounds,
     * but also sends 1 bonus call at the end of the free spins with the total sum of the wins.
     *
     * In this particular scenario we should mark each partial win `FRB_WIN_PARTIAL_OF_TOTAL`, and
     * send the last bonus request with `FRB_WIN_TOTAL` to phive.
     *
     */
    const FRB_WIN_PARTIAL_OF_TOTAL = 'frb_partial_of_total_win';

    /**
     * TOTAL WIN:
     *
     * Some game providers like Evolution sends only one win call at the end of the free spins rounds.
     */
    const FRB_WIN_TOTAL = 'frb_total_win';

    /*
     * PROMO_WIN_CJP:
     *
     * Community Jackpot is a promotion offered by Pragmatic
     * When a player wins the Jackpot, all participating players receive a small share of the prize (async)
     */
    const PROMO_WIN_CJP = 'promo_win_cjp';

    /*
     * PROMO_WIN_PRIZEDROP:
     *
     * Prize Drop is a promotion offered by Pragmatic
     * Random player can win a reward during promo period for selected games (async)
     */
    const PROMO_WIN_PRIZEDROP = 'promo_win_prizedrop';

    /**
     * FRB CONFIRMATION:
     *
     * Use this constant if the game provider allows the system to identify the last win request,
     * or sends a confirmation request after all rounds have concluded.
     *
     * Additionally, mark partial wins with `FRB_FINISHED=false` and last/confirmation request
     * with `FRB_FINISHED=true`.
     */
    const FRB_WITH_CONFIRMATION = 'frb_with_confirmation';
}

class Errtype {
    const RETRY  = 1;
    const UNKNOWN = 2;
    const NO_FUNDS = 3;
    const AUTH_FAIL = 4;
    const GENERAL = 5;
    const FATAL = 6;
    const COMMUNICATION = 7;
    const NO_BRAND = 8;
    const INVALID_BONUS = 9;
    const NO_USER = 10;
    const NO_GAME = 11;
    const NO_SESSION = 12;
    const NO_TRANSACTION = 13;
    const DB_ERROR = 14;
    const TEMPORARY = 15;
    const INVALID_TOKEN = 16;
    const ACCOUNT_LOCKED = 17;
    const INVALID_PARAMETER = 18;
    const BET_DOES_NOT_EXIST = 19;
    const NO_FUNDS_FOR_TIPS = 20;
    const ACTION_FAILED = 21;
    const GEOLOCATION_FAIL = 22;
    const BONUS_LIMIT_EXCEEDED = 23;
    const OPERATION_IN_PROCESS = 24;
    const WRONG_ENVIRONMENT = 25;
    const MISSING_PARAMETER = 26;
    const BET_ALREADY_SETTLED = 27;
    const UNSUPPORTED_GAME = 28;
    const ENTITY_ALREADY_EXISTS = 29;
    const ENTITY_MISSING = 30;
    const ENTITY_CANCELED = 31;
    const ENTITY_USED = 32;
    const ENTITY_EXPIRED = 33;
    const INVALID_HASH = 34;
    const REGULATORY_GENERAL = 35;
    const CONSTRAINT_VIOLATION = 36;
    const REGULATORY_RC = 37;
    const MALFORMED_URL = 38;
    const CONFIG_ERROR = 39;
    const SYSTEM_ERROR = 40;
    const TOMBSTONE = 41;
    const DUPLICATE = 42;
    const INSUFFICIENT_BALANCE = 44;
    const CANNOT_RB_BET_WITH_WIN = 45;
    const INFO_MISMATCH = 46;
    const INVALID_CURRENCY = 48;
    const UKGC_MAX_BET_VIOLATION = 49;
    const DUPLICATE_WIN = 50;
    const WIN_WITH_FAILED_BET = 51;
    const INVALID_SESSION_ID = 52;
    const BOS_ROLLBACK_FAIL = 53;
    const RG_LIMIT_NO_BALANCE = 55;
    const CANNOT_RB_WIN = 54;
}

class GprFields {
    const AMOUNT  = 'amount';
    const GAME_ID = 'game_id';
    const GAME_LAUNCH_ID = 'game_launch_id';
    const CURRENCY = 'currency';
    const BRAND_BONUS_ID = 'brand_bonus_id';
    const BRAND_BONUS_ENTRY_ID = 'brand_bonus_entry_id';
    const GP_BONUS_ID = 'gp_bonus_id';
    const ROUND_ID = 'round_id';
    const ROUND_COUNT = 'round_count';
    const TR_ID = 'transaction_id';
    const RB_TR_ID = 'rollback_gp_transaction_id';
    const RB_TR_TYPE = 'rollback_transaction_type';
    const GAME_SESSION_BETS_SUPPORT = 'game_session_bets_support';
    const TOKEN = 'token';
    const UID = 'user_id';
    const BET_ID = 'bet_id';
    const ROUND_FINISHED = 'round_finished';
    const TR_TYPE = 'transaction_type';
    const GP_BONUS_ENTRY_ID = 'gp_bonus_entry_id';
    const BALANCE = 'cash_balance';
    const GP_SESSION_ID = 'gp_session_id';
    const GP_GAME_CATEGORY = 'gp_game_category';
    const DO_RB_WIN = 'do_rollback_wins';
    const GAME_LAUNCH_URL = 'game_launch_url';
    const FRB_FINISHED = 'frb_finished';
    const LANGUAGE = 'language';
    const CLIENT = 'client';
    const CHANGE_TOKEN = 'change_token';
    const NEW_TOKEN = 'new_token';
    const JURISDICTION = 'jurisdiction';
    const CHECK_FOR_BET = 'check_for_bet';
    const ERASE_TOKEN = 'erase_token';
    const GAME_IN_IFRAME = 'game_in_iframe';
    const IFRAME_CONTEXT = 'iframe_context';
    const IFRAME_TOKEN = 'iframe_token';
    const MAX_STAKE = 'max_stake';
    const CHECK_FOR_ROLLBACK = 'check_for_rollback';
    // We cannot call `finishPartiallyHandledFrb` with all the game providers during the bet call,
    // If the user gets a win in the last round, that method will set the status of the
    // bonus entry to `failed`, and the system will not save the last win once it receives the request.
    //
    // Use `$args[GprFields::SUPPORTS_HANDLED_FRB_ON_BET] = false;` to prevent failing the bonus entry
    // from the function `finishPartiallyHandledFrb` during the bet request.
    const SUPPORTS_HANDLED_FRB_ON_BET = 'supports_partially_handled_frb_on_bet';
    const CAMPAIGN_ID = 'campaign_id';
    const CAMPAIGN_TYPE = 'campaign_type';

    const CHECK_FOR_DUPLICATE_BET = 'check_for_duplicate_bet';

    const CHECK_FOR_DUPLICATE_WIN = 'check_for_duplicate_win';
    const CHECK_FOR_DUPLICATE_WIN_STRICT = 'check_for_duplicate_win_strict';
    const CANNOT_RB_BET_WITH_WIN = 'cannot_rollback_bet_having_win';
    const ROLLBACK_SINGLE_TRANSACTION = 'rollback_single_transaction';
    const CANNOT_RB_WIN = 'cannot_rollback_win';
    const VALIDATE_CURRENCY = 'validate_currency';
    const VALIDATE_UKGC_MAX_BET = 'validate_ukgc_max_bet';
    const API_TESTING_ENABLED = 'api_testing_enabled';
    const VALIDATE_TOKEN = 'validate_token';

    const MOBILE_GAME_ID_EXT = 'mobile_game_id_extension';
    // After assigning the freespins to the user, the GP will send a request
    // to confirm the activation of the freespins.
    const CONFIRM_FRB_BONUS_ACTIVATION = 'confirm_frb_bonus_activation';

    // When the GPS does not provide a way to identify the last round of free spins,
    // we can track the `frb_remaining` value, decreasing it on each round.
    // If `frb_remaining` reaches 0, it indicates that the free spins have ended.
    const USE_FRB_REMAINING_0_AS_CONFIRMATION = 'use_frb_remaining_0_as_confirmation';
    const IS_BINGO = 'is_bingo';

    const PREVENT_ORIGINAL_GAME_OVERWRITE = 'prevent_original_game_overwrite';
}

class AwardType {
    const WIN = 2;
    const FRB_WIN = 3;
    const JP_WIN = 4;
    const FRB_WIN_WITH_WAGERING = 5;
}

class TransactionType {
    const ADJUST_TYPE = 91;
}

class Gpr extends Casino {

    public $cur_user = null;
    public $cur_udata = [];
    public $cur_game = null;
    public $token_data = [];
    public $req_data = [];
    public $cur_action = null;
    public $token = null;
    public $frb_win = false;
    public $gp = null;
    public $frb_bet = false;
    public $token_duration = 900;

    // public $prefix_char = '_';
    public $track_frb_gps = [
        'stakelogic',
        'playtech',
        'nolimit',
        'thunderkick',
        'skywind',
        'blueprint',
    ];

    public $do_login_call_gps = [
        'evolution',
        'pragmatic',
        'greentube',
        'thunderkick',
        'skywind',
        'pariplay'
    ];

    public $iframe_gps = [
       'evolution'
    ];

    /**
     * Mapping array to replace specific values.
     *
     * @var array
     */
    protected $mapping = [
        'quickfire' => 'microgaming'
    ];

    public $cashier = null;
    public $bonuses = null;

    public function __construct(){
        parent::__construct();
    }

    public function init(){
    }

    public function inject($var_name, $obj){
        $this->{$var_name} = $obj;
    }

    public function getRoundId($args){
        return $this->getToDbGpTransId($args[GprFields::ROUND_ID]);
    }

    public function getGpRoundCount($args){
        return $args[GprFields::ROUND_COUNT] ?? $args[GprFields::ROUND_ID];
    }

    public function getGpLanguage($lang){
        // Our own versions of cl-ES, pe-ES and pt-BR
        $map = [
            'cl' => 'es',
            'br' => 'pt',
            'pe' => 'es'
        ];

        return $map[ $lang ] ?? $lang;
    }

    public function getGpFromGame($game = null){
        return strtolower($game['network'] ?? $this->cur_game['network']);
    }

    public function doTrackFrb($gp = null){
        return in_array($gp ?? $this->gp, $this->track_frb_gps);
    }

    public function hasTrackedFrb($gp = null){
        // We track FRBs and we have one in the session data.
        return $this->doTrackFrb() && !empty($this->token_data['frb_bonus']);
    }

    public function doLoginCall($gp = null){
        return in_array($gp ?? $this->gp, $this->do_login_call_gps);
    }

    public function doIframe($gp = null){
        return in_array($gp ?? $this->gp, $this->iframe_gps);
    }

    public function getGpGameId(?array $game = null){
        return $game['ext_game_name'] ?? $this->cur_game['ext_game_name'];
    }

    public function getBrandClientNum(){
        $gpr_client = $this->token_data[GprFields::CLIENT] ?? $this->req_data[GprFields::CLIENT];
        if(empty($gpr_client)){
            return 0;
        }
        return $gpr_client == 'desktop' ? 0 : 1;
    }

    public function getGame($gp_game_id){
        return phive('MicroGames')->getByGameRef($gp_game_id, $this->getBrandClientNum(), $this->cur_user ?? null);
    }

    public function getGameFromBonusEntry(array $args){
        $bonus_entry = $this->getFrbEntry($args);
        $bonus = phive('Bonuses')->getBonus($bonus_entry['bonus_id']);

        return phive('MicroGames')->getByGameRef($bonus['game_id'], $this->getBrandClientNum(), $this->cur_user ?? null);
    }

    public function getOriginalGameRefFromOverridden() {
        if(!empty($this->token_data['original_game'])){
            return $this->token_data['original_game']['ext_game_name'];
        }

        $jur = licJur($this->cur_user);
        return $this->db->getValue("
            SELECT mg.ext_game_name FROM game_country_overrides gco
            LEFT JOIN micro_games mg on gco.game_id = mg.id
            WHERE gco.ext_game_id = '{$this->cur_game['ext_game_name']}' AND gco.country = '{$jur}'");
    }

    public function _getBalance() {
        if($this->isTournamentMode()){
            return $this->tEntryBalance();
        }

        if($this->tokenMissing()){
            // Tokens for external game sessions don't exist anymore so we just use the current balance.
            $balance = $this->cur_user->getCurAttr('cash_balance');
        } else {
            $balance = $this->useExternalSession($this->cur_user) ? $this->getSessionBalance($this->cur_user) : $this->cur_user->getCurAttr('cash_balance');
        }

        if(empty($this->cur_game)){
            // Rollback context, we don't know which game so we just return zero bonus balance.
            return $balance;
        }

        $gref           = $this->getOriginalGameRefFromOverridden();
        $gref           = empty($gref) ? $this->cur_game['ext_game_name'] : $gref;
        $bonus_balances = phive('Bonuses')->getBalanceByRef($gref, $this->cur_user->getId());

        if ($this->isBet()) {
            $bet_amount = $this->req_data[GprFields::AMOUNT];

            if ($bet_amount > $balance) {
                $this->bonus_bet = $bet_amount <= ($balance + $bonus_balances) ? 1 : 0;
            } else {
                $this->bonus_bet = 0;
            }
        }

        // phive('Logger')->getLogger('casino')->debug('BALANCE BEFORE RG: ', ['balance' => $balance, 'gref' => $gref ?? '', 'game' => $this->cur_game ?? '']);

        return $this->lgaMobileBalance($this->cur_udata, $this->cur_game, $balance + $bonus_balances);
    }


    public function getCurrency(){
        return $this->isTournamentMode() ? phive('Tournament')->curIso() : $this->cur_user->getCurrency();
    }

    public function getBalance($balance = null){
        return [
            'alias'                 => $this->cur_user->getAlias(),
            GprFields::CURRENCY     => $this->getCurrency(),
            GprFields::BALANCE      => $balance ?? $this->_getBalance(),
            GprFields::TOKEN        => $this->token ?? '',
            GprFields::JURISDICTION => $this->licJur($this->cur_user),
            GprFields::UID          => $this->cur_user->getId()
        ];
    }

    public function getFrbInfo($gp = null, $u_obj = null, $get_return_data = true, $game = null){
        if(!$this->doTrackFrb($gp)){
            return [];
        }
        return $this->getFrbCommon($gp, $u_obj, $get_return_data, $game);
    }

    public function getFrbCommon($gp = null, $u_obj = null, $get_return_data = true, $game = null){
        $u_obj     = $u_obj ?? $this->cur_user;
        $gp        = $gp ?? $this->gp;
        $frb_bonus = null;
        $frb_entry = null;
        $game      = $game ?? $this->cur_game;

        $frb_entry_id = $this->req_data[GprFields::BRAND_BONUS_ENTRY_ID] ?? NULL;

        if(empty($frb_entry_id) && !empty($this->token_data['frb_bonus'])){
            // The reason we don't use the Redis data directly here is that we want one source of truth for stuff like frb_remaining etc.
            // so this is effectively a refresh / DB re-fetch.
            $frb_entry = phive('Bonuses')->getBonusEntry($this->token_data[GprFields::BRAND_BONUS_ENTRY_ID]);
            $frb_bonus = $this->token_data['frb_bonus'];
        } else {
            if(!empty($this->req_data[GprFields::BRAND_BONUS_ENTRY_ID])){
                $frb_entry = phive('Bonuses')->getBonusEntry($this->req_data[GprFields::BRAND_BONUS_ENTRY_ID]);
            } else {
                // Deploy URL logic in order to initially fetch the FRB.
                $frb_entry = $this->db->readOnly()->sh($u_obj)->loadAssoc("
                    SELECT * FROM bonus_entries
                    WHERE user_id = {$u_obj->getId()}
                    AND status IN('active', 'approved')
                    AND bonus_type = 'freespin'
                    AND bonus_tag = '$gp'
                    AND frb_remaining > 0");
            }

            if(!empty($frb_entry)){
                $frb_bonus = phive('Bonuses')->getBonus($frb_entry['bonus_id']);
                $game_is_mobile_version = ((int) $game['device_type_num']) !== 0;
                $match_bonus_type_game_id = str_contains($frb_bonus['game_id'], $game['game_id']);

                // For PlayNGo If the $game['game_id'] of the mobile version doesn't match $frb_bonus['game_id'],
                // then check if the desktop version of the game matches the $frb_bonus['game_id'].
                if (!$match_bonus_type_game_id && $game_is_mobile_version) {
                    $game_id_desktop_version = phive('MicroGames')->getDesktopGame($game)['game_id'] ?? '';
                    $match_bonus_type_game_id = str_contains($frb_bonus['game_id'], $game_id_desktop_version);
                }

                if(!empty($frb_bonus) && !empty($frb_bonus['game_id']) && !empty($game) && !$match_bonus_type_game_id){
                    // We have an FRB bonus, we have one or more game ids in that bonus, but the game ids do NOT match, therefore we nullify the entry,
                    // and the bonus otherwise people could maybe use the FRB in other games than intended.
                    $frb_entry = null;
                    $frb_bonus = null;
                }
            }
        }

        if(empty($frb_bonus)){
            return [];
        }

        if($get_return_data){
            return [
                GprFields::GP_BONUS_ID          => $this->getExtFrbIds($frb_bonus),
                GprFields::BRAND_BONUS_ENTRY_ID => $frb_entry['id'],
                'frb_count'                     => $frb_entry['frb_granted'],
                'frb_remaining'                 => $frb_entry['frb_remaining']
            ];
        }

        return [$frb_entry, $frb_bonus];
    }

    public function endSession($args){
        if($args[GprFields::ERASE_TOKEN]){
            $uid   = getMuid($args[GprFields::TOKEN]);
            $uid   = $this->getUsrId($uid);
            $u_obj = cu($uid);
            phMdel($args[GprFields::TOKEN]);
            if(!empty($u_obj)){
                lic('endOpenParticipation', [$u_obj], $u_obj);
            }
        }

        $ret = [
            GprFields::TOKEN => $args[GprFields::TOKEN],
        ];

        if (!empty($args[GprFields::TOKEN])) {
            $this->setTokenGameUser($args);

            $ret[GprFields::CURRENCY] = $this->getCurrency();
            $ret[GprFields::BALANCE] = $this->_getBalance();

            if ($args[GprFields::JURISDICTION]) {
                $ret[GprFields::JURISDICTION] = $args[GprFields::JURISDICTION];
            }
        }

        return $ret;
    }

    /**
     * @param $args
     * @return array
     */
    public function endRound($args): array
    {
        if(!empty($args[GprFields::ROUND_ID]) && !empty($this->cur_user)) {
            $rounds = $this->getUserGameSessionBets($this->getRoundId($args));
            $rounds = reset($rounds);

            if (!empty($rounds)) {
                $round_id = $rounds['ext_round_id'];
            }
        }


        if($args['gp'] === 'greentube'){
            $created_at = $this->getBets($this->cur_user, '1')[0]['created_at'] ?? date('Y-m-d H:i:s');
            return $this->getBalance() + ['created_at' => $created_at];
        }

        $balance_arr = $this->getBalance();

        if(!empty($round_id)){
            $balance_arr[GprFields::ROUND_ID] = $round_id;
        }

        return $balance_arr;
    }
    public function refreshToken($args){
        phM('expire', $this->token, $this->token_duration);
        return $this->getBalance();
    }

    public function authenticate($args){
        $jurisdiction = $this->licJur($this->cur_user);
        $user_arr = $this->cur_udata;

        // Requirement by ex MicroGaming
        if(!empty($args[GprFields::CHANGE_TOKEN])){
            $this->changeSessionToken($args);
        }

        if(!$this->isTournamentMode()){
            // Spain and Italy happens here, but only if we're NOT playing BoS
            $res = $this->setupExternalGameSession($this->token, true);
            if($this->isError($res)){
                return $res;
            }
        } else {
            //fixing Quick spin and Auto spin for UK and SE users in Tournament Mode
            if ((($user_arr['country'] == "GB") || ($user_arr['country'] == "SE")) && ($args['gp'] == "playngo")) {
                $user_arr['country'] = "MT";
            }
        }

        // phive('Logger')->getLogger('casino')->debug('EXT SESS ENTRY: ', [$this->session_entry]);

        $ret = array_merge([
            'alias'             => $this->cur_user->getAlias(),
            'city'              => $user_arr['city'],
            'dob'               => $user_arr['dob'],
            'register_date'     => $user_arr['register_date'],
            'region'            => $this->cur_user->getProvince(),
            'token'             => $this->token,
            'jurisdiction'      => $jurisdiction,
            GprFields::LANGUAGE => $this->getGpLanguage($user_arr['preferred_lang']),
            'country'           => $user_arr['country'],
            'iso3_country'      => phive('Cashier')->getIso3FromIso2($user_arr['country']),
            'iso3_jurisdiction' => phive('Cashier')->getIso3FromIso2($jurisdiction),
            GprFields::CURRENCY => $this->getCurrency(),
            'cash_balance'      => $this->_getBalance(),
            GprFields::UID      => $this->getPlayUid($this->cur_user->getId()),
            GprFields::MAX_STAKE => $this->getMaxBetLimit($this->cur_user),
        ], $this->getFrbInfo());

        if(!empty($this->session_entry)){
            $ret['ext_session_id'] = $this->session_entry['id'];
            $ret['ext_participation_id'] = $this->session_entry['participation_id'];
        }


        phive('Logger')->getLogger('casino')->debug('authenticate::response ', $ret);
        return $ret;
    }

    public function error($code, $msg = '', $data = []){
        return ['success' => false, 'code' => $code, 'result' => $msg, 'result_data' => $data];
    }

    public function success($result = '', $status_code = 0){
        return ['success' => true, 'result' => $result, 'code' => $status_code];
    }

    public function isError($res){
        if(is_array($res) && $res['success'] === false){
            return true;
        }

        return false;
    }

    public function balance($args){
        return array_merge($this->getBalance(), $this->getFrbInfo());
    }

    public function betWinCommonReturn($transaction_id, $balance = null){
        return array_merge([
            GprFields::TR_ID => $transaction_id,
            GprFields::UID   => $this->cur_user->getId(),
        ], $this->getBalance($balance));
    }

    public function getToDbGpTransId($gp_id){
        return $this->gp.'_'.$gp_id;
    }

    public function getRefundedId($db_trans_id){
        return $db_trans_id.'ref';
    }

    public function getFromDbGpTransId($db_id){
        list($gp, $gp_id) = explode('_', $db_id);
        return $gp_id;
    }

    public function getTrTable($table){
        if($this->isTournamentMode()){
            $table .= '_mp';
        }
        return $table;
    }

    public function getPriorTransaction($gp_id, $table){
        $mg_id = $this->getToDbGpTransId($gp_id);
        $table = $this->getTrTable($table);

        // finding transaction that may or may not be rolled back already
        $prior_tr = $this->db->sh($this->cur_user->getId())->loadAssoc("SELECT * FROM $table WHERE mg_id = '$mg_id' OR mg_id = CONCAT('$mg_id', 'ref')");

        return empty($prior_tr) ? false : $prior_tr;
    }
    
    public function getTransactionForRollback($transaction_id): ?array {
        $transaction_id = $this->getToDbGpTransId($transaction_id);
        $bets_table = $this->getTrTable("bets");
        $wins_table = $this->getTrTable("wins");

        $transaction = $this->db->sh($this->cur_user->getId())->loadArray("
            SELECT 
                id, amount, mg_id, trans_id, user_id, bonus_bet, 'bets' as transaction_type 
            FROM $bets_table 
            WHERE mg_id = '$transaction_id' OR mg_id = CONCAT('$transaction_id', 'ref')
            UNION
            SELECT 
                id, amount, mg_id, trans_id, user_id, bonus_bet, 'wins' as transaction_type 
                FROM $wins_table 
            WHERE mg_id = '$transaction_id' OR mg_id = CONCAT('$transaction_id', 'ref')
        ");

        $transaction = reset($transaction);

        return empty($transaction) ? null : $transaction;
    }

    public function finishPartiallyHandledFrb($frb_entry, $win_amount = 0, $win_id = null){
        // The total win amount is the current balance plus potentially a final win.
        $win_amount = $frb_entry['balance'] + $win_amount;
        // Casino::handleFspinWin() for some reason increments reward and balance, we don't want that to happen
        // here so we reset to zero.
        $frb_entry['reward'] = 0;
        // We reset the balance to 0 in case the FRB has turnover requirements, the new balance will be the winnings
        // in the balance field / column.
        $frb_entry['balance'] = 0;
        // The FRB has been handled partially but is now ended wo we pass in is_final = true.
        $this->handleFspinWin($frb_entry, $win_amount, $this->cur_user, 'FRB Win', true);
        return $win_amount;
    }

    /**
     * Processes freespins bonuses with support for confirmation request at the end of the rounds.
     *
     * This function handles two scenarios:
     * 1. **Partial Wins**:
     *    - Triggered during the free spins rounds for each win.
     *    - Updates the `balance` field in the `bonus_entries` table.
     *    - Creates an entry in the `wins` table for each win.
     * 2. **Confirm Win**:
     *    - Triggered at the end of the free spins rounds, optionally including the total sum of the wins.
     *    - Updates the `balance` field in the `bonus_entries`.
     *    - Creates an entry in the `wins` table.
     *
     * @param array $args Parameters required for handling the bonus.
     *   - `GprFields::UID`: User ID for identifying the user.
     *   - `GprFields::GAME_ID`: Game reference ID for fetching game details.
     *   - `GprFields::TR_ID`: Transaction ID for identifyng the win on GP side.
     *   - `GprFields::AMOUNT` (optional): Win amount to process (default: 0).
     *   - `GprFields::FRB_FINISHED` (optional): Boolean indicating if the free spins are completed (default: false).
     *
     * @return array The response of the operation, either for partial wins or the confirmation request.
     */
    public function handleBonusWithConfirmation(array $args = []) : array {
        $amount = $args[GprFields::AMOUNT] ?? 0;
        $transaction_id = $args[GprFields::TR_ID];
        $is_confirmation = $args[GprFields::FRB_FINISHED] ?? false;
        $use_frb_remaining_0_as_ending = $args[GprFields::USE_FRB_REMAINING_0_AS_CONFIRMATION] ?? false;

        $user = $this->cur_user;
        $game = $this->cur_game;
        $start_balance = $this->_getBalance();
        $bonus_entry = $this->getFrbEntry($args);
        $bonus_type = phive('Bonuses')->getBonus($bonus_entry['bonus_id']);
        $award_type = $this->getAwardType($bonus_entry);
        $bonus_bet_type = $this->getBonusBetType($bonus_entry);
        $transaction_mg_id = $this->getToDbGpTransId($transaction_id);

        if ($use_frb_remaining_0_as_ending && $bonus_entry['frb_remaining'] == 0) {
            $is_confirmation = true;
        }

        if (empty($user) || empty($game) || empty($bonus_entry) || empty($bonus_type) || empty($transaction_id)) {
            return $this->error(ErrType::ACTION_FAILED, 'Invalid request for freespins bonus with confirmation');
        }

        $transaction = [
            'amount' => $amount,
            'id' => $transaction_id,
            'award_type' => $award_type,
            'mg_id' => $transaction_mg_id,
            'start_balance' => $start_balance,
            'bonus_bet_type' => $bonus_bet_type,
        ];

        if ($is_confirmation) {
            return $this->handleBonusConfirmWin($transaction, $bonus_entry, $bonus_type, $game, $user);
        }

        return $this->handleBonusPartialWin($transaction, $bonus_entry, $bonus_type, $game, $user);
    }

    public function handleBonusPartialWin(array $transaction, array $bonus_entry, array $bonus_type, array $game, object $user) : array {
        $amount = $transaction['amount'];

        if ($amount <= 0) {
            return $this->success(array_merge($this->betWinCommonReturn(uniqid()), $this->getFrbInfo()));
        }

        if (!phive('Bonuses')->increaseBonusBalance($amount, $bonus_entry, $user->getId())) {
            return $this->error(ErrType::ACTION_FAILED, "Failed increasing balance for freespins bonus with {$amount} winnings");
        }

        $transaction_id = $this->insertWin(
            $user->data,
            $game,
            $transaction['start_balance'],
            $transaction['id'],
            $transaction['amount'],
            $transaction['bonus_bet_type'],
            $transaction['mg_id'],
            $transaction['award_type']
        );

        return $this->success(array_merge(
            $this->betWinCommonReturn($transaction_id ?? uniqid()),
            $this->getFrbInfo()
        ));
    }

    public function handleBonusConfirmWin(array $transaction, array $bonus_entry, array $bonus_type, array $game, object $user) : array {
        $total_winnings = (int) ($bonus_entry['balance'] + $transaction['amount']);
        $action_method = $total_winnings <= 0 ? 'failFreespinsBonus' : 'finishFreeSpinsBonus';

        $result = $this->{$action_method}($total_winnings, $bonus_entry, $bonus_type, $game, $user);
        if ($result === false) {
            return $this->error(ErrType::ACTION_FAILED, "Failed finishing free spins bonus with {$total_winnings} winnings");
        }

        if ($total_winnings > 0 && $transaction['amount'] > 0) {
            $transaction_id = $this->insertWin(
                $user->data,
                $game,
                $transaction['start_balance'],
                $transaction['id'],
                $transaction['amount'],
                $transaction['bonus_bet_type'],
                $transaction['mg_id'],
                $transaction['award_type']
            );
        }

        return $this->success(array_merge(
            $this->betWinCommonReturn($transaction_id ?? uniqid()),
            $this->getFrbInfo()
        ));
    }

    public function getBonusBetType($frb_bonus){
        return 3;
    }

    public function getAwardType($frb_bonus = null){
        if(empty($frb_bonus)){
            return AwardType::WIN;
        }
        $wager_turnover = phive('Bonuses')->getTurnover($this->cur_user, $frb_bonus);
        return empty($wager_turnover) ? AwardType::FRB_WIN : AwardType::FRB_WIN_WITH_WAGERING;
    }

    public function handleFspinBet(array $args = []) {
        list($frb_entry, $frb_bonus) = $this->getFrbInfo(null, null, false);

        $win_id = uniqid();

        if(empty($frb_entry['frb_remaining'])){
            return $win_id;
        }

        if(!empty($frb_entry)){

            $this->frb_bet = true;

            if($frb_entry['frb_remaining'] == 1 && $partially_handled_frb){
                $win_amount = $frb_entry['balance'];
                // Last spin, it's over.
                $this->finishPartiallyHandledFrb($frb_entry);
                if(!empty($win_amount)){
                    // We insert a win with made up GP ID as using the sent ID for the bet is not really correct.
                    $win_id = $this->insertWin(
                        $this->cur_udata,
                        $this->cur_game,
                        $this->_getBalance(),
                        0,
                        $win_amount,
                        $this->getBonusBetType($frb_bonus),
                        $this->getToDbGpTransId(uniqid()),
                        $this->getAwardType($frb_bonus)
                    );

                    // We update the token data so that FRB play is now over, we won't be returning remaining spins etc anymore.
                    $this->updateSession('frb_bonus', null);
                }
            } else {
                $this->db->incrValue('bonus_entries', 'frb_remaining', ['id' => $frb_entry['id']], -1, [], $this->cur_user->getId());
            }
        }

        return $win_id;
    }

    public function bet($args){
        if($this->doTrackFrb() && $args[GprFields::TR_TYPE] == Trtype::FRB_BET){
            // The GP has sent an FRB bet notification, we just decrease frb_remaining with one and done.
            $win_id = $this->handleFspinBet($args);
            return array_merge($this->betWinCommonReturn($win_id), $this->getFrbInfo());
        }

        $gp_transaction_id = $args[GprFields::TR_ID];

        $prior_bet = $this->getPriorTransaction($gp_transaction_id, 'bets');
        if($prior_bet){
            // this GP requires an error reply, we must avoid returning success.
            // if we find ref, then we received a rollback request BEFORE the bet actually came in
            if($args[GprFields::CHECK_FOR_DUPLICATE_BET] && str_ends_with($prior_bet['mg_id'], 'ref')){
                return $this->error(Errtype::BET_ALREADY_SETTLED, 'Transaction has been cancelled');
            }

            // the request was duplicate, we check if the transaction is new or some attempted retry
            if($args[GprFields::CHECK_FOR_DUPLICATE_BET]){
                if ($prior_bet['amount'] != $args['amount']){
                    return $this->error(Errtype::DUPLICATE, 'Duplicate bet');
                }
                else{  // if the amount is the same then we just return success
                    return $this->betWinCommonReturn($prior_bet['id']);
                }
            }

            // Idempotency protection, we just return success without doing anything becasue this is a duplicate request.
            return $this->betWinCommonReturn($prior_bet['id']);
        }

        $bet_amount    = $args[GprFields::AMOUNT];
        $start_balance = $this->_getBalance();
        $round_id      = $this->getRoundId($args);


        if(empty($bet_amount)){
            // Zero bet, probably an FRB bet that we don't care about because the GP will indicate when
            // the FRB spins are finished in a win instead. We return immediately. Example GP: PlaynGO
            return $this->betWinCommonReturn(uniqid(), $start_balance);
        }

        if($start_balance < $bet_amount){

            lic('onNotEnoughMoney', [$this->cur_user, $this->cur_game], $this->cur_user);

            // Not enough money period.
            return $this->error(Errtype::NO_FUNDS, 'Not enough money', $this->getBalance($start_balance));
        }

        //this GP needs to validate the bet amount (in cents) due to UKGC regulations
        $maxBet = $this->getMaxBetLimit($this->cur_user) * 100;
        if($this->violatesUkgcMaxBet($maxBet, $args['amount'], $args[GprFields::VALIDATE_UKGC_MAX_BET], $this->licJur($this->cur_user))){
            return $this->error(Errtype::UKGC_MAX_BET_VIOLATION, 'Bet amount received is forbidden due to ukgc max bet limits');
        }

        $balance = $this->lgaMobileBalance($this->cur_udata, $this->cur_game['ext_game_name'], $start_balance, $this->cur_game['device_type'], $bet_amount);

        if ($balance < $bet_amount) {
            // An RG or BoS restriction has been triggered, we have no clue what the GP supports in this case, the safest is to
            // just return zero funds. TODO, step one: return an RG error here. Step 2, take that RG error on the GPR side
            // and return an RG error to the GP and no funds if the GP does not support RG errors.
            return $this->error(Errtype::RG_LIMIT_NO_BALANCE, 'Responsible gambling or tournament limits reached', $this->getBalance($balance));
        }

        $jp_contrib  = $args['jp_contrib'] ?? round($bet_amount * $this->cur_game['jackpot_contrib']);
        $end_balance = $this->playChgBalance($this->cur_udata, -$bet_amount, $gp_transaction_id, 1);
        if($end_balance === false) {
            return $this->error(Errtype::NO_FUNDS, 'Not enough money', $this->getBalance(0));
        }
        $bonus_bet   = empty($this->bonus_bet) ? 0 : 1;

        $bet_id = $this->insertBet(
            $this->cur_udata,
            $this->cur_game,
            $this->getGpRoundCount($args),
            $this->getToDbGpTransId($gp_transaction_id),
            $bet_amount,
            $jp_contrib,
            $bonus_bet,
            $end_balance
        );

        if($bet_id === false){
            return $this->error(Errtype::DB_ERROR);
        }

        $end_balance = $this->betHandleBonuses(
            $this->cur_udata,
            $this->cur_game,
            $bet_amount,
            $end_balance,
            $bonus_bet,
            $round_id,
            $gp_transaction_id
        );
        return $this->betWinCommonReturn($bet_id, $this->_getBalance());
    }

    public function rollback($args){
        if($args[GprFields::CANNOT_RB_WIN]){
            $win = $this->getPriorTransaction($args[GprFields::RB_TR_ID], 'wins');
            if($win) {
                return $this->error(Errtype::CANNOT_RB_WIN, 'Rollback not allowed for winning transactions');
            }
        }

        if($args[GprFields::CANNOT_RB_BET_WITH_WIN]){
            $rounds = $this->getUserGameSessionBets($this->getRoundId($args));
            if($rounds[0]['win_id'] != 0) {  // validate if win exists so that we do not block rollbacks when we send CANNOT_RB_BET_WITH_WIN field
                return $this->error(Errtype::CANNOT_RB_BET_WITH_WIN, 'Trying to cancel a bet from an already closed round');
            }
        }

        if(isset($args[GprFields::RB_TR_TYPE])){
            // We're directed to rollback a specific transaction type.
            $result = $this->_rollback($args, $args[GprFields::RB_TR_TYPE]);
            if(is_numeric($result)){
                // We got the rollback id.
                return $this->betWinCommonReturn($result);
            }

            return $result;
        }

        if (!empty($args[GprFields::ROLLBACK_SINGLE_TRANSACTION])) {
            $transaction = $this->getTransactionForRollback($args[GprFields::RB_TR_ID]);

            if(empty($transaction)){
                return $this->error(Errtype::NO_TRANSACTION, 'Transaction not found');
            } else if (substr_compare($transaction['mg_id'], "ref", strlen($transaction['mg_id']) - 3) == 0) {
                return $this->betWinCommonReturn($transaction['id']);
            }

            $result = $this->_rollback($args, $transaction['transaction_type'], $transaction);
            if(is_numeric($result)){
                return $this->betWinCommonReturn($result);
            }

            return $result;
        }

        $results = [];
        if(!empty($args[GprFields::ROUND_ID]) && !empty($args[GprFields::GAME_SESSION_BETS_SUPPORT])){

            // We have a round id and round support, we get the rounds from the round table that we want to roll back.
            $brand_round_id = $this->getRoundId($args);
            $bet_id = null;

            if(!empty($args[GprFields::RB_TR_ID])){
                // We look for a bet id and if we find it we will use it when getting the round.
                $bet_id = $this->getPriorTransaction($args[GprFields::RB_TR_ID], 'bets')['id'] ?? null;
            }

            $rounds = $this->getUserGameSessionBets($brand_round_id, $bet_id);

            if(empty($rounds)){
                return $this->insertBetWithoutTr($args[GprFields::TR_ID] ?? $args[GprFields::RB_TR_ID] ?? null, $args);
            }

            // phive('Logger')->getLogger('casino')->debug('ROUNDS: ', $rounds);

            $wins   = [];
            $bets   = [];
            $selectColumns = "SELECT id, balance, mg_id, user_id, game_ref, amount, device_type";
            foreach($rounds as $r){
                $win = empty($r['win_id']) ? null : $this->db->sh($this->cur_user)->loadAssoc("$selectColumns, IF(mg_id LIKE '%ref', 1, 0) AS rb_check FROM wins WHERE id = ".$r['win_id']);
                $bet = empty($r['bet_id']) ? null : $this->db->sh($this->cur_user)->loadAssoc("$selectColumns, IF(mg_id LIKE '%ref', 1, 0) AS rb_check FROM bets WHERE id = ".$r['bet_id']);
                if($win['rb_check'] == 1 || $bet['rb_check'] == 1){
                    return $this->success($this->betWinCommonReturn(uniqid()), Errtype::BET_ALREADY_SETTLED);
                }

                if(!empty($bet)){
                    $bets[$bet['id']] = $bet;
                }
                if(!empty($win)){
                    $wins[$win['id']] = $win;
                }
            }

            foreach($bets as $bet){
                $results[] = $this->_rollback($args, 'bets', $bet);
            }

            if(!$args[GprFields::DO_RB_WIN] && !empty($wins)){
                // This GP does only roll back bets if the corresponding win has NOT been handled.
                // We have found wins so we return.
                return $this->success($this->betWinCommonReturn(uniqid()), Errtype::BET_ALREADY_SETTLED);
            }

            // We roll back the wins that resulted from the bets.
            foreach($wins as $win){
                $results[] = $this->_rollback($args, 'wins', $win);
            }
        } else {
            // We don't have a specific transaction type which means that all transactions with the given
            // id should be rolled back. This is the case for GPs that send the same transaction id for
            // the bet and the win and additionally want us to also roll back wins.
            foreach(['bets', 'wins'] as $tr_type){
                $results[] = $this->_rollback($args, $tr_type);
            }
        }

        foreach($results as $res){
            if(is_numeric($res)){
                return $this->betWinCommonReturn($res);
            }
        }

        // They were all failures so we just return the first one.
        return $results[0];
    }


    /**
     * @param $prior_transaction_id
     * @param $args
     * @return array
     */
    public function insertBetWithoutTr($prior_transaction_id, $args): array
    {
        // if we cannot find transaction_id and $this->cur_game is also null, we need to through game not found error.
        if(empty($this->cur_game)){
            return $this->error(Errtype::NO_GAME);
        }
        // transactionID doesn't exist, the bet has never arrived on our server, maybe because it timed-out,
        // so we insert the bet with amount 0 to avoid that it will be processed on a later moment again if the bet request goes through.
        // If /bet request times-out (so our server doesn't answer it) a /win request will never be send by the GP
        $bet_id = $this->insertBet(
            $this->cur_udata,
            $this->cur_game,
            $args[GprFields::ROUND_ID] ?? 0,
            $this->getToDbGpTransId($this->getRefundedId($prior_transaction_id ?: $args[GprFields::TR_ID] ?: $prior_transaction_id)),
            0,
            0,
            0,
            $this->cur_udata['cash_balance']
        );

        return $this->error(Errtype::NO_TRANSACTION, '', $this->betWinCommonReturn(0));
    }

    public function _rollback($args, $tr_type, $prior_transaction = null){

        // phive('Logger')->getLogger('casino')->debug('ROLLBACK ARGS: ', func_get_args());

        if(empty($prior_transaction)){
            $prior_transaction_id = $args[GprFields::RB_TR_ID];
            $prior_transaction    = $this->getPriorTransaction($prior_transaction_id, $tr_type);
            if(!$prior_transaction){
                // The transaction to roll back could not be found, we check for idempotency.
                $prior_transaction = $this->getPriorTransaction($this->getRefundedId($prior_transaction_id), $tr_type);
                if(!$prior_transaction){
                    return $this->insertBetWithoutTr($prior_transaction_id, $args);
                }

                // We found it, we do nothing but return the prior refunded as it's been refunded already.
                return $this->betWinCommonReturn($prior_transaction['id'], $prior_transaction['balance']);
            }
            else{
                //check that GP wants us to rollback the same amount as the prior transaction record, also check that
                //GP sent the amount otherwise this blocks all rollbacks where an amount is not sent by GP
                if(isset($args[GprFields::AMOUNT]) && $args[GprFields::AMOUNT] != $prior_transaction[GprFields::AMOUNT]){
                    return $this->error(Errtype::INFO_MISMATCH, 'Transaction details do not match');
                }

                // transaction found, but we have to check if it is already rolled back, we must return response to GP here if so
                if(substr_compare($prior_transaction['mg_id'], "ref", strlen($prior_transaction['mg_id']) - 3) == 0){
                    return $this->betWinCommonReturn($prior_transaction['id']);
                }
            }
        }

        if ($this->isTournamentMode()) {
            $tournamentInstance = phive('Tournament');
            if ($tournamentInstance->isClosed($this->t_entry)) {
                return $this->success($this->betWinCommonReturn(uniqid()), Errtype::BOS_ROLLBACK_FAIL);
            }
        }

        if(empty($this->cur_user)){
            // If the GP has not supplied us with user info we can now get it by way of the transaction.
            $this->cur_user = cu($prior_transaction['user_id']);
            $this->cur_udata = ud($this->cur_user);
        }

        if(empty($this->cur_game)){
            // If the GP has not supplied us with game info we can now get it by way of the transaction.
            $this->cur_game = phive('MicroGames')->getByGameRef($prior_transaction['game_ref']);
        }

        $bonus_bet_type_from_bets = null;
        if ($tr_type == 'bets'){
	        $amount = $prior_transaction[GprFields::AMOUNT];
            $bonus_bet_type_from_bets = $this->getBonusBetTypeFromBet(null, $prior_transaction['mg_id']);
            $type = 7;
        }else{
	        $amount = -$prior_transaction[GprFields::AMOUNT];
            $type = 1;
        }

        $balance        = $this->playChgBalance($this->cur_udata, $amount, $prior_transaction['trans_id'], $type, $bonus_bet_type_from_bets);
        $rollback_table = $this->isTournamentMode() ? $tr_type.'_mp' : $tr_type;
        $play_mode      = $this->isTournamentMode() ? 'tournament' : 'normal';

        $descr = "{$this->gp} rollback adjustment of {$amount} cents, balance after: $balance cents, play mode: $play_mode, rollback type: {$rollback_table}";

        phive('UserHandler')->logAction($prior_transaction['user_id'], $descr, 'rollback');
        $rollback_id = phive('Cashier')->insertTransaction($prior_transaction['user_id'], $amount, 7, $descr, 0, '', 0, $prior_transaction['id']);

        $mg_id  = $this->getRefundedId($prior_transaction['mg_id']);
        $extra  = $tr_type == 'wins' ? '' : ", jp_contrib = 0, loyalty = 0";
        $result = $this->db->sh($prior_transaction)->query("
            UPDATE $rollback_table SET
                amount = '{$prior_transaction['amount']}',
                op_fee = 0,
                mg_id = '$mg_id'
                $extra
            WHERE mg_id = '{$prior_transaction['mg_id']}'");


        $ugs = $this->getGsess($prior_transaction, $this->cur_user);

        if(!empty($ugs)){
            $rollback_data = [
                'user_id'           => (int) $this->cur_user->getId(),
                'amount'            => (int) $amount,
                'currency'          => $this->cur_user->getCurrency(),
                'mg_id'             => $prior_transaction['mg_id'],
                'game_ref'          => $ugs['game_ref'],
                'device_type'       => (int) $ugs['device_type_num'],
                'event_timestamp'   => time(),
            ];

            $key = $tr_type.'_rollback';

            try {
                if($tr_type == 'wins') {
                    $rollback_data = new WinsRollbackHistoryMessage($rollback_data);
                } else {
                    $rollback_data = new BetsRollbackHistoryMessage($rollback_data);
                }

                lic('addRecordToHistory', [$key, $rollback_data], $this->cur_user);
            } catch(Exception $e) {
                phive('Logger')->getLogger('casino')->error('History Message Validation Error: ', $e->getMessage());
            }

            $this->db->sh($ugs)->query("UPDATE users_game_sessions SET $key = $key + {abs($amount)} WHERE id = {$ugs['id']}");
            phive()->pexec('na', 'lic', ['cancelReportSession', [$ugs['id'], $this->cur_user->getId(), $tr_type, $amount], $this->cur_user->getId()], 500, true);
        } else {
            phive('Logger')->getLogger('casino')->error("Method: Gpr::_rollback(), Error: User Game Session could not be found for this $tr_type: ", $prior_transaction);
        }

        if ($play_mode == 'normal') {
            realtimeStats()->onRollback($this->cur_user, $tr_type, $amount);
        }

        return $rollback_id;
    }

    public function findBetByExtRoundId($args){
        $rounds       = $this->getUserGameSessionBets($this->getRoundId($args));
        $prior_bet_id = null;
        $ret_round    = null;
        foreach($rounds as $round){
            if(!empty($round['bet_id'])){
                $prior_bet_id = $round['bet_id'];
                $ret_round    = $round;
                break;
            }
        }
        return [$prior_bet_id, $ret_round];
    }

    public function win($args){
        if(!empty($args[GprFields::GAME_ID]) && empty($this->cur_game)){
            $this->cur_game = $this->getGame($args[GprFields::GAME_ID]);

            // the cur_game is still empty, we add the mobile extension and try again
            if(isset($args[GprFields::MOBILE_GAME_ID_EXT]) && empty($this->cur_game)){
                $this->cur_game = $this->getGame($args[GprFields::GAME_ID] . $args[GprFields::MOBILE_GAME_ID_EXT]);
            }

            if (empty($this->cur_game)) {
                phive('Logger')->getLogger('casino')->error(__METHOD__, [
                    'message' => 'Game not found',
                    'request' => $args,
                ]);

                return $this->error(Errtype::NO_GAME, 'Game not found');
            }
        }

        if ($args[GprFields::CONFIRM_FRB_BONUS_ACTIVATION] === true) {
            return $this->success(array_merge($this->betWinCommonReturn(uniqid()), $this->getFrbInfo()));
        }

        $gp_transaction_id = $args[GprFields::TR_ID];
        if(empty($gp_transaction_id)){
            return $this->error(Errtype::MISSING_PARAMETER, "GP win ID is missing");
        }
        $prior_win = $this->getPriorTransaction($gp_transaction_id, 'wins');
        if($prior_win){
            // the request was duplicate, we check if the transaction is new or some attempted retry
            if ($args[GprFields::CHECK_FOR_DUPLICATE_WIN]) {
                if ($prior_win['amount'] != $args['amount']) {
                    return $this->error(Errtype::DUPLICATE_WIN, 'Duplicate win');
                }
            }

            if ($prior_win['amount'] === $args['amount'] && $args[GprFields::CHECK_FOR_DUPLICATE_WIN_STRICT]) {
                return $this->error(Errtype::DUPLICATE_WIN, 'Duplicate win');
            }

            // Idempotency protection, we just return success without doing anything becasue this is a duplicate request.
            return $this->betWinCommonReturn($prior_win['id']);
        }

        // validate if currency sent by GP for the win request is the same as the associated bet request
        if($args[GprFields::VALIDATE_CURRENCY]){
            $rounds = $this->getUserGameSessionBets($this->getRoundId($args));
            $assocBet = $this->db->sh($this->cur_user)->loadAssoc("SELECT currency FROM bets WHERE id = ". $rounds[0]['bet_id']);

            // there is no associated bet for the win, perhaps the bet failed for some reason but GP sent the win anyway
            if(is_null($assocBet)){
                return $this->error(Errtype::WIN_WITH_FAILED_BET, 'Initial bet failed');
            }

            if($assocBet['currency'] !== $args['currency']) {
                return $this->error(Errtype::INVALID_CURRENCY, 'The currency received did not match the bet currency');
            }
        }

        // We start out with basic win type.
        $award_type    = AwardType::WIN;
        $win_amount    = $args[GprFields::AMOUNT] ?? 0;
        $start_balance = $this->_getBalance();

        if ($args[GprFields::TR_TYPE] == Trtype::PROMO_WIN_CJP ||
            $args[GprFields::TR_TYPE] == Trtype::PROMO_WIN_PRIZEDROP
        ) {
            return $this->handlePromoWin($args, $win_amount);
        }

        if ($args[GprFields::TR_TYPE] === Trtype::FRB_WITH_CONFIRMATION) {
            return $this->handleBonusWithConfirmation($args);
        }

        // NOTE that this needs to be handled even if win amount is zero, if it is we will just fail the bonus etc.
        if (in_array($args[GprFields::TR_TYPE], [Trtype::FRB_WIN_PARTIAL_OF_TOTAL, Trtype::FRB_WIN_TOTAL])) {
            // FRB win, if amount is zero the FRB will get failed status.
            return $this->handleCompleteFspinWin($args, $win_amount, $start_balance);
        }

        // Context: GP sends partial FRB wins but no need to track bets, ex: Leander
        if($args[GprFields::TR_TYPE] == Trtype::FRB_WIN_PARTIAL && $this->req_data[GprFields::FRB_FINISHED]){
            // We have a partial win where amount could be zero, in any case: the GP states that the
            // FRB is now complete, this is similar to FRB_WIN_TOTAL.
            // We call complete fspin win with the partial flag to true in order to handle things slightly differently.
            return $this->handleCompleteFspinWin($args, $win_amount, $start_balance, true);
        }

        $bonus_bet_type = $this->bonusBetType();
        if(empty($win_amount)){
            // We currently do not do anything with basic win requests that are of the more informative type, we therefore
            // just return success with a made up win id.
            return $this->betWinCommonReturn(uniqid());
        }

        // In order to get here win amount needs to be non-zero which is what we want.
        if($args[GprFields::TR_TYPE] == Trtype::FRB_WIN_PARTIAL){
            return $this->handlePartialFspinWin($win_amount, $start_balance);
        }

        if($args[GprFields::TR_TYPE] == Trtype::JP_WIN){
            // NOTE that we assume that amount contains the jp amount
            $award_type = AwardType::JP_WIN;

            if(isset($args['baseWinVal'])){  // part of the JP win amount value is from normal win, we must separate these two
                $newArgs = $args;
                $newArgs[GprFields::AMOUNT] = $args['baseWinVal'];
                $newArgs[GprFields::TR_TYPE] = Trtype::WIN;

                $this->insertWinReplaced($newArgs[GprFields::AMOUNT], $newArgs, $start_balance, $bonus_bet_type, AwardType::WIN);

                // we remove the normal win amount from the jp win to avoid saving an incorrect jp win value
                $args[GprFields::AMOUNT] = $win_amount = $args[GprFields::AMOUNT] - $args['baseWinVal'];

                // to differentiate between normal and jp win and avoid unique constraint violation, here we add _4 to the jp win entry
                $args[GprFields::TR_ID] = $args[GprFields::TR_ID] . '_4';
            }
        }

       if($args[GprFields::CHECK_FOR_BET]){
           // We have been instructed to check for a bet that generated this win and
           // reject (ie not credit) the win if no bet could be found, we will return success to avoid retries.
           list($prior_bet_id, $round) = $this->findBetByExtRoundId($args);
           if(empty($prior_bet_id)){
               // We did not find the bet so returning here.
               return $this->success($this->betWinCommonReturn(uniqid()));
           }
       }

        // Compare bonus_bet_type to match with Bets table
        $bonus_bet_type_from_bets = null;
        if (empty($bonus_bet_type) && empty($this->cur_user->getCurAttr('cash_balance'))) {
            // bonus_bet_type=1 happens when [balance < bet_amount], but also [(balance + bonus) >= bet_amount]
            // As we do not have all information in insertWin, we are comparing with Bets table
            $rounds = $this->getUserGameSessionBets($this->getRoundId($args));
            $rounds = reset($rounds);

            if (!empty($rounds)) {
                $bonus_bet_type_from_bets = $this->getBonusBetTypeFromBet($rounds['bet_id']);
            }
        }

        $win_id    =  $this->insertWinReplaced($win_amount, $args, $start_balance, $bonus_bet_type, $award_type, $bonus_bet_type_from_bets);

        if($this->isError($win_id)){
            return $win_id;
        }

        if ($award_type == AwardType::JP_WIN && $args['gp'] == 'pragmatic') {
            $game_id = explode('_', $args['game_id']);
            $description = sprintf('PragmaticJackpotWin:%d:%d:%s:%s:%s:%s:%s',
                $args['jackpot_id'], $args['round_id'], $game_id[1],
                $args['provider_id'], $args['platform'], $args['transaction_id'], $this->cur_user->getId()
            );
            // Insert PragmaticJackpotWin in the `actions` table
            phive('UserHandler')->logAction($this->cur_user->getId(), $description, 'PragmaticJackpotWin',true, $this->cur_user->getId());
        }

        // playChgBalance unsets the $this->t_entry, but it is needed to return the correct balance during tournaments
        $tournament_entry = $this->t_entry;
        $end_balance = $this->playChgBalance($this->cur_udata, $win_amount, "", $award_type, 0, $bonus_bet_type_from_bets);
        if($end_balance === false){
            return $this->error(Errtype::DB_ERROR);
        }

        // Here we deal with the situation where a bonus failed on the bet behind this win, we need to deduct the
        // win if that happened.
        $end_balance = $this->handlePriorFail($this->cur_udata, $win_id, $end_balance, $win_amount);
        if($end_balance === false){
            return $this->error(Errtype::DB_ERROR);
        }

        $this->t_entry = $tournament_entry;
        return $this->betWinCommonReturn($win_id, $end_balance);
    }

    public function handlePromoWin($args, int $amount)
    {
        // putting this in variable, so it becomes easier to modify if we decide to change it, or decide to get predefined value from DB or config
        $transaction_type = AwardType::JP_WIN;

        $aUserData = $this->cur_user;
        // Player not found check
        if (empty($aUserData)) {
            return $this->error(Errtype::NO_USER);
        }
        // since we do not have a game reference in this request, and we are not able to use wins or rounds to uniquely identify this transaction
        // So we save $args['tr_id'] (as reference) in a formatted string in description field, and then use it with user_id, bonus_id, amount and transactiontype fields to establish uniqueness of this record
        $description = sprintf('PragmaticPromoWin:%d:%s:%s', $args[GprFields::CAMPAIGN_ID], $args[GprFields::CAMPAIGN_TYPE], $args[GprFields::TR_ID]);
        // check if a transaction already exists against this PromoWin, to avoid adding balance multiple times
        $existing_transaction = phive('Cashier')->getTransaction(cu($aUserData), [
            'bonus_id' => $args[GprFields::CAMPAIGN_ID],
            'transactiontype' => $transaction_type,
            'amount' => $amount,
            'description' => $description
        ]);

        if (!empty($existing_transaction)) {
            return $this->error(Errtype::MISSING_PARAMETER);
        }

        $transaction_id = phive('Cashier')->transactUser(
            $aUserData,
            $amount,
            $description,
            null,
            null,
            $transaction_type,
            false,
            $args[GprFields::CAMPAIGN_ID]
        );

        if ($transaction_id) {
            $end_balance = $this->useExternalSession($this->cur_user) ? $this->getSessionBalance($this->cur_user) : $this->cur_user->getCurAttr('cash_balance');
            return $this->betWinCommonReturn($transaction_id, $end_balance);
        }

        return $this->error(Errtype::SYSTEM_ERROR);
    }

    public function getFrbEntry($args){
        if(!empty($args[GprFields::BRAND_BONUS_ENTRY_ID])){
            return phive('Bonuses')->getBonusEntry($args[GprFields::BRAND_BONUS_ENTRY_ID], $this->cur_user->getId());
        }

        if(!empty($args[GprFields::GP_BONUS_ENTRY_ID])){
            return phive('Bonuses')->getEntryByExtId($args[GprFields::GP_BONUS_ENTRY_ID], $this->cur_user->getId());
        }
    }

    public function handleCompleteFspinWin($args, $win_amount, $start_balance, $partial = false){
        $this->frb_win = true;
        $frb_entry = $this->getFrbEntry($args);
        if(empty($frb_entry)){
            // FRB has invalid status, we can't credit it and therefore we can't return a transaction id,
            // we therefore must return an error.
            return $this->error(Errtype::INVALID_BONUS);
        }

        if ($args[GprFields::TR_TYPE] === Trtype::FRB_WIN_PARTIAL_OF_TOTAL) {
            return  array_merge($this->betWinCommonReturn(uniqid()), $this->getFrbInfo());
        }

        $frb_bonus = phive('Bonuses')->getBonus($frb_entry['bonus_id']);

        if($partial){
            // We're looking at a situation where the win amount is not the full amount and the entry is not approved
            // but active with a balance etc, ex: Leander. The total win sum is returned and which we will use
            // when we insert the win.
            $win_amount = $this->finishPartiallyHandledFrb($frb_entry, $win_amount);
        } else {
            $this->handleFspinWin($frb_entry, $win_amount, $this->cur_user->getId(), 'FRB win');
        }

        if(!empty($win_amount)) {
            $win_id = $this->insertWinReplaced($win_amount, $args, $start_balance, $this->getBonusBetType($frb_bonus), $this->getAwardType($frb_bonus));
        }

        if($this->isError($win_id)){
            return $win_id;
        }

        phive('Bonuses')->resetEntries();
        return array_merge($this->betWinCommonReturn($win_id), $this->getFrbInfo());
    }


    public function handlePartialFspinWin($win_amount, $start_balance){
        $extra_return_info = false;

        if($this->doTrackFrb()){

            $extra_return_info = true;

            // GP does NOT send bonus entry id.
            list($frb_entry, $frb_bonus) = $this->getFrbInfo(null, null, false);
            if(empty($frb_entry)){
                return $this->error(Errtype::INVALID_BONUS);
            }

            if($frb_entry['frb_remaining'] == 0){
                // It looks like we won on the last FRB bet.
                $wager_turnover = phive('Bonuses')->getTurnover($this->cur_user, $frb_bonus);

                // phive('Logger')->getLogger('game_providers')->debug('Gpr->PARTIAL FRB WIN 2', [$wager_turnover]);

                if(!empty($wager_turnover)){

                    // phive('Logger')->getLogger('game_providers')->debug('Gpr->PARTIAL FRB WIN', $frb_entry);

                    // We need to "credit" the win "after the fact" by adjusting the turnover values, ignoring status etc.
                    $this->db->incrValue('bonus_entries', null, ['id' => $frb_entry['id']], [
                        'reward'  => $win_amount,
                        'balance' => $win_amount,
                        'cost'    => ($wager_turnover / 100) * $win_amount
                    ], [], $this->cur_user->getId());

                    // We add an extra bonus shift with the amount.
                    phive('Bonuses')->handleFspinShift($frb_entry, $win_amount, "FRB with entry id {$frb_entry['id']} top up on last partial win");
                } else {
                    return $this->handleCompleteFspinWin($this->req_data, $win_amount, $start_balance, true);
                }
                return array_merge($this->betWinCommonReturn(uniqid()), $this->getFrbInfo());
            }

        } else {
            // GP sends bonus entry id (so no FRB tracking needed) but does partial wins, ex: Leander
            list($frb_entry, $frb_bonus) = $this->getFrbCommon(null, null, false);
        }

        // phive('Logger')->getLogger('game_providers')->debug('Gpr->PARTIAL FRB WIN 1', [$frb_entry, 'win amount' => $win_amount]);

        // We have an FRB win on a single spin, we credit it to the FRB balance, the FRB balance will subsequently be credited
        // to the player's real balance when the last spin is completed.
        // TODO do we "log" here by inserting a cash transaction?
        $this->db->incrValue('bonus_entries', 'balance', ['id' => $frb_entry['id']], $win_amount, [], $this->cur_user->getId());

        $base_return = $this->betWinCommonReturn(uniqid());

        if($extra_return_info){
            return array_merge($base_return, $this->getFrbInfo());
        }

        return $base_return;
    }

    public function tokenMissing(){
        return empty($this->token);
    }

    public function isTokenContext(){
        return $this->isRollback() === false && $this->isEndRound() === false && $this->isWin() === false;
    }

    public function needsUserContext(){
        return $this->isRollback() === false && $this->isEndRound() === false && $this->isAccountInfo() === false;
    }

    public function isRollback(){
        return $this->cur_action == 'rollback';
    }

    public function isEndRound(){
        return $this->cur_action == 'endRound';
    }

    public function isWin(){
        return $this->cur_action == 'win';
    }

    public function isBet(){
        return $this->cur_action == 'bet';
    }

    public function isAccountInfo(){
        return $this->cur_action == 'accountInfo';
    }

    public function setTokenGameUser($args){
        // We start out with no token data.
        $token_data = null;

        // we use this function when using GP testing tools so that redis can be populated on authenticate call
        // API_TESTING_ENABLED parameter can be removed in the authenticate call for the respective GP as the redis will be populated in production
        if($args[GprFields::API_TESTING_ENABLED]){
            $uid            = $this->getUsrId(getMuid($args[GprFields::TOKEN]));
            $this->cur_user = cu($uid);
            $this->getDepUrl($args[GprFields::GAME_ID], 'en', null, null, $args[GprFields::UID]);
        }

        if($args[GprFields::VALIDATE_TOKEN] && $args['action'] == 'authenticate'){

            $sessionIds = phMgetArr('sessionValidation-'.$args[GprFields::UID]);
            if(!empty($sessionIds['activeIds'])){  // on session init, we cannot have a session token that has already been used previously
                if(in_array($args[GprFields::NEW_TOKEN], $sessionIds['activeIds'])) {
                    return Errtype::INVALID_SESSION_ID;
                }
            }

            $sessionIds['activeIds'][] = $args[GprFields::NEW_TOKEN];
            phMsetArr('sessionValidation-'.$args[GprFields::UID], $sessionIds, 3600);
        }

        if(!empty($args[GprFields::TOKEN])){
            if($args[GprFields::VALIDATE_TOKEN] && $args['action'] == 'bet'){

                $ids = phMgetArr('sessionValidation-'.$args[GprFields::UID]);
                if(!in_array($args[GprFields::TOKEN], $ids['activeIds']))  // in a bet scenario, we must have a record of the session token sent by GP
                {
                    return Errtype::INVALID_SESSION_ID;
                }
            }

            $this->token = $token = urldecode(filter_var($args[GprFields::TOKEN], FILTER_SANITIZE_URL));// sometimes the token can be urlencoded, so we need to decode it
            $token_data  = $this->fromsession($this->token);

            if(!empty($token_data)){
                $this->token      = $token;
                $this->token_data = $token_data;
                $uid              = $this->getUsrId($token_data['user_id']);
                $this->cur_user   = cu($uid);

                // Check if the game stored in the session differs from the game provided in the arguments,
                // and if so, update the game in the session.
                if (isset($token_data['original_game']) && isset($args[GprFields::GAME_ID]) && ($curGame = $this->getGame($args[GprFields::GAME_ID]))) {
                    $this->updateOriginalGame($token_data, $curGame);
                }
                // We use the original game for all intents and purposes on our side, since we have set the user
                // getGame() will get the original in case we don't have it in the token data, so if eg Leander sends
                // SILKROAD3U4 we will fetch SILKROAD5Z0 and use that.
                if (!$args[GprFields::PREVENT_ORIGINAL_GAME_OVERWRITE]) {
                    $this->cur_game = $token_data['original_game'] ?? $this->getGame($args[GprFields::GAME_ID]);
                }
            } else if(empty($args[GprFields::UID])) {
                // Token has expired or is otherwise unavailable.
                // No user ID was sent but a token key that we can parse it from (even though we're not in a token context), eg MicroGaming.
                $uid              = $this->getUsrId(getMuid($args[GprFields::TOKEN]));
                $this->cur_user   = cu($uid);

            }
        }

        if(!empty($args[GprFields::UID]) && empty($this->cur_user)){
            // We're in a situation where the token has expired but we have the user id.
            $uid            = $this->getUsrId($args[GprFields::UID]);
            $this->cur_user = cu($uid);


        }

        if(!empty($args[GprFields::GAME_ID]) && empty($this->cur_game)){
            $this->cur_game = $this->getGame($args[GprFields::GAME_ID]);
        }

        if(!empty($this->cur_user)){
            $this->cur_udata = ud($this->cur_user);
        }

        if (empty($args[GprFields::TOKEN]) && empty($this->cur_game) && !empty($args[GprFields::BRAND_BONUS_ENTRY_ID])) {
            $this->cur_game = $this->getGameFromBonusEntry($args);
        }

        // Check if the current game is marked as retired. If it is, attempt to find another active, non-retired entry
        // for the same game in the `micro_games` table using the specified overrides.
        // This scenario occurs when there are duplicate game entries in the `micro_games` table that cannot be removed
        // due to their impact on reporting accuracy.
        // If a valid alternative entry is found, replace the current game with the new entry.
        $is_game_retired = (int)$this->cur_game['retired'] === 1;
        if ($is_game_retired) {
            $new_game = phive('MicroGames')->getByGameRef($args[GprFields::GAME_ID], $this->getBrandClientNum(), $this->cur_user ?? null, false, true);

            if (!empty($new_game)) {
                $this->cur_game = $new_game;
            }
        }

        return null;
    }

    public function exec($args, $action_override = null){

        $this->req_data   = $args;
        $this->cur_action = $action_override ?? $args['action'];

        // If this is a sub action of a multi action call then $this->gp has already been set and $args['gp']
        // will be null.
        $this->gp = $args['gp'] ?? $this->gp;
        $uid      = null;

        if($this->cur_action == 'multiAction'){
            // A multi action takes precedence over everything else and will return result array via recursion to exec
            // so there is no need to pass the result to success().
            return call_user_func_array([$this, $this->cur_action], [$args]);
        }

        if(in_array($this->cur_action, ['createTestSession', 'endSession'])){
            // We hijack everything below.
            $ret = call_user_func_array([$this, $this->cur_action], [$args]);

            $this->logger->debug(__METHOD__, ['response' => $ret]);

            return $this->success($ret);
        }

        $tokenRes = $this->setTokenGameUser($args);

        if($tokenRes === Errtype::INVALID_SESSION_ID){
            return $this->error(Errtype::INVALID_SESSION_ID, 'Session ID did not match');
        }

        if(!empty($this->token)){
            // Spain and Italy happens here, but only if we're NOT playing BoS, we also must have a token.
            if($this->cur_action != 'authenticate' && !$this->isTournamentMode()){
                $res = $this->setupExternalGameSession($this->token, false);
                if($this->isError($res)){
                    return $res;
                }
            }
        }

        if($this->needsUserContext() && empty($this->cur_user)){
            return $this->error(Errtype::NO_USER);
        }

        if($this->isTokenContext()){

            // Skip check if we get request for community_jackpot_win (Pragmatic) or Adjustment (Pragmatic)
            if (empty($this->cur_game)) {
                $isPromoWin = isset($args[GprFields::TR_TYPE]) && $args[GprFields::TR_TYPE] === Trtype::PROMO_WIN_CJP;
                $isAdjustment = $args['action'] === "adjustment";
                $isBingo = !empty($args[GprFields::IS_BINGO]);

                if (!$isPromoWin && !$isAdjustment && !$isBingo) {
                    return $this->error(Errtype::NO_GAME);
                }
            }
        }

        $this->cur_udata = ud($this->cur_user);
        try {
            $ret = call_user_func_array([$this, $args['action']], [$args]);
        } catch (Exception $exception) {
            return $this->error(Errtype::ACTION_FAILED, $exception->getMessage());
        } catch (Error $exception) {
            // Handle error
            return $this->error(Errtype::ACTION_FAILED, $exception->getMessage());
        }

        phive('Logger')->getLogger('casino')->debug('Gpr->EXEC->RESULT', [$ret]);

        // Check if action return is null
        if(is_null($ret)) {
            return $this->error(Errtype::ACTION_FAILED, 'Action method returning null. Action: '.$args['action']);
        }

        $this->logger->debug(__METHOD__, ['response' => $ret]);

        if(isset($ret['success'])){
            // We're looking at a return result reply already (and it's probably an error), nothing to do.
            return $ret;
        }

        return $this->success($ret);
    }

    public function multiAction($args){
        $res = [];
        // We don't just loop the actions but get them by order as they sent array must be a numerical
        // array with each key set explicitly, that way we don't have to worry that the order gets
        // messed up by JSON encoding / decoding.
        for($i = 0; $i < count($args['actions']); $i++){
            $sub_args = $args['actions'][$i];
            $tmp = $this->exec($sub_args);
            if($this->isError($tmp)){
                // If e.g. the bet fails we can't continue and try the win so we return immediately.
                return $tmp;
            }
            // We un-spool the success returns, so we can return [success => true, result => [ results => [ ... ]]] which
            // conforms to our internal format.
            $res[$i] = isset($tmp['success']) ? $tmp['result'] : $tmp;
        }

        return $this->success(['results' => $res]);
    }

    public function setupExternalGameSession($token, $init = true){
        if($init){
            if (lic('hasGameplayWithSessionBalance', [], $this->cur_user) === true) {
                $this->initExternalGameSession($this->cur_user, $token, $this->cur_game);
            }
        }

        if ($this->useExternalSession($this->cur_user)) {
            if(empty($token)){
                return $this->error(Errtype::NO_SESSION);
            }

            // Spain / Italy stuff, token handling is a must for this to work atm.
            $this->setExternalSessionByToken($this->cur_user, $token);
        }

        return $this->success();
    }


    public function createTestSession($args){

        // Does not support external game sessions as the ajax init stuff has not been executed.

        if($_ENV['APP_ENVIRONMENT'] != 'dev'){
            return $this->error(Errtype::WRONG_ENVIRONMENT, 'Create test session can only be called in dev environments');
        }

        $uid = $this->getUsrId($args[GprFields::UID]);

        $u_obj = cu($uid);
        if(empty($u_obj)){
            return $this->error(Errtype::NO_USER);
        }

        list($extra, $frb_entry, $frb_bonus, $override_game) = $this->getGamePlaySessionData($u_obj);

        $token = $this->toSession($u_obj, $extra);

        return [
            'token' => $token
        ];
    }

    public function changeSessionToken($args){
        $token_data = $this->fromSession($this->token);
        phMdel($this->token);
        $this->token = $this->toSession($this->cur_user, $token_data, $args[GprFields::NEW_TOKEN] ?? null);
        return $this->token;
    }

    public function updateSession($key, $val){
        $data = $this->token_data;
        $data[$key] = $val;
        $this->toSession($this->cur_user, $data, $this->token);
    }

    public function toSession($u_obj, $data, $token = null){
        $token = $token ?? mKey($u_obj->getId(), str_replace('-', '', phive()->uuid()));
        // We do only a 10 minute timeout for the "game session" to avoid hogging too much memory.
        phMsetArr($token, $data, $this->token_duration);
        return $token;
    }

    public function fromSession($token){
        // We refresh the Redis session with another 10 minutes.
        return phMgetArr($token, $this->token_duration);
    }

    /**
     * Starts the External Game Session for Italian players
     * If the startup fails it will show an error popup and redirect the player to the lobby
     *
     * @param $user
     * @param $session_id
     * @param $game
     * @return mixed
     */
    public function initExternalGameSession($user, $token, $game)
    {
        $this->logger->getLogger('game_providers')->debug(__METHOD__, [
            'user' => $user->getId(),
            'session_id' => $token,
            'ext_game_name' =>  $game['ext_game_name'],
        ]);

        $external_session_id = lic('initGameSessionWithBalance', [$user, $token, $game], $user);

        return empty($external_session_id) ? false : $external_session_id;
    }

    public function getGameType($game){
        $map = [
            'blackjack'   => 'table',
            'live'        => 'live',
            'live-casino' => 'live',
            'roulette'    => 'table',
            'table'       => 'table',
            'videopoker'  => 'videopoker'
        ];

        return $map[$game['tag']] ?? 'slots';
    }

    public function licJur($u_obj){
        return $this->isTournamentMode() ? $this->getLicSetting('bos-country', $u_obj) : licJur($u_obj);
    }

    public function getGamePlaySessionData($u_obj, $original_game = null){

        $extra = [
            // BoS userid plus entry will be in token_uid.
            GprFields::UID      => $this->mkUsrId($u_obj->getId()),
            'country'           => $u_obj->getCountry(),
            'alias'             => $u_obj->getAlias(),
            'jurisdiction'      => $this->licJur($u_obj),
            'play_type'         => 'real',
            GprFields::CURRENCY => $this->getPlayCurrency(ud($u_obj)),
            'url_history'       => $this->getHistoryUrl(false, $u_obj),
            'is_bos'            => $this->isTournamentMode(),
            'ip'                => $u_obj->getAttr('cur_ip')
        ];

        if(!empty($original_game)){
            $override_game = $this->isTournamentMode()
                ? phive('MicroGames')->overrideGameForTournaments($u_obj, $original_game)
                : phive('MicroGames')->overrideGame($u_obj, $original_game);
            $extra[GprFields::GP_GAME_CATEGORY] = $override_game['module_id'];
            $extra[GprFields::GAME_ID]          = $this->getGpGameId($override_game);
            $extra[GprFields::GAME_LAUNCH_ID]   = $override_game['game_id'];
        }

        list($frb_entry, $frb_bonus) = $this->getFrbInfo($this->gp, $u_obj, false, $original_game);

        if(!empty($frb_bonus)){
            $extra[GprFields::GP_BONUS_ID]          = $this->getExtFrbIds($frb_bonus);
            $extra[GprFields::BRAND_BONUS_ENTRY_ID] = $frb_entry['id'];
        }

        return [$extra, $frb_entry, $frb_bonus, $override_game];
    }


    /**
     * Get an array with the history link and reality check interval in seconds
     * execute the following query on the db: INSERT into config SET config_name ='<gameProviderTag>', config_tag='reality-check-mobile', config_value='on';
     *
     * @param int $user_obj DBUser object.
     * @return array with to key history_link (url to history) and reality_check_interval (in sec)
     */
    public function getRc($u_obj, $gp = null) {
        if($this->getRcPopup('mobile', $u_obj) != 'ingame'){
            // Our own RC logic has been configured as the one to be used, we don't send any RC related params to the GP.
            return [];
        }

        $gp = $gp ?? $this->gp;
        $ret = [];

        $reality_check_interval = $this->startAndGetRealityInterval($u_obj->getId());
        if (!empty($reality_check_interval) && phive("Config")->getValue('reality-check-mobile', $gp) === 'on') {
            $reality_check_interval        = $reality_check_interval * 60;
            $ret['history_link']           = $this->getHistoryUrl();
            $ret['reality_check_interval'] = $reality_check_interval;
        }
        $extra = lic('getBaseGameParams', [$u_obj], $u_obj);
        if(!empty($extra)){
            $ret = array_merge($ret, $extra);
        }
        $rc_params = $this->getRealityCheckParameters($u_obj, false);
        if(!empty($extra)){
            $ret = array_merge($ret, $rc_params);
        }
        return $ret;
    }

    /**
     * This method is used to get the API URL for a specific game.
     *
     * @param string $gameId The ID of the game for which the API URL is needed.
     * @param int $userId The ID of the user who is going to play the game.
     * @param string $language The language in which the game is to be displayed.
     *
     * @return string Returns the API URL for the game.
     */
    public function getApiUrl($gameId, $userId, $language) {
        // Set the current user using the provided user ID
        $this->cur_user = cu($userId);

        if(phive()->isMobile()){
            // Return the mobile play URL for the game in the specified language
            return $this->getMobilePlayUrl($gameId, $language);
        }

        // Return the desktop play URL
        return $this->getDepUrl($gameId, $language);
    }

    /**
     * Get the maximum betting limit based on user's age.
     *
     * @param DBUser $user The user db data
     * @param bool $skipConversion - default "false", if "true" will convert the maxBet to the user's currency
     * @return int|bool The maximum betting limit or false if not found.
     */
    public function getMaxBetLimit(DBUser $user, bool $skipConversion = false)
    {
        $jurisdiction = $this->licJur($user);
        $bettingLimitsConfig = phive('Licensed')->getLicSetting('betting_limits', $jurisdiction);

        // Check if betting limits are enforced for the user's jurisdiction
        if ($bettingLimitsConfig['ENFORCE_BETTING_LIMITS']) {
            // Determine the age group for the user
            $ageGroup = $this->getUserAgeGroup($user);

            // Return the maximum betting limit for the user's age group

            $betLimit = $bettingLimitsConfig['MAX_BET_LIMITS'][$ageGroup];

            if ($skipConversion) {
                return $betLimit;
            }

            return phive('Currencer')->convertCurrencyFromGBP($user->getCurrency(), $betLimit);
        }

        return false;
    }

    /**
     * Determine the user's age group.
     *
     * @param DBUser $user The user db data
     * @return string The user's age group.
     */
    private function getUserAgeGroup(DBUser $user): string
    {
        // If date of birth is not set, consider the user in the lowest age group
        if ($this->isInvalidDob($user->data['dob'])) {
            return 'AGE_18_TO_24';
        }

        $age = phive('UserHandler')->calculateAgeFromDate($user->data['dob']);
        return ($age >= 25) ? 'AGE_25_AND_OVER' : 'AGE_18_TO_24';
    }

    /**
     * Check if the date of birth is invalid.
     *
     * @param mixed $dob The date of birth to check.
     * @return bool True if the date of birth is invalid, false otherwise.
     */
    private function isInvalidDob($dob): bool
    {
        return empty($dob) || $dob === '0000-00-00' || strtolower($dob) === 'null';
    }

    public function depUrlCommon($params){
        $original_game = $params['game'];

        // phive('Logger')->getLogger('casino')->debug('DEP URL GAME: ', $original_game);


        $this->gp = $gp = $this->getGpFromGame($original_game);

        $base = [
            GprFields::GAME_IN_IFRAME => phive('MicroGames')->gameInIframe($original_game),
            GprFields::GAME_ID        => $this->getGpGameId($original_game),
            GprFields::GAME_LAUNCH_ID => $original_game['game_id'],
            'brand'                   => $this->getSetting('brand_name'),
            'gp'                      => $gp,
            'original_language'       => $params[GprFields::LANGUAGE],
            GprFields::LANGUAGE       => $this->getGpLanguage($params[GprFields::LANGUAGE]),
            'client'                  => $params['client'],
            'device'                  => strtolower(phive()->deviceType()),
            'game_type'               => $this->getGameType($original_game),
            'url_lobby'               => $this->getLobbyUrlForGameIframe(false, $params[GprFields::LANGUAGE], $params['client']),
            'url_cashier'             => $this->getCashierUrl(false, $params[GprFields::LANGUAGE], $params['client'])
        ];

        // BoS stuff happens here.
        $this->getUsrId($_SESSION['token_uid']);

        if(isLogged($this->cur_user->userId ?? null)){
            $u_obj = cuPl();

            if($params['testUId']){
                $u_obj = cuPl($params['testUId']);
                $_SESSION['user_id'] = $params['testUId'];
            }

            $this->cur_user = $u_obj;

            $rc_params = [];
            if($params['client'] == 'mobile'){
                // We might need RC for mobile via the GP.
                $rc_params = $this->getRc($u_obj);
            }

            list($extra, $frb_entry, $frb_bonus, $override_game) = $this->getGamePlaySessionData($u_obj, $original_game);

            $this->cur_game = $override_game;

            // This will override game related info with override game info if there is an override game.
            $base = array_merge($base, $extra);

            $token_data = array_merge(
                $base,
                ['original_game' => $original_game, 'override_game' => $override_game, 'frb_bonus' => $frb_bonus]);

            // The reason we only store the bonus_type (frb_bonus) data is that we need fresh bonus_entry data on
            // every request, that's why it's pointless to store it in Redis, a bonus is we save some memory too.
            $token = $this->toSession($u_obj, $token_data);

            $base[GprFields::TOKEN] = $token;
            $base[GprFields::MAX_STAKE] = $this->getMaxBetLimit($this->cur_user);

            $this->token    = $token;
            $this->cur_user = $u_obj;
            $this->cur_game = $override_game;


            $locale = phive('MicroGames')->getGameLocale($this->cur_game['game_id'], $params[GprFields::LANGUAGE] , $game['device_type']);
            $mainProvince = cu()->getMainProvince();

            $base = array_merge($base, ['province' => $this->cur_user->getProvince(), 'locale' => $locale, 'main_province' => $mainProvince]);

            // Everything including nested RC data.
            $http_body = array_merge($base, $rc_params);

            if($this->doLoginCall($gp)){
                // When the user loads the gameplay page the function Gpr::depUrlCommon() is called.
                // For evolution ES, instead of return the game launch URL, the function returns the URL pointing to
                // the file (https://{domain}/diamondbet/evolution_gpr.php?iframe_context=true&args...).
                //
                // Once the user fills the game sessions balance popup, the file `evolution_gpr.php`
                // will be loaded in an iframe, and it will call again Gpr::depUrlCommon() for second time,
                // but this time the function will return the real game launch URL.
                //
                // That behavior will fix the issue with 0 balance for Evolution ES, because the launch URL
                // of the game will be generated after the user fills the balance popup.
                //
                // The iframe_context=true is to avoid the infinite loop over the next if statement.
                $is_iframe_context = boolval($_GET[GprFields::IFRAME_CONTEXT]);
                $external_session_enabled = boolval(lic('hasGameplayWithSessionBalance', [], $u_obj));

                if ($external_session_enabled && $this->doIframe($gp) && !$is_iframe_context) {
                    $iframe_url = "/diamondbet/{$gp}_gpr.php?";

                    return $iframe_url . http_build_query([
                        GprFields::IFRAME_CONTEXT => true, // Don't change this.
                        GprFields::LANGUAGE => $base[GprFields::LANGUAGE],
                        GprFields::GAME_LAUNCH_ID => $base[GprFields::GAME_LAUNCH_ID],
                        GprFields::JURISDICTION => $base[GprFields::JURISDICTION],
                        GprFields::IFRAME_TOKEN => sha1("vs_{$base[GprFields::GAME_LAUNCH_ID]}_{$_SESSION['token']}"),
                    ]);
                }

                $res = $this->login($http_body);

                if($this->isError($res)){
                    return false;
                }

                // The GP wants us to use their token for the session data, eg Pariplay,
                // for this to work the GP must always send the player id as we need that
                // in order to route properly.
                if(!empty($res[GprFields::CHANGE_TOKEN])){
                    $this->changeSessionToken($res);
                }

                if(!empty($res[GprFields::GAME_LAUNCH_URL])){
                    // The GP has returned their own launch URL so we just return it so the player gets
                    // redirected straight to it.
                    return $res[GprFields::GAME_LAUNCH_URL];
                }

                if(!empty($res[GprFields::GP_SESSION_ID])){
                    // Load the token_data before updating the session
                    if (!empty($this->token) && empty($this->token_data)) {
                        $this->token_data = $this->fromSession($this->token);
                    }

                    // The GP has returned their session id which we need to store for future verification.
                    $this->updateSession(GprFields::GP_SESSION_ID, $res[GprFields::GP_SESSION_ID]);
                    $http_body[GprFields::GP_SESSION_ID] = $res[GprFields::GP_SESSION_ID];
                }
            }
        } else {
            $http_body['play_type'] = 'demo';
        }

        // We get the GP URL to redirect to.
        $res = $this->request($http_body, 'launch_url');

        // Didn't work so we just do gp down.
        if($this->isError($res)){
            return false;
        }

        // We get the actual URL from the result array.
        $url = $res['result']['gp_launch_url'];

        parse_str(parse_url($url, PHP_URL_QUERY), $url_args);
        if (!empty($url_args[GprFields::CHANGE_TOKEN]) && !empty($url_args[GprFields::NEW_TOKEN])) {
            $this->changeSessionToken($url_args);
            unset($url_args[GprFields::CHANGE_TOKEN], $url_args[GprFields::NEW_TOKEN]); 
            $queryString = http_build_query($url_args);
            $url = str_contains($url, '?') ? preg_replace('/\?.*/', "?$queryString", $url) : "$url?$queryString";
        }

        return $url;

        //$url_key = $this->doIframe($gp) ? 'iframe_url' : 'launch_url';

        // phive('Logger')->getLogger('casino')->debug('DEP URL: ', ['base' => $base, 'base' => $base]);

    }

    public function getDepUrl($gid, $lang, $game = null, $show_demo = false, $apiTestingUserId = null) {
        $g = phive('MicroGames')->getByGameId($gid, 0, null);
        return $this->depUrlCommon([
            'game'              => $g,
            GprFields::LANGUAGE => $lang,
            'client'            => 'desktop',
            'testUId'           => $apiTestingUserId
        ]);
    }

    public function getMobilePlayUrl($gref, $lang, $lobby_url = null, $g = null, $args = [], $show_demo = false) {
        $g = phive('MicroGames')->getByGameRef($gref, 1, null);
        if(empty($g)) {
            $g = phive('MicroGames')->getByGameId($gref,1);
        }

        return $this->depUrlCommon([
            'game'              => $g,
            GprFields::LANGUAGE => $lang,
            'client'            => 'mobile'
        ]);
    }

    // TODO when everything has been moved to Gpr we can have only one arg: $entry
    public function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry) {
        if(empty($entry)){
            return false;
        }

        $bonus = $this->frbCommon($entry);

        $params = [
            'iso3_country'                  => phive('Cashier')->getIso3FromIso2($this->cur_user->getCountry()),
            GprFields::BRAND_BONUS_ENTRY_ID => $entry['id'],
            GprFields::BRAND_BONUS_ID       => $bonus['id'],
            GprFields::GP_BONUS_ID          => $this->getExtFrbIds($bonus),
            GprFields::GAME_ID              => $this->getGpGameId(),
            'frb_end_date'                  => $entry['end_time'],
            'frb_start_date'                => $entry['start_time'],
            'frb_count'                     => $entry['frb_granted'],
            GprFields::CURRENCY             => $this->cur_user->getCurrency(),
            'frb_denomination'              => $bonus['frb_denomination'],
            'frb_coins'                     => $bonus['frb_coins'],
            'frb_lines'                     => $bonus['frb_lines'],
            'frb_total_round_value'         => $this->getFrbTotalRoundValue($bonus),
            'frb_cost'                      => $bonus['frb_cost'],
            'action'                        => 'awardFrb',
            'gp'                            => $this->gp,
            GprFields::LANGUAGE             => $this->getGpLanguage($this->cur_user->getLang()),
        ];

        $res = $this->request($params);

        if(!$res['success']){
            phive('Logger')->getLogger('casino')->debug('AWARD FRB Failed Params: ', [$params]);
            phive('Logger')->getLogger('casino')->debug('AWARD FRB Failed Result: ', [$res]);
            return false;
        }

        $res = $res['result'];
        phive('Logger')->getLogger('casino')->debug('AWARD FRB Params: ', [$params]);
        phive('Logger')->getLogger('casino')->debug('AWARD FRB Full Result: ', [$res]);
        phive('Logger')->getLogger('casino')->debug('AWARD FRB RETURN: ', [$res[GprFields::GP_BONUS_ENTRY_ID]]);

        // phive('Logger')->getLogger('casino')->debug('AWARD FRB RETURN: ', [$res[GprFields::GP_BONUS_ENTRY_ID]]);

        // In case the GP does not return its own bonus entry id (eg Pragmatic) we just make one up.
        return empty($res[GprFields::GP_BONUS_ENTRY_ID]) ? uniqid() : $res[GprFields::GP_BONUS_ENTRY_ID];
    }


    public function getGpConfig($gp, $key){
        return $this->getSetting('gp_config')[$gp][$key];
    }

    public function fsBonusOverride($user_id, $bonus){
        $gp = $bonus['bonus_tag'];
        if($gp == 'playngo'){
            if (in_array(cu($user_id)->getCurrency(), $this->getGpConfig($gp, 'currencies_override_cost') ?? ['CAD'])) {
                $bonus['frb_cost'] *= 2;
            }
            return $bonus;
        }
        return false;
    }

    /**
     * Activates FRB / freespin bonuses.
     *
     * @param array $entry The bonus entry.
     * @param $round_m TODO henrik remove this, refactor all invocations.
     * @param array $bonus The bonus.
     * @param string $ext_id The external id of the freespin bonus.
     *
     * @return void
     */
    function activateFreeSpin(&$entry, $na, $bonus, $ext_id) {
        // $this->frbCommon($entry);
        // If we have to keep track of the FRB and decrement spins ourselves etc then we need to have
        // the FRB initial status be active so we can differentiate from standard behaviour of just
        // sending one win with the total upon FRB completion.
        // $entry['status'] = $this->doTrackFrb() ? 'active' : 'approved';
    }

    public function frbCommon($entry){
        $bonus          = phive('Bonuses')->getBonus($entry['bonus_id']);
        $this->cur_user = cu($entry['user_id']);
        $game = phive('MicroGames')->getByGameId($bonus['game_id'], 0, $this->cur_user);

        $this->cur_game = phive('MicroGames')->overrideGame($this->cur_user, $game);

        //phive('Logger')->getLogger('casino')->debug('AWARD FRB GAME: ', [$entry, $game, $bonus, $gref, $this->cur_game]);

        $this->gp       = $bonus['bonus_tag'];
        return $bonus;
    }

    public function getExtFrbIds($bonus){
        $ids = phive('Bonuses')->getExtFrbIds($bonus);
        if(is_array($ids)){
            if(!empty($ids[0])){
                // Numerical array so we just return all the ids.
                return $ids;
            }

            // It's a dual formatted string which means we get the id assigned to the player's jurisdiction.
            return $ids[ licJur($this->cur_user) ] ?? $ids['ROW'] ?? $ids['DEFAULT'];
        }

        return $ids;
    }

    public function request($params, $api_url_key = 'api_url'){
        $params['jurisdiction'] = $this->licJur($this->cur_user);
        $params['country']      = $this->cur_user->getCountry();
        $params['brand']        = $this->getSetting('brand_name');
        // During tournament mode, the user_id must have the format `{USER_ID}e{TOURNAMENT_ENTRY_ID}`.
        // Calling the method {$this->cur_user->getId()} (during a tournament) would send a user_id with an incorrect formatting.
        if (!$this->isTournamentMode() || empty($params[GprFields::UID])) {
            $params[GprFields::UID] = $this->cur_user->getId();
        }
        $url                    = $this->getSetting($api_url_key);
        $debug_key              = $this->getSetting('log_out_calls') ? 'gpr_out' : '';
        $res                    = phive()->post($url, $params, 'application/json', '', $debug_key);
        $res_arr                = json_decode($res, true);
        return $res_arr;
    }

    public function cancelFRBonus($user_id, $entry_id) {
        $entry = phive('Bonuses')->getBonusEntry($entry_id, $user_id);
        if(empty($entry)){
            return false;
        }

        $bonus = $this->frbCommon($entry);

        $params = [
            GprFields::BRAND_BONUS_ENTRY_ID => $entry['id'],
            GprFields::GP_BONUS_ENTRY_ID    => $entry['ext_id'],
            GprFields::GP_BONUS_ID          => $this->getExtFrbIds($bonus),
            GprFields::GAME_ID              => $this->getGpGameId(),
            'action'                        => 'cancelFrb',
            'gp'                            => $this->gp,
        ];

        $res = $this->request($params);

        return $res['success'];
    }

    public function login($params){
        $params['action']           = 'login';
        $params[GprFields::GAME_ID] = $this->getGpGameId();
        $params['url_cashier']      = $this->getCashierUrl(false, $params['original_language'], $params['client']);
        $params['url_rg']           = phive('Licensed')->getRespGamingUrl($this->cur_user, $params['original_language']);
        $params['url_lobby']        = $this->getLobbyUrl(false, $params['original_language'], $params['client']);
        $res = $this->request($params);

        if (empty($res)) {
            return $this->logLoginErrorAndReturn('LOGIN CALL: Empty result', [], Errtype::UNKNOWN);
        }

        if ($this->isError($res)) {
            return $this->logLoginErrorAndReturn('LOGIN CALL:', ['params' => $params, 'response' => $res]);
        }

        return $res['result'];
    }

    /**
     * Get the freespin bet value calculated by the frb_denomination, frb_lines and frb_coins
     *
     * @param array $bonus The bonus to work with.
     *
     * @return int the amount in cents
     */
    public function getFrbTotalRoundValue($bonus)
    {
        $value = $bonus['frb_denomination'];

        if (!empty($bonus['frb_coins'])) {
            $value *= $bonus['frb_coins'];
        }

        if (!empty($bonus['frb_lines'])) {
            $value *= $bonus['frb_lines'];
        }

        return mc($value, $this->cur_user->getCurrency(), 'multi', false);
    }

    /**
     * Update game in session
     * @param $token_data
     * @param $curGame
     * @return void
     */
    private function updateOriginalGame(&$token_data, $curGame)
    {
        $originalGameId = $token_data['original_game']['id'] ?? null;
        $curGameId = $curGame['id'] ?? null;

        if ($originalGameId !== $curGameId) {
            $token_data['original_game'] = $curGame;
            $this->updateSession('original_game', $token_data['original_game']);
        }
    }

    /**
     * Get the game from the session
     * @$params $token_data
     * @return mixed
     */
    private function logLoginErrorAndReturn($message, $data, $errorType = null) {
        phive('Logger')->getLogger('casino')->error($message, $data);
        return $errorType ? $this->error(Errtype::UNKNOWN, 'Empty result') : $data['response'];
    }

    public function adjustment($params)
    {
        $transaction_type = TransactionType::ADJUST_TYPE;
        if (empty($this->cur_user)) {
            return $this->error(500, 'user Not found');
        }
        $balance = $this->cur_user->getCurAttr('cash_balance');

        $description = sprintf('PragmaticAdjustBalance:%d:%d:%d:%s:%d:%s',
            $params['gameId'], $params['round_id'], $params['amount'],
            $params['provider_id'], $params['validBetAmount'], $params['reference']);

        // check if a transaction already exists against this JackpotWin, to avoid adding balance multiple times
        $existing_transaction = phive('Cashier')->getTransaction(cu($this->cur_user), [
            'bonus_id' => $params['roundId'],
            'transactiontype' => $transaction_type,
            'amount' => $params['amount'],
            'description' => $description
        ]);
        if (($params['amount'] < 0 && (abs($params['amount']) > $balance))) {
            return $this->error(Errtype::INSUFFICIENT_BALANCE);
        } else {
            if (!empty($existing_transaction)) {
                return $this->betWinCommonReturn($existing_transaction['id'], $balance);
            } else {

                $transaction_id = phive('Cashier')->transactUser(
                    $this->cur_user,
                    $params['amount'],
                    $description,
                    null,
                    null,
                    $transaction_type,
                    false,
                    $params['roundId']
                );

                if ($transaction_id) {
                    return $this->betWinCommonReturn($transaction_id, $this->cur_user->getCurAttr('cash_balance'));
                } else {
                    $response = $this->error(500, "Internal error");
                }
            }
        }
        return $response;
    }

    /**
     * @param int $bet_id
     * @param string $mg_id
     * @return int
     */
    private function getBonusBetTypeFromBet($bet_id = null, $mg_id = null) {
        if(!is_null($bet_id)) {
            $where = 'id = ' . $bet_id;
            return phive('SQL')->sh($this->cur_user->getId())->getValue('', 'bonus_bet', 'bets', $where);
        }elseif (!is_null($mg_id)){
            $where = 'mg_id like ' . $mg_id;
            return phive('SQL')->sh($this->cur_user->getId())->getValue('', 'bonus_bet', 'bets', $where);
        }
    }

    /**
     * Get the GPR filter string.
     *
     * This method retrieves the GPR settings from the 'Casino' service,
     * formats them into a string, and returns a SQL clause that can be
     * used to filter games based on their network.
     *
     * @return string The GPR filter string.
     */
    public function getGprFilter(bool $and = true): string
    {
        $gps_list = phive('Casino')->getSetting('gps_via_gpr');
        $gps_list = array_map(function($item) {
            $normalizedItem = strtolower($item);
            return $this->mapping[$normalizedItem] ?? $item;
        }, $gps_list);

        $gps_list = implode("','", $gps_list);
        $sqlClause = "mg.network IN ('$gps_list')";

        if ($and) {
            return " AND " . $sqlClause;
        } else {
            return $sqlClause;
        }
    }

    /**
     * @param $win_amount
     * @param $args
     * @param $start_balance
     * @param $bonus_bet_type
     * @param $award_type
     * @param null $bonus_bet_type_from_bets
     * @return array
     */
    public function insertWinReplaced(
        $win_amount,
        $args,
        $start_balance,
        $bonus_bet_type,
        $award_type,
        $bonus_bet_type_from_bets = null
    ) {
        $win_amount = $win_amount ?? $args[GprFields::AMOUNT] ?? 0;

        $win_id = $this->insertWin(
            $this->cur_udata,
            $this->cur_game,
            $start_balance,
            $this->getGpRoundCount($args),
            $win_amount,
            $bonus_bet_type,
            $this->getToDbGpTransId($args[GprFields::TR_ID]),
            $award_type,
            '',
            $bonus_bet_type_from_bets
        );

        if ($win_id === false) {
            return $this->error(Errtype::DB_ERROR);
        }
        return $win_id;
    }

    /**
     * This function verifies if a bet violates UKGC max bet limits or not
     *
     * @param int|false $betLimit The bet limit of the player. Numeric if they have one, false  if not applicable
     * @param int|float $betAmount The monetary amount the player tried to bet
     * @param bool|null $shouldValidateUkgcMaxBet A parameter passed from the GpFrom classes indicating we should verify
     * ourselves if the player has tried to bet over the max bet limit. Example: IsoftbetFrom
     * @param string $jurisdiction The jurisdiction of the player
     * @return bool
     */
    public function violatesUkgcMaxBet($betLimit, $betAmount, $shouldValidateUkgcMaxBet, $jurisdiction): bool
    {
        $bettingLimitsConfig = phive('Licensed')->getLicSetting('betting_limits', $jurisdiction);

        $isBlockedCategory = in_array($this->cur_game['tag'], $bettingLimitsConfig['BLOCKED_CATEGORIES']);
        if($betLimit && $betAmount > $betLimit && $shouldValidateUkgcMaxBet && $isBlockedCategory){
            return true;
        }

        return false;
    }
}
