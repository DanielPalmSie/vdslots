<?php

use Videoslots\HistoryMessages\BonusCancellationHistoryMessage;
use Videoslots\HistoryMessages\CashTransactionHistoryMessage;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Carbon\Carbon;

require_once __DIR__ . '/Bonuses.php';

/**
 * The basic bonus class, it is the base class that is powerin Casino specific logic / requirements.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_failed_bonuses The wiki docs for the failed bonuses table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_bonus_types The wiki docs for the bonus_types (AKA bonus table) table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_bonus_entries The wiki docs for the bonus_entries table.
 */
class CasinoBonuses extends Bonuses {

    protected const FRB_COST = 51;
    protected const CASINO_TOURNAMENT_HOUSE_FEE = 52;
    protected const CASH_BALANCE_BONUS_CREDIT = 66;
    protected const WAGER_BONUS_CREDIT = 68;
    protected const WAGER_BONUS_DEBIT = 70;


    /**
     * This is how we're able to do phive('Bonuses') to get an instance of this class.
     *
     * @return array An array with Bonuses in it.
     */
    function phAliases(){
        return ['Bonuses'];
    }

    /**
     * Takes a trophy or bonus id and if the Casino has trophies gives the player the Trophy with that id, otherwise the Bonus with that id.
     *
     * @param int $id The Trophy or Bonus id.
     * @param DBUser $user The user object to reward the Trophy or Bonus to.
     *
     * @return null
     */
    public function addTrophyOrBonus($id, $user){
        if(phive()->moduleExists('Trophy')){
            phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(1.1)', [
                'user_id' => $user->getId(),
                'bonus' => $id,
            ]);
            phive('Trophy')->giveAward($id, $user->data, 5000000);
        }else{
            phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(1.2)', [
                'user_id' => $user->getId(),
                'bonus' => $id,
            ]);
            $this->addUserBonus($user->getId(), $id, true);
        }
    }

    /**
     * Returns a localized string alias, the default if the bonus is not configured to keep winnings,
     * if it is configured to keep winnings we return the default with keep.winnings. prefixed.
     *
     * @param string $default_string The default alias.
     * @param int $uid User id.
     *
     * @return string The result alias.
     */
    function getBonusString($default_string, $uid = ''){
        if(empty($uid))
            $uid = uid();
        if(empty($uid))
            return $default_string;
        $bonus = $this->getUserBonuses($uid, '', "= 'active'")[0];
        if(empty($bonus['keep_winnings']))
            return $default_string;
        return 'keep.winnings.'.$default_string;
    }

    /**
     * Main logic to run when adding a bonus to a user.
     *
     * Here we do the following:
     *
     * 1. Check if the user can actually get the bonus, ie the bonus is an email promo and the user has gotten the email,
     * this is done via checkBonusEmail().
     * 2. Check exclusivity, ie if there are already some bonus active on this user that prevents the bonus in question
     * from being added.
     * 3. If all good we start to build the bonus entry from the bonus type information. If we are to activate the bonus
     * right away additional logic is run and info added to the entry. If we're looking at an FRB we try to make a call to
     * the GP to activate the freespins on their side.
     *
     * @uses CasinoBonuses::checkBonusEmail()
     * @uses CasinoBonuses::handleExclusive()
     * @uses CasinoBonuses::activatePendingEntry()
     * @uses CasinoBonuses::addTrophyAward()
     * @uses CasinoBonuses::addFreeSpin()
     *
     * @see CasinoBonuses::checkBonusEmail()
     * @see CasinoBonuses::handleExclusive()
     * @see CasinoBonuses::activatePendingEntry()
     * @see CasinoBonuses::addTrophyAward()
     * @see CasinoBonuses::addFreeSpin()
     *
     * @param int $user_id The user id.
     * @param int $bonus_id The bonus id.
     * @param bool $activate Whether or not to activate right away, note that if the bonus is a freespin bonus we call the
     * GP regardless of what this boolean indicates.
     * @param $renew=false TODO henrik remove this
     * @param $start_date=null TODO henrik remove this
     * @param $trans_type=14 TODO henrik remove this, hardcode 14
     * @param bool $show_event Whether or not to show the FRB activation event in the newsfeed.
     *
     * @return int|string Can return new id or error string.
     */
    function addUserBonus($user_id, $bonus_id, $activate = false, $renew = false, $start_date = null, $trans_type = 14, $show_event = true){
        phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(1)', [
            'user_id' => $user_id,
            'bonus' => $bonus_id,
        ]);
        $bonus = $this->getBonus($bonus_id, false);

        $user = cu($user_id);
        if(!$this->checkBonusEmail($user, $bonus_id))
            return false;

        $exclusive_res = $this->handleExclusive($bonus, $user_id);

        if($bonus && $exclusive_res  === true){
            phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(2)', [
                'user_id' => $user_id,
                'bonus' => $bonus_id,
            ]);

            $inserts = array('bonus_id' => $bonus['id']);

            $keys = array("user_id", "cost", "reward");

            if(in_array($bonus['bonus_type'], array('casino', 'casinowager', 'freespin'))){
                foreach(array('game_tags', 'game_percents', 'loyalty_percent', 'bonus_tag', 'progress_type', 'allow_race') as $f)
                    $keys[] = $f;
            }

            if($bonus['bonus_type'] == 'casino')
                $inserts['balance'] = $bonus['reward'];

            if($bonus['bonus_type'] == 'casinowager')
                $keys[] = 'stagger_percent';
            //FRB logic
            if($bonus['bonus_type'] == 'freespin')
                $inserts['frb_granted'] = $inserts['frb_remaining'] = $bonus['reward'];

            foreach($keys as $key)
                $inserts[$key] = $bonus[$key];

            $inserts['user_id'] = $user_id;

            // TODO this is a bit of a duplicate of activatePendingEntry(), could it be refactored away? /Henrik
            if($activate){
                $start_time = date("Y-m-d");

                phive('Logger')->getLogger('casino')->debug('double-bonus-transactions addUserBonus bonus_activation', [
                    'user_id' => $user_id,
                    'bonus_id' => $bonus_id,
                    'reward' => $bonus['reward'],
                    'bonus_type' => $bonus['bonus_type'],
                    'start_time' => $start_time,
                    'transaction_type' => $trans_type,
                    'time' => date('Y-m-d H:i:s')
                ]);

                if($this->addActivation($inserts, $bonus, $user_id, $start_time) && $bonus['bonus_type'] != 'freespin')
					phive('Cashier')->insertBonusActivation($user_id, $bonus['reward'], $trans_type);
					// phive('Cashier')->insertBonusActivation($user_id, $bonus['reward'], $bonus['id'], $bonus['bonus_type']);

                $inserts['bonus_type']  = $bonus['bonus_type'];
                $this->addCashCost($inserts, $bonus);
                $this->addBetLimits($inserts, $bonus);
            }

            if($start_date){
                $inserts['start_time'] 	= $start_date;
                $inserts['end_time'] 	= date("Y-m-d", strtotime("+ ".$bonus['num_days']." days", strtotime($start_date)));
            }

            if (!isset($inserts['progress'])) {
                $inserts['progress'] = 0;
            }

            if (!isset($inserts['balance'])) {
                $inserts['balance'] = 0;
            }

            if($renew || $bonus['renew'])
                $inserts['renew'] = 1;

            $new_id = phive("SQL")->sh($user_id, '', 'bonus_entries')->insertArray('bonus_entries', $inserts);

            phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(3)', [
                'user_id' => $user_id,
                'bonus' => $bonus_id,
                'bonus_entry' => $new_id,
                'bonus_entry_inserts' => $inserts
            ]);

            if($activate)
                $this->activatePendingEntry($new_id, $user_id);

            $this->addTrophyAward($new_id, $user->data, $bonus);

            if($bonus['bonus_type'] == 'freespin'){
                phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(4)', [
                    'user_id' => $user_id,
                    'bonus' => $bonus_id,
                    'bonus_entry' => $new_id,
                ]);

                if($this->addFreeSpin($bonus, $new_id, 100, $show_event) === false)
                    return 'connection.error';
            }

			$this->updateWelcomeBonusEntry($user_id, $bonus_id, $new_id);

            return $new_id;
        }

        return $exclusive_res;
    }

    /**
     * Simple checks the result of the bonus adding, if it is false or a string (error message) the
     * bonus add was not successful.
     *
     * @param mixed $entry_id Most probably the new bonus entry id.
     *
     * @return bool True if the entry id is false or a string (error message), false otherwise.
     */
    function isAddedError($entry_id){
        return $entry_id === false || !is_numeric($entry_id);
    }

    /**
     * Adds a suffix to an error string.
     *
     * @param string $alias The extra localized string alias to add to the current alias.
     * @param mixed $entry_id The new entry id in case of success, false or a localized string alias if not.
     *
     * @return string The complete localized string alias.
     */
    function addErrorSuffix($alias, $entry_id){
        if($entry_id === false)
            return $alias;
        if(!is_numeric($entry_id) && strpos($entry_id, ' ') === false)
            return $alias.'.'.$entry_id;
        return $alias;
    }

    /**
     * Gets wagering progress of a bonus entry with wagering turnover requirements.
     *
     * @uses Casino::getRtpProgress() In order to calculate the progress in case the bonus
     * progress needs to be based on the game's RTP (ie games with lower RTP have more / faster progress).
     * @see Casino::getRtpProgress()
     *
     * @param array &$game The game being played.
     * @param array &$entry The bonus entry.
     * @param int $amount The wagered amount.
     *
     * @return float The progress.
     */
    public function getBonusProgress(&$game, &$entry, $amount)
    {
        $tag_arr = explode(',', $entry['game_tags']);
        $per_arr = explode(',', $entry['game_percents']);

        if (!empty($game['sub_tag']) && in_array($game['sub_tag'], $tag_arr)) {
            $percent = $per_arr[array_search($game['sub_tag'], $tag_arr)];
        } else {
            $percent = $per_arr[array_search($game['tag'], $tag_arr)];
        }

        if (trim($percent) === 'rtp') {
            return phive('Casino')->getRtpProgress($amount, $game, $entry['user_id']);
        }

        return $amount * (!isset($percent) ? 1 : $percent);
    }

    /**
     * Gets all entries of a certain bonus.
     *
     * @param int $bonus_id The bonus id.
     * @param array $statuses The statuses to filter on.
     *
     * @return array The bonus entries.
     */
    function getEntries($bonus_id, $statuses){
        $where = empty($statuses) ? '' : " AND status IN(".phive("SQL")->makeIn($statuses).")";
        return phive("SQL")->shs('merge', '', null, 'bonus_entries')->arrayWhere('bonus_entries', " bonus_id = $bonus_id $where ");
    }

    // TODO henrik remove
    function archive($date){
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        $date = empty($date) ? phive()->modDate('', '-3 month', 'Y-m-01') : $date;
        $sql = phive("SQL");
        $archive = $sql->doDb('archive');
        $sql->updateTblSchema('bonus_entries');
        $sql->updateTblSchema('bonus_types');
        $date = phive('SQL')->escape($date,false);
        foreach($sql->loadArray("SELECT * FROM bonus_types WHERE expire_time < '$date' AND brand_id = {$brandId}") as $bt){
            $active = $this->getEntries($bt['id'], 'active');
            if(empty($active)){
                if($archive->insertArray('bonus_types', $bt)){
                    $sql->delete("bonus_types", ['id' => $bt['id']]);
                    foreach($this->getEntries($bt['id']) as $e){
                        if($archive->insertArray('bonus_entries', $e))
                            $sql->delete("bonus_entries", ['id' => $e['id']], $e['user_id']);
                    }
                }
            }
        }
    }

    /**
     * Gets the bonus name from the bonus id.
     *
     * @param int $id The bonus id.
     *
     * @return string The name.
     */
    function nameById($id){
        return phive('SQL')->getValue("SELECT bonus_name FROM bonus_types WHERE id = $id");
    }

    // TODO henrik remove
  function getBonusProfit($user){
    $profit = 0;
    $bonuses = $this->getUserBonuses($user->getId(), '', "IN('active')", " = 'casino'");
    if(!empty($bonuses))
      $profit = phive('Cashier')->getDepBonusProfit($user->getId());
        return $profit;
  }

    /**
     * Checks whether or not a bonus can be used by a certain user, based on the country the user is from.
     *
     * @param array $bonus The bonus.
     * @param DBUser $user The user.
     *
     * @return bool|string True if yes, a localized string alias if not.
     */
    function allowCountry($bonus, $user){
        if(empty($bonus))
            return true;
        $user_country = strtolower($user->getAttr('country'));
        if(!empty($bonus['included_countries']) && strpos(strtolower($bonus['included_countries']), $user_country) === false)
            return 'voucher.not.in.included.country';

        if(strpos(strtolower($bonus['excluded_countries']), $user_country) !== false)
            return 'voucher.excluded.country';

        return true;
    }

    /**
     * Adds a deposit bonus to the user and returns that bonus if success, false otherwise.
     *
     * @uses CasinoBonuses::addDepositBonus()
     * @uses CasinoBonuses::getDeposits()
     * @see CasinoBonuses::addDepositBonus()
     * @see CasinoBonuses::getDeposits()
     *
     * @param DBUser $user The user object.
     * @param string $bonus_code The bonus code.
     * @param int $cents The deposit amount.
     *
     * @return array|bool False if fail, the bonus if success.
     */
    public function addDepositBonusAndReturn($user, $bonus_code, $cents)
    {
        $lic_bonus = lic('getFirstDepositBonus', [], $user);
        $b = empty($lic_bonus) ? $this->getDeposits(phive()->today(), $bonus_code)[0] : $lic_bonus;
        if (!empty($b)) {

            phive('Logger')->getLogger('casino')->debug('double-bonus-transactions addDepositBonusAndReturn', [
                'user_id' => $user->getId(),
                'bonus_id' => $b,
                'deposit' => $cents,
                'time' => date('Y-m-d H:i:s')
            ]);

            $entry_id = $this->addDepositBonus($user->getId(), $b, $cents);
            if (!$this->isAddedError($entry_id)) {
                return $b;
            }
        }
        return false;
    }

    /**
     * Is being run on first deposit to potentially add eligible a first deposit bonus.
     *
     * @param DBUser $user The user object.
     * @param int $cents The deposit amount.
     *
     * @return array|bool False if fail, the bonus if success.
     */
    public function handleFirstDeposit($user, $cents)
    {
        phive('Logger')->getLogger('casino')->debug('double-bonus-transactions handleFirstDeposit', [
            'user_id' => $user->getId(),
            'bonus_code' => $user->getAttr('bonus_code'),
            'deposit' => $cents,
            'time' => date('Y-m-d H:i:s')
        ]);

        $bcode_res = $this->addDepositBonusAndReturn($user, $user->getAttr('bonus_code'), $cents);
        if (!$bcode_res) {

            phive('Logger')->getLogger('casino')->debug('double-bonus-transactions handleFirstDeposit second', [
                'user_id' => $user->getId(),
                'bonus_code' => '',
                'deposit' => $cents,
                'time' => date('Y-m-d H:i:s')
            ]);

            $bcode_res = $this->addDepositBonusAndReturn($user, '', $cents);
        }

        $this->addTrophyBonus($user);

        return $bcode_res;
    }

    /**
     * Logic executed to award free spins
     * Initially logic was placed inside registrationEnd in DBUserHandler class. Moved and will be called in handleFirstDeposit to ensure free spins are provided only after first deposit.
     *
     * @param DBUser|string $user
     * @return void
     */
    public function addTrophyBonus($user)
    {
        $fspin = phive('Config')->getByTagValues('freespins');

        $custom_frb_id = $fspin[cuAttr('bonus_code')];

        $can_do = function($key) use ($user, $fspin){
            return in_array(strtolower($user->getAttribute('country')), explode(',', strtolower($fspin[$key])));
        };

        $can_netent = $can_do('netent-reg-bonus-countries');
        $can_normal = $can_do('normal-reg-bonus-countries');
        $can_default = $can_do('reg-bonus-countries');
        $can_reg_reward = $can_do('reg-award-countries');

        if (!empty($custom_frb_id) && $can_netent) {
            $this->addTrophyOrBonus($custom_frb_id, $user);
        } else if (!empty($_SESSION['reg-game-bonus-id']) && $can_netent) {
            $this->addTrophyOrBonus($_SESSION['reg-game-bonus-id'], $user);
            unset($_SESSION['reg-game-bonus-id']);
        } else if (!empty($fspin['netent-reg-bonus-id']) && $can_netent) {
            $this->addTrophyOrBonus($fspin['netent-reg-bonus-id'], $user);
        } else if (!empty($fspin['normal-reg-bonus-id']) && $can_normal) {
            $this->addTrophyOrBonus($fspin['normal-reg-bonus-id'], $user);
        } else if (!empty($fspin['reg-bonus-id']) && $can_default) {
            $this->addTrophyOrBonus($fspin['reg-bonus-id'], $user);
        }
        if (!empty($fspin['reg-award-id']) && $can_reg_reward) {
            // 7 day freeroll BoS
            $this->addTrophyOrBonus($fspin['reg-award-id'], $user);
        }
        $free_m = phive('Config')->getByTagValues('free-money');

        $countries = explode(' ', strtolower($free_m['countries']));

        if($free_m['on'] == 1 && in_array(strtolower($user->getAttribute('country')), $countries) && !empty($free_m['amount-registration'])){
            phive('QuickFire')->changeBalance($user, $free_m['amount-registration'], '#welcome.deposit', 14);
            uEvent('bonuspayout', $free_m['amount-registration'], '', '', $user->data);
        }
    }

    /**
     * Gets the aggregated total of all bonus balances.
     *
     * @param string $status Status filter.
     * @param bool $change Whether or not to return the result FX'ed to the casino default / base currency.
     * @param string $currency ISO3 currency code in case we only want the balances that are denominated
     * in a specific currency (ie via the inner join on the users table).
     *
     * @return int The total balance.
     */
    function getTotalBalances($status = " = 'active' ", $change = false, $currency = null){
        $currency = phive('SQL')->escape($currency,false);
        if($change){
            $str = "
                SELECT SUM(be.balance / c.multiplier) AS amount
                FROM bonus_entries be
                INNER JOIN users AS u ON u.id = be.user_id
                INNER JOIN currencies AS c ON u.currency = c.code
                WHERE status $status";
        }elseif(!empty($currency)){
            $str = "
                SELECT SUM(be.balance)
                FROM bonus_entries be
                INNER JOIN users AS u ON u.id = be.user_id
                WHERE be.status $status
                AND u.currency = '$currency'";
        }else{
             $str = "SELECT SUM(balance) FROM bonus_entries WHERE status $status";
        }

        return current(phive('SQL')->shs('sum', '', null, 'bonus_entries')->loadArray($str)[0]);
    }

	/**
	 * Gets the bonus progression.
	 *
	 * @param array &$b The bonus entry.
	 * @param bool $as_partial This should be $as_float, if true we just return progress divided by cost.
	 * If false we want a number like this: 12.65%
	 *
	 * @return float The progression.
	 */
	function progressPercent(&$b, $as_partial = false){
		if($b['cost'] == 0 && $b['progress'] == 0 && $b['status'] == 'approved'){
			return $as_partial ? 1 : 100;
		}

		if($as_partial){
			return $b['progress'] / $b['cost'];
		}

		return substr(number_format(min(100, ($b['progress'] / $b['cost']) * 100 ), 4), 0, 5);
	}

	/**
     * This method must never be called with user supplied data for $status as it just goes straight into the query.
     *
     * @param mixed $user_id The user id or object.
     * @param string $status The status filter SQL string.
     *
     * @return array An array of bonus_entries.
     */
    public function getUserEntries($user_id, $status = " = 'active'"){
        $user_id = uid($user_id);
        $where_status = empty($status) ? '' : " AND status $status ";
        $sql = "SELECT * FROM bonus_entries WHERE user_id = $user_id $where_status";
        return phive('SQL')->sh($user_id)->loadArray($sql);
    }

    /**
     * Retrieves the latest active and cashable bonus entry for a given user at the time of a bet.
     *
     * This function fetches the most recent bonus from the `bonus_entries` table for the specified user
     * where the bonus was active at the given bet creation date. It considers only bonuses of type
     * 'casino' or 'freespin' with a rake percentage greater than 0. The result is ordered by entry ID
     * in descending order, returning the latest matching record.
     *
     * @param int $user_id The ID of the user whose bonus is being queried.
     * @param string $bet_creation_date The timestamp of the bet (in a format compatible with SQL datetime).
     *
     * @return array|null Returns the bonus entry as an associative array if found, or null otherwise.
     */
    public function getLatestCashableForfeitedBonus(int $user_id, string $bet_creation_date): ?array {
        $user_id = phive('SQL')->escape($user_id, false);
        $bet_creation_date = phive('SQL')->escape($bet_creation_date, false);

        $query = "
            SELECT
                be.id,
                be.status
            FROM bonus_entries AS be
                INNER JOIN bonus_types AS bt ON be.bonus_id = bt.id
            WHERE
                be.user_id = $user_id
                AND '$bet_creation_date' BETWEEN be.activated_time AND be.last_change
                AND (
                    (be.bonus_type = 'casino')
                    OR
                    (be.bonus_type = 'freespin' AND bt.rake_percent > 0)
                )
                AND be.status = 'failed'
                AND be.activated_time != '0000-00-00 00:00:00'
            ORDER BY be.bonus_type ASC, be.last_change DESC
            LIMIT 1
        ";

        $bonus_entry = phive('SQL')->sh($user_id)->loadArray($query);
        $bonus_entry = reset($bonus_entry);

        return !empty($bonus_entry) ? $bonus_entry : null;
    }

    /**
     * Gets all the user's bonus entries, joined with the bonus_types table.
     *
     * @param int $user_id The user id.
     * @param int $limit The $limit in LIMIT 0, $limit
     * @param string $status The bonus entry status.
     * @param string $bonus_type The bonus type.
     * @param $add_archive // TODO henrik remove this, don't forget to refactor all invocations.
     * @param array $dates Start and end date with the start date in position 0 and end date in position 1.
     *
     * @return array The result array.
     */
    function getUserBonuses($user_id, $limit = '', $status = '', $bonus_type = '', $add_archive = false, $dates = array()){
        $limit   = empty($limit)   ? '' : "LIMIT 0,$limit";
        $status  = empty($status)  ? '' : "AND be.status $status";
        $bonus_type = empty($bonus_type)   ? '' : "AND be.bonus_type $bonus_type";
        $where_time = empty($dates) ? '' : phive('SQL')->tRng($dates[0], $dates[1], $x);

        $sql = "SELECT be.*, bt.bonus_name, bt.bonus_tag, bt.exclusive, bt.deposit_multiplier, bt.deposit_limit, bt.type, bt.keep_winnings, bt.reward AS frb_spins
                    FROM bonus_entries be, bonus_types bt
                    WHERE be.user_id = $user_id
                    AND be.bonus_id = bt.id
                    $where_time
                    $status
                    $bonus_type
                    ORDER BY `last_change` DESC $limit";

        $res = phive("SQL")->sh($user_id, '', 'bonus_entries')->loadArray($sql);

        return $res;
    }

    // TODO henrik remove
    function getReactivateCtr($bonus_id, $user_id, $end_time, $tr_type){

        if(is_array($tr_type)) {
            $tr_type = implode(',', $tr_type);
            $type = "transactiontype IN($tr_type)";
        } else {
            $type = "transactiontype = $tr_type";
        }

        return phive('SQL')->sh($user_id, '', 'cash_transactions')->loadAssoc("SELECT * FROM cash_transactions WHERE bonus_id = $bonus_id AND user_id = $user_id AND DATE(timestamp) = '$end_time' AND $type ORDER BY id DESC LIMIT 0,1");
    }

    /**
     * Gets the numerical transaction type with the help of the bonus action and bonus type.
     *
     * TODO henrik having a default empty string for $action does not make sense, remove it.
     *
     * @see CasinoCashier::getTransactionTypes()
     *
     * @param string $bonus_type The bonus type.
     * @param string $action Is credit, debit or shift
     *
     * @return int The transaction type.
     */
    function getTransactionType($bonus_type, $action = ''){
        $map = [
            'credit' => [
                'casino' => 66,
                'casinowager' => 68,
                'freespin' => 51
            ],
            'shift' => [
                'casino' => 4,
                'casinowager' => 69,
                'freespin' => 71
            ],
            'debit' => [
                'casino' => 67,
                'casinowager' => 70,
                'freespin' => 72
            ]
        ];
        return $map[$action][$bonus_type];
    }

    // TODO henrik remove
    function reactivateBonusEntry($entry_id, $skip_check = false)  {
        $entry_id = (int)$entry_id;
        $entry     = $this->getBonusEntry($entry_id);
        $bonus_id  = $entry['bonus_id'];
        $b         = $this->getBonus($bonus_id);
        $user_id   = $entry['user_id'];

        if ($this->canReactivate($b, $user_id) !== true && !$skip_check)
            return;

        $end_time  = $entry['end_time'];
        $days      = phive('SQL')->getValue("SELECT num_days FROM bonus_types WHERE id = $bonus_id");
        // need to look for more possible failed bonusses
        $failed_reward_types = array(15, 67, 70, 72, 75, 78, 81);
        $failed_tr = $this->getReactivateCtr($bonus_id, $user_id, $end_time, $failed_reward_types);
        switch($b['bonus_type']){
            case 'casino':
                if(!empty($failed_tr)){
                    $amount = abs($failed_tr['amount']);
                    $extra_update = ", balance = $amount";
                    phive('UserHandler')->logAction($user_id, "Added back $amount to bonus $bonus_id", "bonus_reactivation");
                    $transaction_type = static::CASH_BALANCE_BONUS_CREDIT;
                    $description = 'Bonus reactivation';
                    $transaction_id = phive('Cashier')->insertTransaction($user_id, $amount, $transaction_type, $description, $bonus_id, '', $entry_id);

                    if ($transaction_id) {
                        $user = cu($user_id);
                        $history_message = [
                            'user_id' => (int)$user_id,
                            'transaction_id' => (int)$transaction_id,
                            'amount' => (int)$amount,
                            'currency' => $user->getCurrency(),
                            'transaction_type' => $transaction_type,
                            'parent_id' => (int)$bonus_id,
                            'description' => $description,
                            'event_timestamp' => time(),
                        ];

                        /** @uses Licensed::addRecordToHistory() */
                        lic(
                        'addRecordToHistory',
                            [
                                'cash_transaction',
                                new CashTransactionHistoryMessage($history_message)
                            ],
                            $user
                        );
                    }

                }
                break;
            case 'casinowager':
                $amount = $entry['reward'] - $this->getStaggerPaid($entry);
                $transaction_type = static::WAGER_BONUS_CREDIT;
                $description = 'Casino wager bonus reactivation';
                $transaction_id = phive('Cashier')->insertTransaction($user_id, $amount, $transaction_type, $description, $bonus_id, '', $entry_id);

                if ($transaction_id) {
                    $user = cu($user_id);
                    $history_message = [
                        'user_id' => (int)$user->getId(),
                        'transaction_id' => (int)$transaction_id,
                        'amount' => (int)$amount,
                        'currency' => $user->getCurrency(),
                        'transaction_type' => $transaction_type,
                        'parent_id' => (int)$bonus_id,
                        'description' => $description,
                        'event_timestamp' => time(),
                    ];

                    /** @uses Licensed::addRecordToHistory() */
                    lic(
                        'addRecordToHistory',
                        [
                            'cash_transaction',
                            new CashTransactionHistoryMessage($history_message)
                        ],
                        $user
                    );
                }

                break;
            default:
                break;
        }

        $update    = "UPDATE bonus_entries SET start_time = CURDATE(), end_time = DATE_ADD(CURDATE(), INTERVAL $days DAY), status = 'active' $extra_update WHERE id = $entry_id";
        phive('SQL')->sh($user_id, '', 'bonus_entries')->query($update);
        phive('UserHandler')->logAction($user_id, "Reactivated bonus with id $bonus_id", "bonus_reactivation");

        $failed_winnings_tr = $this->getReactivateCtr($bonus_id, $user_id, $end_time, 53);
        if (!empty($failed_winnings_tr)) {
            $amount = abs($failed_winnings_tr['amount']);
            phive('Cashier')->transactUser($user_id, $amount, 'Bonus reactivation', null, null, 90, true, $bonus_id, $entry_id);
            phive('UserHandler')->logAction($user_id, "Reactivated bonus with id $bonus_id, got $amount back to main balance.", "bonus_reactivation");
        }
    }

    /**
     * Checks if a user has active bonuses or not.
     *
     * @param int $uid The user id.
     *
     * @return bool True if yes, otherwise false.
     */
    function hasActive($uid){
        $bs = $this->getUserBonuses($uid, '', " = 'active'");
        return empty($bs) ? false : true;
    }

    /**
     * Checks if a user has active exclusive bonuses or not.
     *
     * @param int $uid The user id.
     *
     * @return bool True if yes, otherwise false.
     */
    function hasActiveExclusives($uid){
        $bs = $this->getUserBonuses($uid, '', " = 'active'");
        foreach($bs as $b){
            if($b['exclusive'] == 1)
                return true;
        }
        return false;
    }


  // TODO henrik remove this, replace the invocation with the contents instead.
    function getGameTag($game_ref){
        if(empty($this->game_tag))
            $this->game_tag = phive('MicroGames')->getGameTagByRef($game_ref);
        return $this->game_tag;
    }

    /**
     * Gets all entries for a user by way of the so called game_ref which is actually the micro_games.ext_game_name, AKA external GP id.
     *
     * @param string $game_ref The game ref.
     * @param int $user_id The user id.
     * @param string $status Status clause.
     *
     * @return array The entries.
     */
    function getEntriesByRef($game_ref, $user_id, $status = "= 'active'"){
        if(empty($this->entries)){
            $game_tag      = $this->getGameTag($game_ref);
            $str           = "SELECT * FROM bonus_entries WHERE status $status AND bonus_type IN('casino', 'casinowager', 'freespin') AND frb_remaining = 0 AND user_id = $user_id ORDER BY id ASC";
            $this->entries = array_filter(
                phive('SQL')->sh($user_id)->loadArray($str, 'ASSOC', 'id'),
                function($entry) use ($game_tag, $game_ref){
                    $game_tags = explode(',', $entry['game_tags']);
                    // We have only got one value.
                    if(count($game_tags) === 1)
                        $game_tags = $game_tags[0];
                    // There are no game tags or ext_game_names so we return true.
                    if(empty($game_tags))
                        return true;
                    // In case we have just one ext_game_name or tag, if it matches current tag or ext_game_name we return true.
                    if($game_tags == $game_ref || $game_tags == $game_tag)
                        return true;
                    // If we have several ext_game_names or tags we check if the current tag or ext_game_name is in the array, if it is we return true.
                    if(in_array($game_ref, $game_tags) || in_array($game_tag, $game_tags))
                        return true;
                    return false;
                }
            );
        }

        return $this->entries;
    }

    /**
     * Gets an arbitrary column by way of a game ref (micro_games.ext_game_name).
     *
     * @uses Phive::arrCol()
     * @see Phive::arrCol()
     *
     * @param string $col The column to get.
     * @param string $game_ref The game ref.
     * @param int $user_id The user id.
     *
     * @return array The 1D result array.
     */
    function getAnyByRef($col, $game_ref, $user_id){
        $tmp = $this->getEntriesByRef($game_ref, $user_id, "IN('active')");
        if(empty($tmp))
            return array();
        return phive()->arrCol($tmp, $col);
    }

    /**
     * Checks if race progression should happen when the user in question is playing with his bonuses.
     *
     * This just checks if we have a bonus with a balance or a non FRB bonus, if that is the case
     * we return false.
     *
     * @param int $uid The user id.
     *
     * @return bool True if race progression should be possible, false otherwise.
     */
    function canRace($uid){
        foreach($this->getUserEntries($uid) as $e){
            if(!empty($e['balance']))
                return false;
            if($e['bonus_type'] != 'freespin')
                return false;
        }
        return true;
    }

    /**
     * Gets the loyalty multiplier for the user in question.
     *
     * This is relevant only if loyalty / cashback is being in effect, if it is this number will be used to modify
     * the payout for each bet. Ex: if this method returns 0.5 the loyalty will be cut in half.
     *
     * @param string $game_ref The game ref (micro_games.ext_game_name).
     * @param int $user_id The user id.
     *
     * @return float The modifier.
     */
    function getLoyaltyPercentByRef($game_ref, $user_id){
        $res = $this->getAnyByRef('loyalty_percent', $game_ref, $user_id);
        if(empty($res))
            return 1;
        return min($res);
    }

    // TODO henrik remove
    function getAllowRaceByRef($game_ref, $user_id){
        $res = $this->getAnyByRef('allow_race', $game_ref, $user_id);
        if(empty($res))
            return 1;
        return (int)min($res);
    }

    /**
     * Gets the aggregate bonus balance for a user and a game.
     *
     * @param string $game_ref The game ref (micro_games.ext_game_name).
     * @param int $user_id The user id.
     * @param bool $reset_entries Whether or not to reset the entries.
     *
     * @return int The total sum of all bonus balances.
     */
    function getBalanceByRef($game_ref, $user_id, bool $reset_entries = false){
        if ($reset_entries) {
            $this->resetEntries();
        }

        $this->getEntriesByRef($game_ref, $user_id);

        if(!empty($this->entries))
            return phive()->sum2d($this->entries, 'balance');
        else
            return 0;

    }

    // TODO henrik remove
    function getBalance($eid){
        return phive('SQL')->shs('merge', '', null, 'bonus_entries')->getValue("SELECT balance FROM bonus_entries WHERE id = $eid");
    }

    /**
     * Gets the aggregate bonus balance for a user.
     *
     * @param int $user_id The user id.
     *
     * @return int The total sum of all bonus balances.
     */
    function getBalanceByUser($uid){
        if(is_object($uid))
            $uid = $uid->getId();
        // Called in a possibly non-logged in context so we return 0
        if(empty($uid)){
            return 0;
        }
        if(empty($this->total_balance))
            $this->total_balance = phive('SQL')->sh($uid)->getValue("SELECT SUM(balance) FROM bonus_entries WHERE user_id = $uid AND status = 'active' GROUP BY user_id");
        return empty($this->total_balance) ? 0 : $this->total_balance;
    }

    /**
     * Gets the aggregate bonus balance for a user, plus all money that has not been paid out on staggered bonuses.
     *
     * @param int $user_id The user id.
     *
     * @return int The total sum of all bonus balances and unpaid stagger money.
     */
    function getTotalBalanceByUser($uid = ''){
        $uid = empty($uid) ? $_SESSION['mg_id'] : $uid;
        return $this->getBalanceByUser($uid) + $this->getRewards($uid, 'casinowager');
    }

    /**
     * Gets the total paid out amount for a staggered (casinowager type) bonus entry.
     *
     * Staggered bonuses are paid out in certain increments, eg 10%. Therefore we begin
     * by multiplying the total reward amount with that number in order to get the
     * stagger amount.
     *
     * We then figure out the total progress on the reward, perhaps 650 when the reward is 1000.
     *
     * Finally we divide the reward progress with the stagger amount, note the user of floor there so in
     * our example we get 6 here as the 50 in 650 has not been paid out yet.
     *
     * @param array $e The bonus entry.
     *
     * @return int The paid out total for this staggered entry, in our case 6 * 100 = 600.
     */
    function getStaggerPaid($e){
        $stagger_amount 	= $e['reward'] * $e['stagger_percent'];
        $reward_progress 	= $e['reward'] * ($e['progress'] / $e['cost']);
        $nlvls 				= floor($reward_progress / $stagger_amount);
        return $nlvls * $stagger_amount;
    }

    /**
     * Gets the total reward sum for a certain bonus type, will deduct the total staggered amounts alread paid out
     * on casinowager bonuses. This method is in effect returning our total potential bonus liability.
     *
     * @param int $uid The user id.
     * @param string $type The casino type.
     *
     * @return int The total rewards sum.
     */
    function getRewards($uid, $type = 'casinowager'){

        if(empty($uid)){
            return 0;
        }

        $where_user = "AND user_id = $uid";

        if(empty($this->rewards[$type])){

            $sum = 0;
            $type = phive('SQL')->escape($type,false);
            $str = "
                SELECT * FROM bonus_entries
                WHERE status IN('active')
                $where_user
                AND bonus_type = '$type'";

            $entries = phive('SQL')->sh($uid, '', 'bonus_entries')->loadArray($str);

            foreach($entries as $e){
                $sum += empty($e['stagger_percent']) ? $e['reward'] : $e['reward'] - $this->getStaggerPaid($e);
            }

            $this->rewards[$type] = $sum;
        }

        return empty($this->rewards[$type]) ? 0 : $this->rewards[$type];
    }

    /**
     * Closes a bonus entry, this logic is being executed in several contexts such as cron jobs when the bonus
     * times out, when the player actively fails the bonus in order to perhaps activate a better one or when
     * some requirement is being breached such as withdrawing money before the bonus has been completed.
     *
     * @param int $entry_id The bonus entry id.
     * @param string $status The new bonus satus, typically **failed**.
     * @param array $extra Extra values to update.
     *
     * @return bool The result of the UPDATE query.
     */
    function close($entry_id, $status, $extra = array(), $uid){
        return $this->editBonusEntry($entry_id, array_merge(array('balance' => 0, 'status' => $status, 'end_time' => date('Y-m-d')), $extra), $uid);
    }

    /**
     * Wrapper around close() with a hardcoded **approved** status.
     *
     * @uses CasinoBonuses::close()
     * @see CasinoBonuses::close()
     *
     * @param int $entry_id The bonus entry id.
     * @param array $extra Extra values to update.
     *
     * @return bool The result of the UPDATE query.
     */
    function approve($entry_id, $extra = array(), $uid){

		$this->completeWelcomeBonusTrophies($uid, $entry_id);

        return $this->close($entry_id, 'approved', $extra, $uid);
    }

    /**
     * Fails all bonuses for a user, typically used in case of self exclusions, blocks and similar.
     *
     * @param int $user_id The user id.
     * @param string $msg Custom cash_transactions.description.
     *
     * @return null
     */
    function failBonusEntries($user_id, $msg = ''){
        foreach($this->getUserBonuses($user_id, '', " IN('active') ") as $b)
            $this->fail($b['id'], $msg, $user_id);
    }

    /**
     * Gets all the user's bonus entries, joined with the bonus_types table.
     *
     * TODO henrik look into getting rid of this and using getUserBonuses() instead, they are very similar.
     *
     * @param int $user_id The user id.
     * @param string $status Status WHERE clause.
     *
     * @return array The array of bonuses and entries.
     */
    function getExclusive($user_id, $status = ''){
        $status_sql = empty($status) ? "IN('pending', 'active')" : $status;
        $query = "
            SELECT be.*, bt.exclusive, bt.id AS parent_id FROM bonus_entries be
            LEFT JOIN bonus_types AS bt ON be.bonus_id = bt.id
            WHERE be.user_id = $user_id
            AND be.status $status_sql";

		// temp fix until RHEA-3985 is merged, should be removed when we have exclusive 4
        if ($this->getSetting('is_batched_welcome_bonus_enabled')) {
           $query .= " AND (bt.auto_activate_bonus_id IS NULL OR bt.auto_activate_bonus_id = 0)";

		   $bonusAwards = phive('SQL')->shs()->loadArray("SELECT auto_activate_bonus_id FROM bonus_types WHERE auto_activate_bonus_id IS NOT NULL AND auto_activate_bonus_id != 0");
		   if (!empty($bonusAwards)) {
			   $ids = [];
			   foreach ($bonusAwards as $bonusAward) {
				   if (!empty($bonusAward['auto_activate_bonus_id']) && !in_array($bonusAward['auto_activate_bonus_id'], $ids)) {
					   $ids[] = $bonusAward['auto_activate_bonus_id'];
				   }
			   }

			   // excluding welcome weekly fs bonuses
			   if (!empty($ids)) {
				   $query .= " AND be.bonus_id NOT IN (" . implode(',', $ids) . ")";
			   }
		   }
		}

        return phive('SQL')->sh($user_id, '', 'bonus_entries')->loadArray($query);
    }

    function canReactivate($b, $user_id) {
        return phive('Bonuses/ExclusiveHandler')->canActivateBonus($user_id, $b, false);
    }

    /**
     * Checks if the passed in bonus can be activated based on what kind of bonuses the user
     * has active already.
     *
     * Exclusive types are (value of exclusive field):
     * 0 Not exclusive, can not be used with exclusives or reactivated.
     * 1 Exclusive, can't be used with other exclusives, or reactivated.
     * 2 Not exclusive, can not be used with exclusives but can be reactivated, can not be active simultaneously as another 2.
     * 3 Not exclusive, can be used with exclusives but can not be reactivated.
     * 4 Not exclusive, can be used with exclusives and can be reactivated, can not be active simultaneously as another 4.
     *
     * @param array $b The bonus we want to activate / add to the user.
     * @param int $user_id The user id.
     * @param bool $execute Determines what we are to do in case we have a certain type of conflict, do we fail the currently
     * activated bonus and activate the new one (true) or do we leave things as is and return an error message (false)?
     *
     * @return string|bool Return true if we can activate, false if $user_id is invalid, else the error message
     */
    function handleExclusive(array $b, int $user_id, bool $execute = true)
    {
        $user = cu($user_id);
        if (empty($user)) return false;

        if($this->getSetting('exclusive') !== 1) return true;

        /** @var ExclusiveHandler $eh */
        $eh = phive('Bonuses/ExclusiveHandler');

        try {
            $eh->canActivateBonus($user, $b);
        } catch (LogicException $e) {
            return $e->getMessage();
        }

        if ($execute) $eh->failOthersToActivate($b, $user, "Failed %d to activate {$b['id']} with exclusive {$b['exclusive']}");

        return true;
    }

    /**
     * Checks if a bonus can be failed, ie if it is not failed already.
     *
     * @param array $e The bonus entry.
     *
     * @return bool True if yes, false if no.
     */
    function failable($e){
        if($e['status'] == 'active' || $e['status'] == 'pending')
            return true;
        return false;
    }

    /**
     * Checks if a bonus can be failed by the currently logged in user / admin.
     *
     * @param array $e The bonus entry.
     *
     * @return bool True if yes, false if no.
     */
    function canFail($e){
        if(!$this->failable($e))
            return false;
        if(p('bonus.fail.all'))
            return true;
        if($e['bonus_tag'] == 'qspin')
            return false;
        if($e['bonus_tag'] == 'relax')
            return false;
        return true;
    }

    /**
     * Summary
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_failed_bonuses The wiki docs for the failed bonuses table.
     *
     * @param int $user_id The user id.
     * @param string $sstamp The start stamp.
     * @param int $win_deduction The win deduction.
     *
     * @return array The failed bonus.
     */
    function getFailedBonus($user_id, $sstamp, $win_deduction = 0){
        $str = "SELECT * FROM failed_bonuses WHERE user_id = $user_id AND created_at > '$sstamp' AND win_deduction = $win_deduction";
        return phive('SQL')->sh($user_id, '', 'failed_bonuses')->loadAssoc($str);
    }

    /**
     * Fails an active or pending bonus.
     *
     * Depending on which type of bonus we:
     * - freespin / FRB: we create a forked process and call cancelFRBonus() there in order to fail the bonus in the GP's system too.
     * - casino / cash balance bonus: we debit the winnings (if any) made with the bonus.
     * - casinowager / staggered bonus: we figure out how much money was left on the table (not paid out) and log that amount.
     *
     * Finally we log the forfeited bonus money / balance as a cash transaction and the fail event in general in actions.
     *
     * @uses CasinoBonuses::close()
     * @uses CasinoBonuses::getDebBonusProfit() In order to determine how much money to debit / deduct from the user.
     * @uses CasinoBonuses::getStaggerPaid() In order to figure out how much money was not paid out.
     * @uses Phive::pexec() In order to call cancelFRBonus() in a forked process, we don't want that external
     * call to block the main process.
     * @see CasinoBonuses::close()
     * @see CasinoBonuses::getDebBonusProfit()
     * @see CasinoBonuses::getStaggerPaid()
     * @see Phive::pexec()
     *
     * @param int $entry_id The bonus entry id.
     * @param string $descr_error Description to use in cash_transactions and actions.
     * @param int $uid User id.
     *
     * @return bool The result of the close call.
     */
    function fail($entry_id, $descr_error = 'Deposit bonus fail', $uid = '', $extra_entry_updates = [])
    {
        if(is_array($entry_id)){
            $e 	= $entry_id;
            $entry_id = $entry_id['id'];
        }else
            $e = $this->getBonusEntry($entry_id, $uid);

        $uid = empty($uid) ? $e['user_id'] : $uid;

        //database connection error so quitting
        if ($this->close($entry_id, 'failed', $extra_entry_updates, $uid) === false) return false;

        //after closing the bonus entry, it's possible that the game_session_id is repeated, we retrieve it and update it to NULL
        if($e['bonus_type'] === 'freespin'){
            $query = "SELECT id FROM bonus_entries WHERE id = {$entry_id}";
            $res = phive('SQL')->sh($uid, '', 'bonus_entries')->loadAssoc($query);
            if($res){
                phive('SQL')->sh($uid, '', 'bonus_entries')->updateWithNullById('bonus_entries', 'game_session_id', $entry_id);
            }
        }

        if(!isCli()){
            if(!empty($_SESSION["failing_$entry_id"]))
                return false;
            $_SESSION["failing_$entry_id"] = true;
            $this->failed = true;
        }

        $bonusStatus = ['failed', 'pending'];

        // @todo: Can this be extended to all brands?
        if (phive('BrandedConfig')->getBrand()  === BrandedConfig::BRAND_MEGARICHES) $bonusStatus = ['failed'];

        if (
            !in_array($e['status'], $bonusStatus) &&
            in_array($e['bonus_type'], array('casino', 'casinowager', 'freespin'))
        ) {
            //description to use in History Message reporting
            $descr_reporting = "Failed ".$e['bonus_type'];

            $casino_acc = 'cash_balance';
            $db_profit  = 0;
            $user 		= cu($e['user_id']); // have to make sure we don't do double

            if (phive()->moduleExists('Trophy')) {
                phive()->pexec('Bonuses', 'wsOnProgress', array($e['user_id'], $e['id']));
            }

            if ($e['bonus_type'] == 'freespin') {
                $gp_module = phive('Casino')->getNetworkName($e['bonus_tag']);
                switch ($gp_module) {
                    case 'Gpr':
                        // Relax/Microgaming/Quickspin cannot cancel used Freespin
                        if (in_array($e['bonus_tag'], ['relax', 'microgaming', 'qspin'])) break;

                        // We need the entry id and the user id so we can re-fetch the entry in the Gpr method.
                        phive()->pexec($gp_module, 'cancelFRBonus', [$e['user_id'], $entry_id]);
                        break;
                    case 'Netent':
                    case 'Pushgaming':
                        // These GPs do not create their own unique IDs for the FRBs, or does not send them,
                        // they only send our bonus entry id.
                        phive()->pexec($gp_module, 'cancelFRBonus', array($entry_id));
                        break;
                    default:
                        $ext_id = $e['ext_id'];
                        phive()->pexec($gp_module, 'cancelFRBonus', array($e['user_id'], $ext_id));
                        break;
                }
            } else {
                if ($e['status'] == 'active') {
                    $db_profit = (int)phive('Cashier')->getDepBonusProfit($e['user_id'], $entry_id);
                }

                $stagger_amount = empty($e['stagger_percent']) ? 0 : $this->getStaggerPaid($e);
                $db_profit = min($user->getAttribute($casino_acc), $db_profit + $stagger_amount);
                $bonus = $this->getBonus($e['bonus_id']);

                if ($db_profit > 0 && empty($bonus['keep_winnings'])) {
                    phive('QuickFire')->bonusChgBalance($user, -$db_profit, "Bonus profit fail deduction", 53, $e['bonus_id'], $entry_id);
                }

                /**
                 * @todo: This block should use $this->getTransactionType($e['bonus_type'], 'debit');
                 * instead of the constant WAGER_BONUS_DEBIT for consistency
                 */
                if ($e['bonus_type'] == 'casinowager' && $this->userActivatedBonus($e)) {
                    $amount_left = $e['reward'] - $stagger_amount;
                    $transaction_id = phive('Cashier')
                        ->insertTransaction($e['user_id'], -$amount_left, static::WAGER_BONUS_DEBIT, $descr_error, $e['bonus_id'], '', $entry_id);

                  try{
                      /** @uses Licensed::addRecordToHistory() */
                      lic('addRecordToHistory', [
                          'bonus_cancellation',
                          new BonusCancellationHistoryMessage(
                              [
                                  'user_id'          => (int) $e['user_id'],
                                  'transaction_id'   => (int) $transaction_id,
                                  'amount'           => -(int) $amount_left,
                                  'currency'         => $user->getCurrency(),
                                  'transaction_type' => static::WAGER_BONUS_DEBIT,
                                  'parent_id'        => 0,
                                  'description'      => $descr_reporting,
                                  'event_timestamp'  => time()
                              ]
                          )
                      ],
                          $user
                      );
                  } catch(InvalidMessageDataException $e) {
                      phive('Logger')->getLogger('history_message')->error(
                          $e->getMessage(),
                          [
                              'topic'             => 'fail bonus',
                              'validation_errors' => $e->getErrors(),
                              'trace'             => $e->getTrace(),
                              'data'              => $win_history_data
                          ]
                      );
                  }
               }

               $this->handleBonusForfeit($e);
           }

            if ($e['bonus_type'] == 'freespin' || (!empty($e['balance']) && $this->userActivatedBonus($e))) {
                $transaction_type = $this->getTransactionType($e['bonus_type'], 'debit');
                /**
                 * @todo: Why does this not use Cashier::insertTransaction() ?
                 */
                $tr_id = phive('SQL')->sh($e, 'user_id', 'cash_transactions')->insertArray('cash_transactions', [
                        'user_id'         => $e['user_id'],
                        'amount'          => -$e['balance'],
                        'balance'         => $user->getAttr('cash_balance'),
                        'currency'        => $user->getAttr('currency'),
                        'description'     => $descr_error,
                        'bonus_id'        => $e['bonus_id'],
                        'entry_id'        => $entry_id,
                        'transactiontype' => $transaction_type,
                        'session_id'      => $user->getCurrentSession()['id'] ?? 0,
                    ]
                );

                if (in_array($e['bonus_type'], ['casino', 'casinowager', 'freespin'])) {
                    try{
                        /** @uses Licensed::addRecordToHistory() */
                        lic('addRecordToHistory', [
                            'bonus_cancellation',
                            new BonusCancellationHistoryMessage([
                                'user_id'          => (int) $e['user_id'],
                                'transaction_id'   => (int) $tr_id,
                                'amount'           => -(int) $e['balance'],
                                'currency'         => $user->getCurrency(),
                                'transaction_type' => (int) $transaction_type,
                                'parent_id'        => 0,
                                'description'      => $descr_error ?? 'Bonus failure',
                                'event_timestamp'  => time(),
                            ])
                        ],
                            $user
                        );
                    } catch(InvalidMessageDataException $e) {
                        phive('Logger')->getLogger('history_message')->error(
                            $e->getMessage(),
                            [
                                'topic'             => 'fail bonus',
                                'validation_errors' => $e->getErrors(),
                                'trace'             => $e->getTrace(),
                                'data'              => $win_history_data
                          ]
                        );
                    }
                }
            }

			phive('UserHandler')->logAction($user, $descr_error, 'bonus_fail');
        }

        $_SESSION["failing_$entry_id"] = false;

        return true;
    }

    /**
     * Pays out a bonus when the requirements have been reached, eg 40 times turnover.
     *
     * In effect there are only two scenarios that actually entailing crediting the user's real balance:
     * 1. A casino / cash balance bonus where we move the bonus balance to the real money cash balance.
     * 2. An FRB bonus with turnover requirements, ie the FRB winnings end up as a balance that needs to be turned over,
     * that scenario basically defaults to #1.
     *
     * @param array $entry The bonus entry.
     *
     * @return null
     */
    function rewardBonus($entry){
        //Abort if we have a casino wager.
        if(!empty($entry['stagger_percent']) || $entry['bonus_type'] == 'casinowager')
            return;

        $trans_type = 4;

        $updates = array(
            "status" 	      => $entry['status'] == 'active' ? "approved" : $entry['status'],
            "progress"      => $entry['cost'],
            "cash_progress" => $entry['cash_cost'],
            "end_time"      => date("Y-m-d"));

        phive("SQL")->sh($entry, 'user_id', 'bonus_entries')->updateArray('bonus_entries', $updates, "id = ".$entry['id']);

        $parent     = $this->getBonus($entry['bonus_id']);
        $micro 	    = phive('QuickFire');
        $user 	    = cu($entry['user_id']);
        $amount     = $entry['balance'];
        $max_payout = mc($parent['max_payout'], $user);

        if($max_payout > 0 && $max_payout < $entry['balance'] && in_array($entry['bonus_type'], array('casino', 'freespin'))){
            $amount 		= $max_payout;
            $failed_amount 	= $entry['balance'] - $max_payout;
            //72 or 67
            $debit_trans_type = $this->getTransactionType($entry['bonus_type'], 'debit');
            $debit_trans_desc = "Bonus balance bigger than max payout.";
            $debit_trans_id = phive('Cashier')->insertTransaction($user->getId(), -(int)$failed_amount, $debit_trans_type, $debit_trans_desc, $entry['bonus_id'], '', $entry['id']);

            if ($debit_trans_id) {
                lic(
                    'addRecordToHistory',
                    [
                        'bonus_cancellation',
                        new BonusCancellationHistoryMessage(
                            [
                                'user_id' => (int)$entry['user_id'],
                                'transaction_id' => (int)$debit_trans_id,
                                'amount' => -(int)$failed_amount,
                                'currency' => $user->getCurrency(),
                                'transaction_type' => (int)$debit_trans_type,
                                'parent_id' => 0,
                                'description' => $debit_trans_desc,
                                'event_timestamp' => time(),
                            ]
                        )
                    ],
                    $user
                );
            }
        }

        if(!empty($amount)){
            $result = $micro->changeBalance($user, $amount, 'Bonus Completed: '.$parent['bonus_name'], $trans_type, '', $entry['bonus_id'], $entry['id']);
            uEvent('bonuspayout', $amount, $parent['bonus_name'], '', $user->data);
            lic('dispatchReportTransactionJob', ['deposit', 'bonus', $amount, $user->getId()], $user);
        }

        if(phive()->moduleExists('Trophy')){
            phive()->pexec('Bonuses', 'wsOnProgress', array($user->getId(), $entry['id']));
        }

        $this->approve($entry['id'], [], $user->getId());
    }

    /**
     * Check if gameTag is available in bonusEntryTags or
     * Check if gameExt is available in bonusEntryTags
     * Check if gameSubTag is available in bonusEntryTags
     * Send true if available in any case.
     * @param array $entry
     * @param array $game
     * @return bool
     */
    public function isGameTagAvailable(array $entry = [], array $game = []): bool
    {
        // Early return if either $entry or $game are empty
        if (empty($entry) || empty($game)) {
            return false;
        }

        $game_tag = $game['tag'] ?? '';
        $game_sub_tag = $game['sub_tag'] ?? '';
        $bonus_entry_tags = $entry['game_tags'] ?? '';

        // If all tags are empty, return false
        if (empty($game_tag) && empty($game_sub_tag) && empty($bonus_entry_tags)) {
            return false;
        }

        $bonus_entry_tags_plus = $bonus_entry_tags . ',casino-playtech'; // Append tag
        $bonus_entry_tags_plus = explode(',', $bonus_entry_tags_plus);

        if (!(in_array($game_tag, $bonus_entry_tags_plus) ||
            in_array($game_sub_tag, $bonus_entry_tags_plus))) {
            return false;
        }

        return true;
    }

    /**
     * Check if game_tag has no zero value in game_percents
     * Check if game_sub_tag has no zero value in game_percents
     * If yes return true otherwise false
     *
     * @param array $entry
     * @param array $game
     * @return bool
     */
    public function checkGameTagAndGamePercentage(array $entry = [], array $game = []): bool
    {
        // Early return if either $entry or $game are empty
        if (empty($entry) || empty($game)) {
            return false;
        }

        // Get game tag and sub tag, defaulting to an empty string if not present
        $game_tag = $game['tag'] ?? '';
        $game_sub_tag = $game['sub_tag'] ?? '';

        // If both tags are empty, return false
        if (empty($game_tag) && empty($game_sub_tag)) {
            return false;
        }

        if (!$this->isGameTagAvailable($entry, $game)) {
            return false;
        }

        // Get game tags and percentages, splitting them by commas
        $bonus_tag_array = explode(',', $entry['game_tags'] ?? '');
        $bonus_percentage = explode(',', $entry['game_percents'] ?? '');

        // Search for game_tag or game_sub_tag in the tag array
        foreach ([$game_tag, $game_sub_tag] as $tag) {
            if (!empty($tag)) {
                $index = array_search($tag, $bonus_tag_array);
                // Check if a valid index was found and corresponding percentage exists
                if ($index !== false && isset($bonus_percentage[$index]) && $bonus_percentage[$index]) {
                    return true;
                }
            }
        }

        // If no valid tag/percentage was found, return false
        return false;
    }

    /**
     * Checks if the game in question can be played with the bonus in question.
     *
     * @param array $entry The bonus entry.
     * @param array $game The game.
     *
     * @return bool True if yes, the game can be played, false otherwise.
     */
    function correctGame($entry, $game){

        if (is_null($game)) {
            return false;
        }

        if ($this->isPersistentBannedGame($game['game_name'])) {
            return false;
        }

        if(empty($entry['game_tags']))
            return true;
        if($entry['game_tags'] == $game['ext_game_name'])
            return true;
        return $this->checkGameTagAndGamePercentage($entry, $game);
    }

    /**
     * Determines if the game is "persistent" (prohibited for bonus bets).
     *
     * @param string|null $gameRef Game name/identifier (ext_game_name, etc.)
     * @return bool
     */
    public function isPersistentBannedGame(?string $gameRef): bool
    {
        if (empty($gameRef)) {
            return false;
        }

        $config = phive("Config")->getByTags('games');
        if (empty($config) || !is_array($config) || !isset($config['games']['banned-bonus-games'])) {
            return false;
        }

        $bannedGamesConfig = $config['games']['banned-bonus-games'];
        if (empty($bannedGamesConfig) || !is_array($bannedGamesConfig)) {
            return false;
        }

        $gamesString = $bannedGamesConfig['config_value'] ?? '';
        $gamesDelimiter = "\n";
        if (!empty($bannedGamesConfig['config_type'])) {
            $configType = json_decode($bannedGamesConfig['config_type'], true);
            if (is_array($configType) && isset($configType['next_data_delimiter'])) {
                $gamesDelimiter = $configType['next_data_delimiter'];
            }
        }

        $normalize = function($name) {
            $name = mb_strtolower(trim($name));
            $name = preg_replace('/\s+/', ' ', $name);
            return preg_replace('/[^\p{L}\p{N} ]+/u', '', $name);
        };

        $bannedGames = array_filter(array_map($normalize, explode($gamesDelimiter, $gamesString)));
        $normalizedGameRef = $normalize($gameRef);

        return in_array($normalizedGameRef, $bannedGames, true);
    }

    /**
     * Inverses correctGame() but checks all active bonuses against the game.
     *
     * @uses CasinoBonuses::correctGame()
     * @see CasinoBonuses::correctGame()
     *
     * @param int $uid The user id.
     * @param array $game The game.
     *
     * @return array An array with true in the first spot and the failing bonus entry in the second spot if
     * it isn't working. [false []] otherwise if we're all good to go.
     */
    function isWrongGame($uid, $game){
        $entries = $this->getActiveEntriesByType("'casino', 'casinowager', 'freespin'", $uid);
        foreach($entries as $entry){
            if(!$this->correctGame($entry, $game))
                return [true, $entry];
        }
        return [false, []];
    }

    /**
     * The main logic being executed when a player is playing the wrong game.
     *
     * @uses CasinoBonuses::fail() To fail the offending bonuses.
     * @see CasinoBonuses::fail()
     *
     * @param array $udata User data.
     * @param string $game_ref The game reference (micro_games.ext_game_name).
     *
     * @return bool True if the game was not allowed, false otherwise.
     */
    function failByWrongGame($udata, $game_ref){
        $this->failed = false;
        $game 	  = is_array($game_ref) ? $game_ref : phive('MicroGames')->getByGameRef($game_ref);
        if(strpos($game['ext_game_name'], '_system') !== false)
            return false;
        $entries 	  = $this->getActiveBonusEntries($udata['id']);
        $this->entries = array();
        foreach($entries as $entry){
            $eid = $entry['id'];

            if(!empty($entry['game_tags'])){
                if(!$this->correctGame($entry, $game)){
                    $this->fail($eid, 'Trying to play the wrong wrong game: '.$game['game_name'], $entry['user_id']);
                    continue;
                }
            }
        }

        return $this->failed;
    }

    /**
     * Checks if a bet is illegal given a few checks, note that we do not take any action here we just determine
     * if the bet was legal or not.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_bonus_types The wiki docs for the bonus_types (AKA bonus table) table.
     *
     * @param array $udata User data.
     * @param string $game_ref Ext game name.
     * @param int $amount Bet amount.
     *
     * @return True if the bet failed, false otherwise.
     */
    function failByRequirements($udata, $game_ref, $amount = 0){
        $entries 	   = $this->getActiveBonusEntries($udata['id']);
        $game 	   = is_array($game_ref) ? $game_ref : phive('MicroGames')->getByGameRef($game_ref);
        $this->entries = array();

        foreach($entries as $entry){
            $eid = $entry['id'];

            if(!empty($entry['deposit_max_bet_amount']) && $amount > $entry['deposit_max_bet_amount']){
                return true;
            }

            if(!empty($entry['bonus_max_bet_amount']) && $amount > $entry['bonus_max_bet_amount']){
                return true;
            }

            if(!empty($entry['max_bet_amount']) && $amount > mc($entry['max_bet_amount'], $udata['currency'])){
                return true;
            }

            if($entry['bonus_type'] == 'casino')
                $this->entries[$eid] = $entry;
        }

        return $this->failed;
    }

    /**
     * Fails all casino / cash balance bonuses in case the user hasn't been active in 2 days, this is a cron job.
     *
     * @return null
     */
    function failByFailLimit(){
        $entries = phive("SQL")->shs('merge', '', null, 'bonus_entries')->loadArray(
            "SELECT be.*, bt.fail_limit FROM bonus_entries be, bonus_types bt
           WHERE be.status = 'active'
               AND be.bonus_id = bt.id
               AND be.bonus_type = 'casino'
               AND be.balance < bt.fail_limit");

        foreach($entries as $e){
            $llogin = phive("SQL")->sh($e, 'user_id', 'users')->getValue("SELECT DATE(last_login) FROM users WHERE id = {$e['user_id']}");
            if($llogin != phive()->today() && $llogin != phive()->yesterday()){
                $this->fail($e['id'], "Below the fail limit of: {$e['fail_limit']}", $e['user_id']);
            }
        }
    }

    /**
     * Handles staggered bonus progression on bet.
     *
     * The logic will determine all the thresholds that have to be achieved in order to get each staggered payout
     * and then loops them to determine how many thresholds have been "breached" and should therefore be paid out,
     * typically only one but the logic supports an arbitrary amount just in case.
     *
     * @param array $entry The bonus entry.
     * @param int $amount The bet amount.
     *
     * @return null|string "rewarded" in case we got any payouts, null otherwise.
     */
    function handleStaggered($entry, $amount){

        //$amount needs to have been run through getBonusProgress for this to work.
        $tot_prog = $entry['progress'] + $amount;

        if(empty($amount) || empty($entry['stagger_percent']))
            return;

        $reward_amount = round($entry['stagger_percent'] * $entry['reward']);

        if(empty($reward_amount))
            return;

        $user = cu($entry['user_id']);

        if(!is_object($user))
            return;

        $tholds = array_filter(range(0, $entry['cost'], $entry['stagger_percent'] * $entry['cost']), function($lvl) use ($entry, $amount, $tot_prog){
            return $lvl > $entry['progress'] && $lvl <= ceil($tot_prog);
        });

        $result = false;
        foreach($tholds as $thold){
            $result = phive("Casino")->changeBalance($user, $reward_amount, "#partial.bonus.payout", 69, '', $entry['bonus_id'], $entry['id']);
            uEvent('bonuspayout', $reward_amount, '', '', $user->data);
            lic('dispatchReportTransactionJob', ['deposit', 'bonus', $reward_amount, $user->getId()], $user);
        }

        if($entry['cost'] <= ceil($tot_prog)){
            $this->approve($entry['id'], array('progress' => $entry['cost'], 'cash_progress' => $entry['cash_cost']), $entry['user_id']);
            phive()->pexec('Bonuses', 'wsOnProgress', array($user->getId(), $entry['id']));
            return 'rewarded';
        }
    }

    /**
     * When a freespin with turnover requirements starts to turn over, we log the balance.
     *
     * @param array $e The bonus entry.
     * @param int $amount Optional amount.
     * @param string $description Optional description.
     *
     * @return null
     */
    function handleFspinShift($e, $amount = null, $description = null){
        phive('Cashier')->insertTransaction($e['user_id'], $amount ?? $e['balance'], 71, $description ?? "FRB with entry id {$e['id']} starting to turnover", $e['bonus_id'], '', $e['id']);
    }

    /**
     * Logic that happens on every bet with an FRB active.
     *
     * TODO: new logic in Gpr replaces this logic so when all GPs go via Gpr this can be removed. /Henrik
     *
     * @param int $entry_id The bonus entry id.
     * @param int $amount The bet amount.
     * @param string $game_ref The external game name.
     * @param int $uid The user id.
     *
     * @return bool|string False if nothing happened, true if progress happened and **rewarded** if the FRB was rewarded as a result of the bet.
     */
    function handleFreespin($entry_id, $amount, $game_ref, $uid = ''){
        $entry = $this->getBonusEntry($entry_id, $uid);
        if($entry['bonus_type'] != 'freespin')
            return false;

        //FRB spins have not been played out yet or bonus didn't result in wins, status should already be failed here, if not player has to manually fail
        if($entry['cost'] == 0 && $entry['balance'] == 0)
            return false;

        $rleft = 1;
        //If the cost is empty it could be because the FRBs have not been played out yet. In that case we need to figure out how many are left.
        //If there are 0 left it means they were played out with 0 winnings, in that case we simply reward the bonus with zero money being credited.
        //FRB logic
        if(!empty($entry['cost']) && $entry['cost'] <= $entry['progress'] + $amount){
            switch($entry['bonus_tag']){
            case 'bsg':
                // TODO we need to refactor this and use the remaining logic instead and remove the fork for this function
                $rleft = phive()->apply($entry['bonus_tag'], 'getFRBonusSpinsLeft', array($entry['user_id'], $entry['ext_id']));
                break;
            default:
                //TODO this setting needs to be looked at as it doesn't make much sense?
                if(in_array($entry['bonus_tag'], $this->getSetting('deposit_spin_gps'))){
                    $rleft = 0;
                } else {
                    $rleft = $entry['frb_remaining'];
                }
            }
        }

        if((int)$rleft === 0){
            $this->rewardBonus($entry);
            return 'rewarded';
        }

        $cur_game = phive('MicroGames')->getByGameRef($game_ref);

        if(!empty($amount)){
            //This is where progress is happening for FRBs with turnover requirements.
            //FRB logic
            $entry['progress'] += $this->getBonusProgress($cur_game, $entry, $amount);
            phive("SQL")->sh($entry, 'user_id', 'bonus_entries')->save('bonus_entries', $entry);
            if(phive()->moduleExists('Trophy'))
                phive()->pexec('Bonuses', 'wsOnProgress', array($entry['user_id']));
        }

        return true;
    }

    /**
     * We're caching the active entries in many context, this will clear that cache.
     *
     * @return CasinoBonuses To enable fluency / chaining.
     */
    function resetEntries(){
        unset($this->entries);
        //$this->has_queried_entries = false;
        return $this;
    }

    /**
     * We display realtime bonus progress with a progress bar, this takes care of
     * updating the FE GUI via websockets.
     *
     * @param int $uid The user id.
     * @param string $finished Whether or not the bonus is complete / finished, will be interpreted as an actual boolean
     * by the FE JavaScript.
     *
     * @return null
     */
    function wsOnProgress($uid, $bonus_entry_id = false)
	{
		$user = cu($uid);
		$details = phive('Bonuses')->getActiveBonusesForProgress($user, $bonus_entry_id);
		if (empty($user)) {
			return;
		}

        toWs($details, 'rewardprogress', $uid);
    }

  /**
   * Progress bonus.
   *
   * @param array $udata User data.
   * @param $entry bonus entry to progress
   * @param int $amount The bet amount.
   * @param string $game_ref External game name or 'sportsbook' to handle sports bonus
   * @param bool $bonus_bet True if bet was made with bonus money, false otherwise. Note that
   * we debit the normal balance first, only when the normal / real balance is down to zero
   * do we start debiting the bonus balance.
   */
  public function progressBonus($udata, $entry, $amount, $game_ref, $bonus_bet) {

    if($entry['status'] == 'active'){

      if($entry['progress_type'] == 'bonus' && $bonus_bet === 0){
        return;
      } else if($entry['progress_type'] == 'cash' && $bonus_bet === 1){
        return;
      }

      if($game_ref == 'sportsbook') {
        $progress_amount = phive('SportsbookBonuses')->getBonusProgress($entry, $amount);
      } else {
        $cur_game = phive('MicroGames')->getByGameRef($game_ref);
        $progress_amount = $this->getBonusProgress($cur_game, $entry, $amount);
      }

      if($this->handleStaggered($entry, $progress_amount) == 'rewarded'){
        $this->reset_entries = true;
        return;
      }

      if($entry['bonus_type'] == 'freespin'){
        // This is only necessary because we need to query BSG on every spin for the amount of spins left.
        // Should be refactored so we don't have to do that if possible.
        phive()->pexec('Bonuses', 'handleFreespin', array($entry['id'], $amount, $game_ref, $entry['user_id']));
        return;
      }

      $tot_prog     = $entry['progress'] + $progress_amount;
      $cash_prog    = $entry['cash_progress'] + $progress_amount;
      $set          = "progress = ".($tot_prog >= $entry['cost'] ? $entry['cost'] : $tot_prog);
      $progress     = false;

      // Real cash bet?
      if($bonus_bet !== 1){
        if($entry['cash_cost'] <= $cash_prog && $entry['cost'] <= $tot_prog){
          $this->rewardBonus($entry);
          $this->reset_entries = true;
          return;
        }else{
          $set .= ", cash_progress = ".($cash_prog >= $entry['cash_cost'] ? $entry['cash_cost'] : $cash_prog);
          $progress = true;
        }
      }else{
        if($entry['cash_cost'] <= $entry['cash_progress'] && $entry['cost'] <= $tot_prog){
          $this->rewardBonus($entry);
          $this->reset_entries = true;
          return;
        }else{
          $progress = true;
        }
      }


      if($progress){
        $sql = "UPDATE bonus_entries SET $set WHERE id = {$entry['id']}";
        phive('SQL')->sh($udata, 'id', 'bonus_entries')->query($sql);
      }
    }

    if($progress && phive()->moduleExists('Trophy'))
      phive()->pexec('Bonuses', 'wsOnProgress', array($udata['id']));
  }


  /**
     * Main entrypoint for bonus progression during gameplay.
     *
     * @param array $udata User data.
     * @param string $game_ref External game name.
     * @param int $amount The bet amount.
     * @param int $balance TODO henrik remove this, don't forget to refactor all calls.
     * @param bool $bonus_bet True if bet was made with bonus money, false otherwise. Note that
     * we debit the normal balance first, only when the normal / real balance is down to zero
     * do we start debiting the bonus balance.
     *
     * @return bool Whether or not the bonus failed as a result of the bet.
     */
    function progressBonuses($udata, $game_ref, $amount, $balance, $bonus_bet){
        $this->getEntriesByRef($game_ref, $udata['id'], "= 'active'");
        // We divide the amount by the amount of entries so we don't increase the aggregate total progress too much.
        $amount /= count($this->entries);
        foreach($this->entries as $entry){
          $this->progressBonus($udata, $entry, $amount, $game_ref, $bonus_bet);
        }

        if($this->reset_entries == true){
          unset($this->entries);
        }

        return $this->failed;
    }

    /**
     * Returns all bonuses with a proper cash balance, ie only bonus types casino and freespin.
     *
     * @param int $bonus_bet Whether or not the bet was made with bonus money.
     *
     * @return array The bonus entries.
     */
    function onlyBonusBalanceEntries($bonus_bet = 0, $user_id = null, $fresh = false){
        $entries = $this->entries;

        if ($fresh) {
            $entries = [];
        }

        if(empty($entries) && !empty($user_id)){
            $entries = $this->getUserEntries($user_id);
        }

        $rarr = [];
        foreach($entries as $e){
            //BALANCE ISSUE
            // If frb_remaining is not zero it means that handleFspinWin has not been called or handleFspinBet has not decremented frb_remaining to zero yet, so we do not consider the bonus in question.
            // It is considered to still be in the FRB state.
            if(!empty($e['frb_remaining']) && $e['bonus_type'] == 'freespin')
                continue;
            if(in_array($e['bonus_type'], array('casino', 'freespin'))){
                if($bonus_bet === 0 && $e['progress_type'] != 'bonus')
                    $rarr[] = $e;
                else if($bonus_bet === 1 && in_array($e['progress_type'], array('bonus', 'both'))){
                    $rarr[] = $e;
                }else if(empty($bonus_bet))
                    $rarr[] = $e;
            }
        }

        return $rarr;
    }

    /**
     * Used when sending out bonus mails, only people who received the mail should be able to use the reload code(s).
     *
     * TODO henrik remove the pass by reference as objects are passed by reference anyway.
     *
     * @param DBUser $user The user object.
     * @param int $bid The bonus id.
     *
     * @return int The new user_flags id.
     */
    function insertCheck(&$user, $bid){
        return $user->setFlag('bonus-'.$bid);
    }

    /**
     * Used when people try to activate bonuses, only people who received the mail should be able to use the reload code(s).
     *
     * @param DBUser $user The user object.
     * @param int $bid The bonus id.
     *
     * @return bool True if the user should be able to activate the bonus in question, false otherwise.
     */
    function checkBonusEmail(&$user, $bid){
        $code_exists = phive('SQL')->sh($user, 'id', 'user_flags')->loadAssoc('', 'user_flags', ['flag' => 'bonus-'.$bid], true);
        //If there is no code at all the player can activate (ie the code is not related to an email at all)
        if(empty($code_exists))
            return true;

        $flag_exists = $user->hasFlag('bonus-'.$bid);

        //if we don't have a row this player isn't eligible
        if(!$flag_exists){
            phive('UserHandler')->logAction($user->getId(), "Could not get bonus with id $bid, did not get the promo email", 'bonus');
            return false;
        }

        return true;
    }

    /**
     * Since we support simultaneous play with multiple bonuses at the same time we need to dynamically update the bonus
     * balances when a bet / win happens in a way that debits / credits them equally, currently we do that by simply
     * splitting the amount up between them, there is no weighting going on.
     *
     * @param int $amount
     * @param array $bonus_entry
     * @param int $user_id The user id.
     * @return bool True if the DB queries were successful, false otherwise.
     */
    function increaseBonusBalance(int $amount, array $bonus_entry, int $user_id) : bool {
        return phive('SQL')->incrValue(
            'bonus_entries',
            'balance',
            ['id' => $bonus_entry['id']],
            $amount,
            [],
            $user_id
        );
    }

    /**
     * Some bonuses have a split real cash turnover requirement, this method adds that requirement to the entry to be inserted.
     *
     * Ex: pretend we have a 40 times total wagering, then the real cash requirement could be 20 times, in that case
     * cash_percentage would be 0.5 and the user would need to deposit more in case the real cash balance hits zero as
     * the bonus will not progresss until the real cash turnover has been completed.
     *
     * Only after the 20 times real cash turnover has been completed can the user turnover the 20 remaining times in any
     * way he feels like, whether that is with real cash or bonus cash.
     *
     * @param array &$inserts The bonus entry data to be inserted into the bonus_entries table.
     * @param array $bonus The bonus (bonus_types row).
     *
     * @return null
     */
    function addCashCost(&$inserts, $bonus){
        $inserts['cash_cost'] 	= empty($bonus['cash_percentage']) ? 0 : $inserts['cost'] * ($bonus['cash_percentage'] / 100);
    }

    /**
     * Adds various bet limits to the bonus entry to be inserted, breaking these limits won't fail the bonus, it will just
     * invalidate the bet with regards to bonus progression etc.
     *
     * @param array &$inserts The bonus entry data to be inserted into the bonus_entries table.
     * @param array $bonus The bonus (bonus_types row).
     * @param int $amount The deposit amount in case we're looking at a deposit bonus.
     *
     * @return null
     */
    function addBetLimits(&$inserts, $bonus, $amount = ''){
        if(!empty($bonus['bonus_max_bet_percent']))
            $inserts['bonus_max_bet_amount'] = round($inserts['reward'] * $bonus['bonus_max_bet_percent']);

        if(!empty($bonus['deposit_max_bet_percent']) && !empty($amount))
            $inserts['deposit_max_bet_amount'] = round($amount * $bonus['deposit_max_bet_percent']);

        if(!empty($bonus['max_bet_amount']))
            $inserts['max_bet_amount'] = $bonus['max_bet_amount'];
    }

    /**
     * Gets the turnover requirement for a bonus for a user, the turnover is different for different countries.
     *
     * @param DBUser $user The user object.
     * @param array $bonus The bonus.
     *
     * @return int The turnover in percent, ie 4000(%) = 40 times turnover.
     */
    function getTurnover($user, $bonus) {
        $user = cu($user);
        $turnover = 0;
        if(is_numeric($bonus['rake_percent'])){
            $turnover = $bonus['rake_percent'];
        } else {
            $country            = $user->data['country'];
            //ex: default:2000|DE:4000
            $rake_percent_array = phive()->fromDualStr($bonus['rake_percent']);
            $turnover = empty($rake_percent_array[$country]) ? $rake_percent_array['default'] : $rake_percent_array[$country];
        }

        if($bonus['bonus_type'] == 'freespin'){
            $minimum_fs_wager = (int)licSetting('minimum_fs_wager', $user) * 100;
            if(!empty($minimum_fs_wager)){
                $turnover = $minimum_fs_wager > $bonus['rake_percent'] ? $minimum_fs_wager : $bonus['rake_percent'];
            }
        }

        return $turnover;
    }

    /**
     * Gives an award in case the bonus has an award connected.
     *
     * If we have the trophy module enabled and if the bonus contains a non-zero award id we go ahead and give the award.
     *
     * @uses Trophy::giveAward()
     * @uses Trophy::giveAward()
     *
     * @param int|bool $eid is the entry id of the newly added entry, it's false if the insert didn't work
     * @param array $ud User data.
     * @param array $bonus The bonus.
     *
     * @return bool True if the award was handed out successfully, false otherwise.
     */
    function addTrophyAward($eid, $ud, $bonus){
        if (!empty($eid) && phive()->moduleExists('Trophy') && !empty($bonus['award_id'])) {
            if($this->shouldAwardAssociateBonus($ud, $bonus['id'])) {
                return false;
            }
            return phive('Trophy')->giveAward($bonus['award_id'], $ud);
        }
        return false;
    }

    /**
     * Adds / gives a deposit bonus to a user.
     *
     * Apart from a few initial checks the basic logic is the same as addUserBonus(), we could in fact
     * refactor the two methods to get rid of some duplicate logic.
     *
     * @see CasinoBonuses::addUserBonus() For a more descriptive description.
     * @uses CasinoBonuses::checkBonusMail() In order to determine if this user got the promo mail or not, only people who
     * got the mail should be able to activate the bonus.
     * @uses CasinoBonuses::allowCountry() To prevent people from the wrong country from activating.
     * @see CasinoBonuses::checkBonusMail()
     * @see CasinoBonuses::allowCountry()
     *
     * @param int $user_id The user id.
     * @param int $bonus_id The bonus id.
     * @param bool $activate Whether or not to activate right away, note that if the bonus is a freespin bonus we call the
     * GP regardless of what this boolean indicates.
     * @param int $deposit The deposit amount.
     *
     * @return int|string Can return new id or error string.
     */
    function addDepositBonus($user_id, $bonus_id, $deposit, $activate = false){ //deposit is in cents
        $bonus    = is_numeric($bonus_id)   ? $this->getBonus($bonus_id) : $bonus_id;
        $user     = is_object($user_id)     ? $user_id : cu($user_id);
        $user_id  = !is_object($user_id)    ? $user_id : $user->getId();
        $amount   = is_numeric($deposit)    ? $deposit : $deposit['amount'];
        $bonus_id = $bonus['id'];

        $bonus_block	= $user->getSetting('bonus_block');
        if(!empty($bonus_block))
            return false;

        if(!$this->checkBonusEmail($user, $bonus_id))
            return false;

        if($this->allowCountry($bonus, $user) !== true)
            return false;

        foreach($this->getDepositBonusEntries($user_id, 'pending') as $b)
            $this->close($b['id'], 'failed', [], $user_id);

        $exclusive_res = $this->handleExclusive($bonus, $user_id);
        if($bonus && $exclusive_res === true){
            $inserts = array(
                'bonus_id'          => $bonus['id'],
                //FRB logic
                'frb_granted'       => $bonus['reward'],
                'frb_remaining'     => $bonus['reward'],
                'game_tags' 	  => $bonus['game_tags'],
                'game_percents' 	  => $bonus['game_percents'],
                'loyalty_percent'   => $bonus['loyalty_percent'],
                'allow_race'    	  => $bonus['allow_race'],
                'stagger_percent'   => $bonus['stagger_percent'],
                'progress_type' 	  => $bonus['progress_type'],
                'end_time'          => $bonus['expire_time']);

            $inserts['user_id']    = $user_id;
            $inserts['reward']     = round(min($amount * $bonus['deposit_multiplier'], mc($bonus['deposit_limit'], $user)));
            $inserts['balance']    = $inserts['reward'];

            $turnover = $this->getTurnover($user, $bonus);

            $inserts['cost']       = ($turnover / 100) * $inserts['reward'];
            $inserts['bonus_type'] = $bonus['bonus_type'];

            if(in_array($bonus['bonus_type'], array('casinowager', 'freespin')))
                $inserts['balance'] = 0;

            $this->addCashCost($inserts, $bonus);
            $this->addBetLimits($inserts, $bonus, $amount);

            if ($this->isUserEligibleForBonusBatch($bonus_id, $user_id)) {
                $inserts['auto_activate_bonus_id'] = $bonus['auto_activate_bonus_id'];
                $inserts['auto_activate_bonus_day'] = $bonus['auto_activate_bonus_day'];
                $inserts['auto_activate_bonus_period'] = $bonus['auto_activate_bonus_period'];
                $inserts['auto_activate_bonus_send_out_time'] = $bonus['auto_activate_bonus_send_out_time'];
            }

            $eid = phive("SQL")->sh($user_id, '', 'bonus_entries')->insertArray('bonus_entries', $inserts);

            $this->addTrophyAward($eid, $user->data, $bonus);

            if($bonus['bonus_type'] == 'freespin') {
                //$this->addFreeSpin($bonus, $eid, $amount / ($bonus['reward'] * 100)); // If we want amount of spins to correspond to deposit amount we need the line to look like this.
                $this->addFreeSpin($bonus, $eid);
            }

            if($activate) {
                phive('Logger')->getLogger('casino')->debug('double-bonus-transactions addDepositBonus', [
                    'user_id' => $user_id,
                    'entry_id' => $eid,
                    'bonus_id' => $bonus_id,
                    'reward' => $bonus['reward'],
                    'bonus_type' => $bonus['bonus_type'],
                    'time' => date('Y-m-d H:i:s')
                ]);

                $this->activatePendingEntry($eid, $user_id);
            }

            return $eid;
        }

        return $exclusive_res;
    }

    /**
     * Activates FRB / freespin bonuses.
     *
     * Apart from internal book keeping this method executes an external call to the GP to enable the freespin play.
     *
     * @param array $entry The bonus entry.
     * @param array $bonus The bonus.
     * @param string $ext_id The eternal id of the freespin bonus.
     * @param string $module The GP module, eg Netent.
     * @param $round_m TODO henrik remove this, refactor all invocations.
     * @param int $nspins The amount of freespins.
     * @param bool $show_event Whether or not to show the activation in the public news / event feed.
     *
     * @return string The external GP id.
     */
    function activateFreeSpin($entry, $bonus, $ext_id, $module, $round_m, $nspins = 0, $show_event = true){
        $user = cu($entry['user_id']);

        $entry['ext_id'] = $ext_id;
        phive($module)->activateFreeSpin($entry, $round_m, $bonus, $ext_id);

        $wager_turnover = phive('Bonuses')->getTurnover($user, $bonus);

        if(empty($wager_turnover)){
            $entry['status'] = 'approved';
        } else {
            $entry['status'] = 'active';
        }

        if($show_event)
            phive('UserHandler')->fspinEvent($nspins, $entry['user_id'], $bonus['game_id']);
        // Is needed for GPs where we are responsible for keeping track of free rounds on our side, ex: Stakelogic and Leander
        // This value will go down for each round used.
        $entry['frb_granted'] = $entry['frb_remaining'] = $nspins;
        $entry['reward'] = 0;
        $entry['cost'] = 0;
        $entry['bonus_tag'] = $bonus['bonus_tag'];
        phive("SQL")->sh($entry, 'user_id', 'bonus_entries')->save($this->getSetting("entries"), $entry);

        if(!empty($bonus['frb_cost'])){
            $frb_cost = empty($bonus['deposit_limit']) ? $bonus['frb_cost'] : ($bonus['frb_cost'] * $nspins);
            $game     = phive('MicroGames')->getByGameId($bonus['game_id'], '');
            $desc = "FRBcost:{$bonus['id']}:{$game['network']}";
            $type = static::FRB_COST;
            $transaction_id = phive('Cashier')->insertTransaction($entry['user_id'], mc($frb_cost, $user), $type, $desc, $bonus['id'], '', $entry['id']);

            if ($transaction_id) {
                lic('addRecordToHistory', [
                    'cash_transaction',
                    new CashTransactionHistoryMessage([
                        'user_id'          => (int) $user->getId(),
                        'transaction_id'   => (int) $transaction_id,
                        'amount'           => (int) mc($frb_cost, $user),
                        'currency'         => $user->getCurrency(),
                        'transaction_type' => $type,
                        'parent_id'        => (int) $entry['id'],
                        'description'      => $desc,
                        'event_timestamp'  => time(),
                    ])
                ], $user);
            }
        }
        return $ext_id;
    }

    // TODO henrik remove this, remove invocations.
    function canActivate(&$b, $uid){
        $GLOBALS['bonus_activation'] = true;
        return true;
    }

    /**
     * Adds an FRB / freespin bonus to a user.
     *
     * @uses CasinoBonuses::activateFreeSpin()
     * @see CasinoBonuses::activateFreeSpin()
     *
     * @param array $bonus The bonus.
     * @param int $eid The bonus entry id.
     * @param int $round_m Round multiplier in case we're looking at an FRB where they get eg 1 spin / 5 EUR of deposits.
     * @param bool $show_event Whether or not to show the activation in the public news / event feed.
     *
     * @return bool|string The external id if successful, false otherwise.
     */
    function addFreeSpin($bonus, $eid, $round_m = 1, $show_event = true){
        if(!empty($bonus['bonus_tag'])){
            $module = phive('Casino')->getNetworkName($bonus['bonus_tag']);
            $entry = $this->getBonusEntry($eid);
            $cur = getCur();
            setCur($entry['user_id']);

            phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(1)', [
                'user_id' => $entry['user_id'],
                'bonus' => $bonus['id'],
                'entry_id' => $eid,
            ]);

            //FRB logic, if supplier supports it, we can do a deposit X money get Y spins with the help of the deposit_multiplier.
            if(in_array(strtolower($bonus['bonus_tag']), $this->getSetting('deposit_spin_gps'))){
                //only deposit based FRBs use deposit_multiplier and need to respect the currency
                $reward = empty($bonus['deposit_multiplier']) ? $entry['reward'] : mc($entry['reward'], '', 'div');
                //we divide by 100 because we don't want to give one spin per cent, but one per eur or 10 sek, 10 sek is handled by the above line
                $nspins = round(($reward * $round_m) / 100);
                phive()->dumpTbl('frb-nspins', [$entry, $round_m, $nspins, $reward]);
            } else {
                $nspins = $entry['reward'];
            }

            // will be either ID of the bonus as provided by the GP or our internal bonus_types:ext_ids
            if (phive()->methodExists($module, 'awardFRBonus')) {
                // We check first if the GP has a function to override bonus config
                $overridden_bonus = phive($module)->fsBonusOverride($entry['user_id'], $bonus);
                if ($overridden_bonus !== false) {
                    $bonus = $overridden_bonus;
                }

                $ext_id = phive($module)->awardFRBonus($entry['user_id'], $bonus['ext_ids'], $nspins, pt($bonus['bonus_name']), $entry);

                phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(2.1)', [
                    'user_id' => $entry['user_id'],
                    'bonus' => $bonus['id'],
                    'overridden_bonus' => $overridden_bonus['id'] ?? '',
                    'entry_id' => $eid,
                    'ext_id' => $ext_id,
                ]);
            } else {
                $ext_id = $bonus['ext_ids'];
                phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(2.2)', [
                    'bonus' => $bonus['id'],
                    'entry_id' => $eid,
                    'ext_id' => $ext_id,
                ]);
            }

            setCur($cur);

            if($ext_id !== false && $ext_id !== 'fail'){
                // create the bonus_entry in bonus_entries table
                phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(3.1)', [
                    'entry' => $entry,
                    'bonus' => $bonus,
                    'ext_id' => $ext_id,
                    'module' => $module,
                    'round_m' => $round_m,
                    'nspins' => $nspins,
                    'show_event' => $show_event,
                ]);
                return $this->activateFreeSpin($entry, $bonus, $ext_id, $module, $round_m, $nspins, $show_event);
            } else if($ext_id === false){
                phive('Logger')->getLogger('game_providers')->debug(__METHOD__ . '(3.2)', [
                    'bonus' => $bonus['id'],
                    'entry_id' => $eid,
                    'ext_id' => $ext_id,
                ]);
                if(phive()->methodExists($module, 'frbStatus')){
                    sleep(5);
                    // TODO here we want to check if the bonus got registered in the external system and if yes,
                    // modify our own data to comply with success. It doesn't currently look like that is happening.
                    // frbStatus should return the missing ext_id and we should save it to achieve more robustness
                    if(phive($module)->frbStatus($entry) === 'activate') {
                        $activation_result = $this->activateFreeSpin($entry, $bonus, false, $module, $round_m, $nspins, $show_event);
                        // create the bonus_entry in bonus_entries table
                        phive('Logger')->getLogger('game_providers')->debug(__METHOD__, [
                            'entry' => $entry,
                            'bonus' => $bonus,
                            'ext_id' => false,
                            'module' => $module,
                            'round_m' => $round_m,
                            'nspins' => $nspins,
                            'show_event' => $show_event,
                            'activation_result()' => $activation_result,
                            '$ext_id' => $ext_id,
                        ]);

                        return $activation_result;
                    }
                }
            }
            // this will change the bonus_entries:status row in case the network issue.
            $this->changeBonusEntryStatus($entry, "corrupted");
            phive('Logger')->getLogger('game_providers')->debug('changeBonusEntryStatus', [
                'message' => 'Change bonus entry status to failed due to network issue',
                '$entry' => $entry,
                '$ext_id' => $ext_id
            ]);
        }
        return false;
    }

    /**
     * This is the logic that runs in case the user has deposited with a reload code, the reload code is stored in Redis
     * upon deposit start and is fetched here upon deposit end / finish in order to get the bonus.
     *
     * @uses CasinoBonuses::getCurReload() To get the reload code from Redis.
     * @uses CasinoBonuses::delCurReload() To delete the reload code from Redis.
     * @uses CasinoBonuses::getReload() To get the bonus with the help of the reload code.
     * @uses CasinoBonuses::addDepositBonus() To add the bonus to the user.
     * @uses CasinoBonuses::activatePendingEntry() To activate the entry right away.
     * @see CasinoBonuses::getCurReload()
     * @see CasinoBonuses::delCurReload()
     * @see CasinoBonuses::getReload()
     * @see CasinoBonuses::addDepositBonus()
     * @see CasinoBonuses::activatePendingEntry()
     *
     *
     * @param DBUser $user The user object.
     * @param int $cents The deposit amount minus potential deposit fees
     * @param int $orig_cents The original deposit amount without deposit fees applied.
     * @param string $currency The ISO3 code for the user's currency.
     *
     * @return array|bool Returns the bonus if successful, otherwise false
     */
    function handleReloadDeposit($user, $cents, $orig_cents, $currency){
        $user_id = $user->getId();
        //$reload  = $this->getReload($_SESSION['reload_code']);
        $rcode   = $this->getCurReload($user);
        $reload  = $this->getReload($rcode, '', true, $user);
        $bid     = $reload['id'];


        if(!empty($reload) && mc($reload['deposit_threshold'], $user) <= $orig_cents){
            //$this->handleExclusive($reload, $user_id);

            phive('Logger')->getLogger('casino')->debug('double-bonus-transactions handleReloadDeposit', [
                'user_id' => $user_id,
                'reload' => $reload,
                'deposit' => $cents,
                'time' => date('Y-m-d H:i:s')
            ]);

            $entry_id = $this->addDepositBonus($user, $reload, $cents);

            if(!$this->isAddedError($entry_id)){
                if(!empty($reload['top_up']))
                    phive('Casino')->bonusChgBalance($user, mc($reload['top_up'], $currency), '#topup.deposit', 84, $bid, $entry_id);
                $this->activatePendingEntry($entry_id, $user_id, true);
                $ret = $reload;
            }else
                $ret = false;
            $this->delCurReload($user);
            return $ret;
        }
        return false;
    }

    /**
     * Gets all the deposit bonus entries for a user.
     *
     * @param int $user_id The user id.
     * @param string $status The status.
     * @param string $type Type WHERE clause.
     *
     * @return array The bonus entries.
     */
    function getDepositBonusEntries($user_id, $status = '', $type = "IN('casino', 'casinowager')"){
        $status =  phive('SQL')->escape($status,false);
        $where_status = empty($status) ? '' : "AND status = '$status'";
        $sql = "SELECT DISTINCT be.* FROM bonus_entries be, bonus_types bt
                WHERE be.user_id = $user_id
                AND bt.deposit_limit != 0
                AND be.bonus_type $type
                $where_status
                AND be.bonus_id = bt.id
                ORDER BY activated_time DESC";

        return phive('SQL')->sh($user_id, '', 'bonus_entries')->loadArray($sql);
    }

    // TODO henrik remove
    function getExpiredDepositBonuses($group_by = ''){
        $date = date('Y-m-d');
        return phive('SQL')->shs('merge', '', null, 'bonus_entries')->loadArray(
            "SELECT DISTINCT be.* FROM bonus_entries be, bonus_types bt
             WHERE end_time < '$date'
             AND bt.deposit_limit != 0
             AND status = 'active'
             AND be.bonus_id = bt.id
             $group_by");
    }

    /**
     * Internal Redis key builder for the reload code.
     *
     * @param mixed $u User identification data.
     *
     * @return string The Redis key.
     */
    function curReloadKey($u){
        $uid = uid($u);
        if(empty($uid)){
            return uniqid();
        }
        return mKey($uid, 'reload_code');
    }

    /**
     * Sets the reload code in Redis, this happens at the beginning of the deposit flow.
     *
     * @param string $code The code.
     * @param mixed $u User identification data.
     *
     * @return null
     */
    function setCurReload($code, $u = false){
        phMset($this->curReloadKey($u), $code);
    }

    /**
     * Deletes the reload code from Redis.
     *
     * @param mixed $u User identification data.
     *
     * @return null
     */
    function delCurReload($u){
        phMdel($this->curReloadKey($u));
    }

    /**
     * Gets the reload code from Redis, this happens at the end of the deposit flow.
     *
     * @param mixed $u User identification data.
     *
     * @return string The reload code.
     */
    function getCurReload($u){
        return phMget($this->curReloadKey($u));
    }

    /**
     * Fails all active or pending deposit bonuses when time of windrawal.
     *
     * @uses CasinoBonuses::getDepositBonusEntries()
     * @uses CasinoBonuses::fail()
     * @see CasinoBonuses::getDepositBonusEntries()
     * @see CasinoBonuses::fail()
     *
     * @param int $user_id The user id.
     * @param string $msg Transaction description.
     *
     * @return array An array of all the bonus entries that were failed.
     */
    function failDepositBonuses($user_id, $msg = ''){
        $res = array();
        foreach($this->getDepositBonusEntries($user_id) as $e){
            if($e['status'] != 'approved' && $e['status'] != 'failed'){
                $this->fail($e['id'], $msg, $e['user_id']);
                $res[] = $e['id'];
            }
        }
        return $res;
    }

    /**
     * A bonus has the ability to initially end up as deactivated / pending and then it's up to the user to
     * activate it at which point the user might have for instance 7 days to complete it. However, pending
     * bonuses also have a best before date but that date but that time span might be different than the
     * time to complete. Anyway, this method activates a pending bonus.
     *
     * @see CasinoBonuses::failExpiredBonuses()
     * @uses Bonuses::addActivation()
     * @uses Bonuses::editBonusEntry()
     * @see Bonuses::addActivation()
     * @see Bonuses::editBonusEntry()
     *
     * @param int $entry_id The bonus entry to activate.
     * @param int $uid The user id.
     * @param bool $force_activate In case we have a bonus of type normal without a bonus_type then the bonus logic
     * has just been used in order to for instance give an award and then this argument should be false to bypass
     * the typical activation logic, otherwise it should be true.
     *
     * @return bool False if the bonus was not activated, otherwise true.
     */
    function activatePendingEntry($entry_id, $uid, $force_activate = false){
        $entry = $this->getBonusEntry($entry_id, $uid);
        if(empty($entry))
            return false;

        $bonus = $this->getBonus($entry['bonus_id'], false);

        if($entry['status'] == 'pending' || $force_activate){
            $update = array();
            if(!$this->addActivation($update, $bonus, $entry['user_id']))
                return false;
            uEvent('activatedbonus', $entry['reward'], $bonus['bonus_name'], '', $entry['user_id']);
            if(phive()->moduleExists('Trophy'))
                phive()->pexec('Bonuses', 'wsOnProgress', array($entry['user_id'], 'start'));
            if(in_array($entry['bonus_type'], ['casino', 'casinowager'])) {
                phive('Logger')->getLogger('casino')->debug('double-bonus-transactions activatePendingEntry', [
                    'user_id' => $entry['user_id'],
                    'entry_id' => $entry_id,
                    'bonus_id' => $entry['bonus_id'],
                    'reward' => $bonus['reward'],
                    'bonus_type' => $bonus['bonus_type'],
                    'time' => date('Y-m-d H:i:s')
                ]);

                phive('Cashier')->insertBonusActivation($entry['user_id'], $entry['reward'], $entry['bonus_id'], $entry['bonus_type'], $entry['id']);
                $this->welcomeBonusWithAward($uid, $entry['bonus_id']);
            }

            if($bonus['type'] == 'normal' && empty($bonus['bonus_type']))
                $update['status'] = 'approved';
            return $this->editBonusEntry($entry_id, $update, $uid);
        }

        // We have a type normal without bonus_type, this means it's an "activator" so should not generate any active entries
        if($bonus['type'] == 'normal' && empty($bonus['bonus_type']))
            return $this->editBonusEntry($entry_id, ['status' => 'approved'], $uid);

        return false;
    }

    /**
     * A cron job to expire / fail active bonus entries that were not completed in time and pending bonus entries
     * that have been just sitting there for too long without being touched.
     *
     * @return null
     */
    function failExpiredBonuses(){
        $a_month_ago = phive()->hisMod('-30 day');
        $date = date('Y-m-d');

        foreach(phive('SQL')->shs('merge', '', null, 'bonus_entries')->loadArray("SELECT * FROM bonus_entries WHERE end_time < '$date' AND status IN('active','pending')") as $e){
            $this->fail($e, "Expired on {$e['end_time']}", $e['user_id']);
        }

        foreach(phive('SQL')->shs('merge', '', null, 'bonus_entries')->loadArray("SELECT * FROM bonus_entries WHERE last_change < '$a_month_ago' AND status = 'pending'") as $pe){
            /**
             * @todo: This logic was introduced in RHEA-2806
             * To check if it can be standardised across all brands
             * @link https://videoslots.atlassian.net/browse/RHEA-2806
             */
            $this->fail(
                $pe,
                "Has been pending since $a_month_ago so failing it", $pe['user_id'],
                (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) ? ['status' => 'expired'] : []);
        }
    }

    // TODO henrik remove
    function getByStatusUserTypeDate($status, $user_id, $type = "'casino', 'casinowager'", $date = ''){
        $date = empty($date) ? date('Y-m-d') : $date;
        $sql = "SELECT DISTINCT be.*, bt.bonus_name FROM bonus_entries be, bonus_types bt
        WHERE be.status = '$status'
        AND be.end_time >= '$date'
        AND be.user_id = $user_id
        AND be.bonus_id = bt.id
        AND bt.bonus_type IN($type)";
        return phive('SQL')->sh($user_id, '', 'bonus_entries')->loadArray($sql);
    }

    /**
     * Gets all bonus entries by status and time period.
     *
     * @param string $status Status WHERE clause.
     * @param string $sdate Start date / stamp.
     * @param string $edate End date / stamp.
     *
     * @return array The bonus entries.
     */
    function getByStatusPeriod($status, $sdate = '', $edate = ''){
        $sdate = phive('SQL')->escape($sdate,false);
        $edate = phive('SQL')->escape($edate,false);
        if(!empty($sdate) && !empty($edate))
            $where = " AND end_time >= '$sdate' AND end_time <= '$edate'";
        return phive('SQL')->shs('merge', '', null, 'bonus_entries')->loadArray("SELECT * FROM bonus_entries WHERE status = '$status' $where");
    }

    // TODO henrik remove
    function getCompletedPeriod($sdate, $edate){
        return $this->getByStatusPeriod('approved', $sdate, $edate);
    }

    /**
     * Gets a bonus by bonus code.
     *
     * @param string $code The bonus code.
     *
     * @return array The bonus.
     */
    function getByCode($code){
        return phive('SQL')->loadAssoc('', 'bonus_types', "bonus_code = '$code'");
    }

    // TODO remove
    function getUserEntryByCode($user_id, $code){
        $user_id = intval($user_id);
        $code = phive('SQL')->escape($code,false);
        return phive('SQL')->sh($user_id, '', 'bonus_entries')->loadAssoc("SELECT * FROM bonus_entries WHERE user_id = $user_id AND bonus_id IN (SELECT id FROM bonus_types WHERE bonus_code = '$code')");
    }

    /**
     * Gets all non deposit bonuses where expire_time is larger than a passed in date / stamp.
     *
     * @param string $date The date / stamp that expire_time needs to be larger than.
     * @param string $type Optional type filter.
     * @param string $extra Optional extra WHERE clauses.
     *
     * @return array The array of bonuses.
     */
    function getNonDeposits($date = '', $type = '', $extra = ''){
        $date = empty($date) ? date('Y-m-d') : $date;
        $where_type = empty($type) ? '' : "AND type = '$type'";
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        return phive('SQL')->loadArray("SELECT * FROM bonus_types WHERE deposit_limit = 0 AND brand_id = {$brandId} AND expire_time >= '$date' $where_type $extra");
    }

    /**
     * Gets a bonus by way of a reload code.
     *
     * @param string $code The reload code.
     * @param string $date The date / stamp that expire_time needs to be larger than.
     * @param $do_archive TODO henrik remove, don't forget to refactor invocations.
     * @param DBUser $user Optional user object, if passed in we will check if the user can get a reload bonus
     * as per regulations.
     *
     * @return bool|array The bonus if found and allowed, false otherwise.
     */
    function getReload($code, $date = '', $do_archive = true, $user = null){
        $code = phive('SQL')->escape($code,false);
        $date = phive('SQL')->escape($date,false);
        $code = trim($code);
        if(empty($code))
            return array();
        $date = empty($date) ? date('Y-m-d') : $date;
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        $sql = "SELECT * FROM bonus_types WHERE reload_code = '$code' AND expire_time >= '$date' AND brand_id = {$brandId}";
        $res = phive('SQL')->loadAssoc($sql);

        if(empty($user)){
            return $res;
        }

        return lic('canGetReloadBonus', [$res, $user], $user) ? $res : false;
    }

    /**
     * Filters an array of bonuses, remove all bonuses the user should be allowed to get.
     *
     * TODO henrik remove the $user arg to getCountry(), it does not make sense, also remove passing the user by ref.
     *
     * @param DBUser $user The user object.
     * @param array &$bs The bonuses to filter.
     *
     * @return array The bonuses, now filtered to remove all bonuses not allowed for the user's country.
     */
    function filterBlockedCountries(&$user, &$bs){
        $country = $user->getCountry($user);
        return phive('MicroGames')->filterBlocked($bs, $country);
    }

    /**
     * Gets all reload and deposit bonuses.
     *
     * @param string $date The date / stamp that expire_time needs to be larger than.
     * @param string $type Optional type filter.
     * @param string $extra Optional extra WHERE clauses.
     *
     * @return array The array with bonuses.
     */
    function getReloadsAndDeposits($date = '', $type = '', $extra = ''){
        $date = empty($date) ? date('Y-m-d') : $date;
        $type = phive('SQL')->escape($type,false);
        $date = phive('SQL')->escape($date,false);
        $where_type = empty($type) ? '' : "AND type = '$type'";

        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        $str = "SELECT * FROM bonus_types WHERE deposit_limit > 0 AND brand_id = {$brandId} AND expire_time >= '$date' $where_type $extra";
        return phive('SQL')->loadArray($str);
    }

    /**
     * Gets all types.
     *
     * @return array The array of all possible bonus types.
     */
    function getBonusTypes(){
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        return phive('SQL')->loadKeyValues("SELECT * FROM bonus_types WHERE brand_id = {$brandId} GROUP BY type", 'type', 'type');
    }

    /**
     * Gets a bonus entry by way of the GP / external id.
     *
     * @param string $ext_id The GP / ext id.
     * @param int $user_id The user id.
     * @param string $status Optional status WHERE clause.
     *
     * @return xxx
     */
    function getEntryByExtId($ext_id, $user_id, $status = ''){
        $status_sql = empty($status) ? '' : " AND $status ";
        $str 	= "SELECT * FROM bonus_entries WHERE ext_id = '$ext_id' AND user_id = $user_id $status_sql";
        return phive('SQL')->sh($user_id)->loadAssoc($str);
    }

    // TODO henrik remove
    function getEntriesByModule($uid, $module, $status){
        return phive('SQL')->sh($uid, '', 'bonus_entries')->arrayWhere('bonus_entries', array('user_id' => $uid, 'bonus_tag' => $module, 'status' => $status));
    }

    /**
     * Get bonus entry data by user ID and either gameId (with GP prefix) or bonusEntryId
     *
     * @param int $p_iUserId The user ID
     * @param mixed $p_mId bonus_entries:game_id (with GP prefix), bonus_entries:ext_id (with GP prefix) or the bonus_entries:id
     * @param string $p_sFilter game_id|ext_id|''
     * @param string $p_sGpName The game Providers name
     * @return array The bonus entry.
     */
    function getBonusEntryBy($p_iUserId, $p_mId, $p_sFilter = '', $p_sGpName = ''){
        $minimum_fs_wager = (int) licSetting('minimum_fs_wager', cu($p_iUserId)) * 100;

        $query = "
          SELECT
            be.id,
            be.bonus_id,
            be.user_id,
            be.balance,
            be.start_time,
            be.end_time,
            be.status,
            be.reward,
            be.cost,
            be.frb_remaining,
            be.frb_granted,
            be.bonus_type,
            bt.frb_denomination,
            bt.frb_lines,
            IFNULL(NULLIF(bt.rake_percent, 0), {$minimum_fs_wager}) as rake_percent,
            bt.frb_coins,
            bt.game_id
          FROM bonus_entries be
          INNER JOIN bonus_types bt ON bt.id = be.bonus_id AND bt.bonus_type = 'freespin'";

        if(($p_sFilter === 'game_id' && strpos($p_mId, strtolower($p_sGpName)) !== false) || ($p_sFilter === 'be_game_id')) {

            // we are getting the entry by bonus_entries:game_id
            $query = "$query AND bt.game_id = " . phive("SQL")->escape($p_mId) . " WHERE IF (IFNULL (NULLIF (bt.rake_percent, 0), {$minimum_fs_wager}) > 0, be.status = 'active', be.status = 'approved') AND";

        } else if($p_sFilter === 'ext_id' && strpos($p_mId, strtolower($p_sGpName)) !== false){

            // we are getting the entry by bonus_entries:ext_id
            $query = "$query WHERE be.ext_id = " . phive("SQL")->escape($p_mId) . " AND IF (IFNULL (NULLIF (bt.rake_percent, 0), {$minimum_fs_wager}) > 0, be.status = 'active', be.status = 'approved') AND";

        } else {

            // we are getting the entry by bonus_entries:id
            $query = "$query WHERE be.id = " . phive("SQL")->escape($p_mId) . " AND";
        }

        $query = "$query be.user_id = " . phive("SQL")->escape($p_iUserId) . " ORDER BY id DESC LIMIT 1";

        return phive('SQL')->sh($p_iUserId, '', 'bonus_entries')->loadAssoc($query);
    }

    /**
     * Gets all active bonus entries of a certain type for a certain user.
     *
     * @param string $type Comma separated types (if more than one).
     * @param int $uid The user id.
     *
     * @return array The bonus entries.
     */
    function getActiveEntriesByType($type, $uid){
        $uid = (int)$uid;
        // If $type is ever coming from user input we need some kind of filtering / security to protect against
        // SQL injection.
        $str 		= "SELECT * FROM bonus_entries WHERE status = 'active' AND bonus_type IN($type) AND user_id = $uid";
        return phive('SQL')->sh($uid, '', 'bonus_entries')->loadArray($str);
    }

    /**
     * Gets all deposit bonus entries for a certain bonus code with an optional cutoff date with regards to the expire_time.
     *
     * @param string $bcode The bonus code.
     * @param string $date The date / stamp that expire_time needs to be larger than.
     *
     * @return array The bonus entries.
     */
    function getDeposits($date = '', $bcode = ''){
        $date        = empty($date) ? date('Y-m-d') : $date;
        $where_bcode = empty($bcode) ? '' : " AND bonus_code = '$bcode'";
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        $str         = "SELECT * FROM bonus_types WHERE deposit_limit > 0 AND brand_id = {$brandId} AND expire_time >= '$date' AND reload_code = '' $where_bcode";
        return phive('SQL')->loadArray($str);
    }

    /**
     * Gets all non deposit bonus entries for a certain bonus code with an optional cutoff date with regards to the expire_time.
     *
     * @param string $bcode The bonus code.
     * @param string $date The date / stamp that expire_time needs to be larger than.
     *
     * @return array The bonus entries.
     */
    function getNonDepsByBcode($bcode, $date = ''){
        if(empty($bcode))
            return array();
        $date = empty($date) ? date('Y-m-d') : $date;
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        return phive('SQL')->loadArray("SELECT * FROM bonus_types WHERE bonus_code = '$bcode' AND brand_id = {$brandId} AND deposit_limit = 0 AND expire_time >= '$date' AND reload_code = ''");
    }

    /**
     * This method is involved in logic that creates bonuses from bonus template strings, they look like this:
     *
     * ```
     * expire_time::{{phive||modDate||+1 day}}
     * num_days::7
     * ...
     * deposit_threshold::1000
     * award_id::2143
     * ```
     *
     * Note the {{ ... }} there, that will be executed to create an expire time one day in the future.
     *
     * @uses Config::getValue()
     * @see Config::getValue()
     *
     * @param string $tag The config tag.
     * @param string $name The config name.
     *
     * @return array The bonus to insert into bonus_types.
     */
    function templateToArr($tag, $name){
        $template = phive("Config")->getValue($tag, $name);

        foreach(explode("\n", $template) as $line){
            list($field, $value) = explode("::", $line);
            if(!empty($field))
                $insert[trim($field)] = rep(trim($value));
        }

        return $insert;
    }

    /**
     * Get new bonus from template
     *
     * @param string $tag The config tag.
     * @param string $name The config name.
     * @param array $default The config default values.
     *
     * @return bool|array The new bonus if successful, false otherwise.
     * @see CasinoBonuses::templateToArr()
     */
    public function insertTemplate($tag, $name, $default = [])
    {
        $arr = $this->templateToArr($tag, $name);
        if (empty($arr)) {
            return false;
        }
        if (!empty($default)) {
            unset($default['id']);
            foreach ($default as $key => $value) {
                $arr[$key] = $arr[$key] ?? $value;
            }
        }
        $arr['ext_ids'] = $arr['ext_ids'] ?? ' ';
        $arr['game_id'] = $arr['game_id'] ?? ' ';

        unset($arr['country_version']); // clean up country specific configuration

        return phive("SQL")->insertArray('bonus_types', $arr)
            ? $this->getBonus(phive("SQL")->insertBigId())
            : false;
    }

    // TODO henrik remove
    function getBonusStats($sdate, $edate, $cur){
        return phive('SQL')->readOnly()->loadArray("SELECT * FROM bonus_daily_stats WHERE created_at >= '$sdate' AND created_at <= '$edate' AND currency = '$cur'", 'ASSOC', 'created_at');
    }

    // TODO henrik remove
    function bonusStatsCron($date){
        $sql = phive('SQL');
        $gby = array('bonus_id', 'currency');
        // Sharding TODO test this
        $cts = $sql->shs('merge', '', null, 'cash_transactions')->loadArray("
      SELECT ct.bonus_id, ct.currency, REPLACE(bt.bonus_name, '\'', '') AS bonus_name, bt.bonus_code, bt.reload_code, bt.bonus_type, v.voucher_name
      FROM cash_transactions ct
      INNER JOIN bonus_types AS bt ON bt.id = ct.bonus_id
      LEFT JOIN vouchers AS v ON v.bonus_id = ct.bonus_id
      WHERE ct.timestamp >= '$date 00:00:00'
      AND ct.timestamp <= '$date 23:59:59'
      AND ct.bonus_id != 0
      GROUP BY ct.bonus_id, ct.currency", 'ASSOC', $gby);

        $com_where = "WHERE `timestamp` >= '$date 00:00:00' AND `timestamp` <= '$date 23:59:59' AND bonus_id != 0";

        $ct_string = "
      SELECT sum( amount ) AS %1, bonus_id, currency
      FROM cash_transactions
      $com_where
      AND transactiontype = %2
      GROUP BY bonus_id, currency";

        $dep_str = "
      SELECT sum( amount ) AS dep_amount, count( * ) AS dep_count, bonus_id, currency
      FROM %1
      $com_where
      GROUP BY bonus_id, currency";

        $deposits = $sql->shs('merge', '', null, 'deposits')->loadArray(str_replace('%1', 'deposits', $dep_str), 'ASSOC', $gby);
        $fdeposits = $sql->shs('merge', '', null, 'first_deposits')->loadArray(str_replace('%1', 'first_deposits', $dep_str), 'ASSOC', $gby);

        $e_str = "SELECT count(*) AS e_count, u.currency, be.bonus_id FROM bonus_entries be, users u WHERE be.user_id = u.id AND be.status = 'active' GROUP BY bonus_id, currency";
        $actives = $sql->shs('merge', '', null, 'bonus_entries')->loadArray($e_str, 'ASSOC', $gby);
        //$keys = array_merge(array_keys($cts), array_keys($actives));

        foreach($cts as $key => &$c){

            $transaction_type_awards = $this->getTransactionType($c['bonus_type'], 'credit');
            $transaction_type_fails = $this->getTransactionType($c['bonus_type'], 'debit');

            $awards = $sql->shs('merge', '', null, 'cash_transactions')->loadArray(str_replace(array('%1', '%2'), array('award_amount', $transaction_type_awards), $ct_string), 'ASSOC', $gby);
            $fails = $sql->shs('merge', '', null, 'cash_transactions')->loadArray(str_replace(array('%1', '%2'), array('fail_amount', $transaction_type_fails), $ct_string), 'ASSOC', $gby);

            $c['award_amount']     = $awards[$key]['award_amount'];
            $c['fail_amount']      = abs($fails[$key]['fail_amount']);
            $c['dep_amount']       = $deposits[$key]['dep_amount'];
            $c['dep_count']        = $deposits[$key]['dep_count'];
            $c['first_dep_amount'] = $fdeposits[$key]['dep_amount'];
            $c['first_dep_count']  = $fdeposits[$key]['dep_count'];
            $c['active_count']     = $actives[$key]['e_count'];
            $c['created_at']       = $date;
        }

        $sql->insert2DArr('bonus_daily_stats', $cts);
    }

    /**
     * Outputs the bonus thumbnail / picture.
     *
     * @param array &$bt The bonus type (bonus).
     *
     * @return string|null
     */
    function doPic(&$bt, $return = false){
        if($bt['bonus_type'] == 'casinowager'){
            $base = ($bt['deposit_multiplier'] * 100).'_'.($bt['deposit_limit'] / 100).'_depositbonus_reward_event';
            $default = 'events/depositbonus.png';
        }else if($bt['bonus_type'] == 'freespin'){
            $base = $bt['frb_spins']."_freespins_event";
            $default = 'events/freespins.png';
        }else if(strtolower($bt['type']) == 'vip'){
            $base = 'vip';
        }else
            $base = 'logo_color';

        $path = "events/$base.png";

        if($return) {
            return fupUri($path, true, $default);
        }

        fupUri($path, false, $default);
    }

    /**
     * Wrapper around getUserBonuses().
     *
     * @uses CasinoBonuses::getUserBonuses()
     * @see CasinoBonuses::getUserBonuses()
     *
     * @param DBUser $user User object.
     *
     * @return array The first active bonus entry.
     */
    function getCurrentActive($user){
        if(empty($user))
            return [];
        return array_shift(phive('Bonuses')->getUserBonuses($user->getId(), 1, "= 'active'"));
    }

    /**
     * Wrapper around getUserBonuses().
     *
     * @uses CasinoBonuses::getUserBonuses()
     * @see CasinoBonuses::getUserBonuses()
     *
     * @param DBUser $user User object.
     *
     * @return array The first active bonus entry that is not an FRB.
     */
    function getCurrentForProgress($user){
        if(empty($user))
            return false;
        $bs = phive('Bonuses')->getUserBonuses($user->getId(), 1, "= 'active'");
        foreach($bs as $b){
            if($b['bonus_type'] != 'freespin')
                return $b;
            else if(!empty($b['cost']))
                return $b;
        }
        return false;
    }

	/**
	 * Get one active welcome and freespin bonus.
	 *
	 * @param DBUser $user User object.
	 *
	 * @return array Freespin and welcome bonus.
	 */
	function getActiveBonusesForProgress(DBUser $user, $bonus_entry_id = false): array
	{
		$bonus_entries = phive('Bonuses')->getUserBonuses($user->getId(), false, "= 'active'");
		$data = [
			'freespin' => [],
			'welcome-bonus' => []
		];

		if (!empty($bonus_entries)) {
			foreach($bonus_entries as $bonus_entry) {
				if (!empty($data['freespin']) && !empty($data['welcome-bonus'])) {
					break;
				}

				if(!empty($bonus_entry['cost'])) {
					if (strpos($bonus_entry['bonus_name'], 'Welcome Bonus') !== false && empty($data['welcome-bonus'])) {
						$data['welcome-bonus'] = $this->getBonusPercentage($bonus_entry);
					} else if(empty($data['freespin'])) {
						$data['freespin'] = $this->getBonusPercentage($bonus_entry);
					}
				}
			}
        }

		if (!empty($bonus_entry_id)) {
			$finished_bonus_entry = phive('Bonuses')->getBonusEntry($bonus_entry_id, $user->getId());
			if (!empty($finished_bonus_entry)) {
				$bonus = phive('Bonuses')->getBonus($finished_bonus_entry['bonus_id']);
				if (strpos($bonus['bonus_name'], 'Welcome Bonus') !== false && empty($data['welcome-bonus'])) {
					$data['welcome-bonus'] = $this->getBonusPercentage($finished_bonus_entry);
				} else if (empty($data['freespin'])) {
					$data['freespin'] = $this->getBonusPercentage($finished_bonus_entry);
				}
			}
		}

		$data['entry_id'] = $bonus_entry_id;

        return $data;
	}

    /**
	 * Get bonus percentage data for display
	 * @param $bonus_entry
	 * @return array
	 */
	function getBonusPercentage($bonus_entry): array
	{
		// $bar_width = 2.3;
        $bar_width = siteType() === 'normal' ? 2.3 : 1.5;
		$progressData = phive("Bonuses")->getBonusProgressData($bonus_entry, $bar_width);

        return [
            'progress' => $progressData['progress'],
            'bonus_entry' => $bonus_entry,
            'progress_width' => $progressData['progress_width']
        ];
    }

    /**
     * Checks if we have a bonus code, and if it's the same as the bonus code that the user has registered with.
     *
     * @return string Empty string if no bonus code, otherwise the bonus code.
     */
    function getBonusCode()
    {
        $bonus_code = '';
        if(!empty($_SESSION['affiliate'])) {
            $bonus_code = $_SESSION['affiliate'];
        }

        $user = cu();

        if(!empty($user)) {
            $bonus_code = $user->getAttribute('bonus_code');
        }

        return $bonus_code;
    }

    /**
     * Returns true if there is an active free spin (bonus entry).
     *
     * @param object|null $user
     * @param array|null $game
     * @return bool
     */
    public function isFreeSpinGameSession(object $user = null, array $game = null): bool {
        if (empty($user) || empty($game)) {
            return false;
        }

        $bonus_entry = $this->getBonusEntryBy($user->getId(), $game['game_id'], 'be_game_id');

        if (empty($bonus_entry) || $bonus_entry['bonus_type'] !== 'freespin') {
            return false;
        }

        return ($bonus_entry['frb_remaining'] > 0);
    }

    /**
     * Gets forfeit bonus flag by bonus_types id.
     *
     * @param int $bonus_type_id The bonus type id.
     *
     * @return boolean The bonus.
     */
    function getForfeitBonusFlag($bonus_type_id){
        $bonus_type_id = intval($bonus_type_id);
        $forfeit_bonus =  phive("SQL")->getvalue("SELECT forfeit_bonus FROM ".$this->getSetting("types")." WHERE id = $bonus_type_id");
        return $forfeit_bonus;
    }

    public function getExtFrbIds($bonus){
        if(strpos($bonus['ext_ids'], '|') !== false){
            // We have a separated string.
            if(strpos($bonus['ext_ids'], ':') !== false){
                // We have a dual separated string.
                return phive()->fromDualStr($bonus['ext_ids']);
            }
            // Single separated so we just return the numerical split result.
            return phive()->fromDualStr($bonus['ext_ids'], '|', '');
        }
        // If 'ext_ids' does not contain '|', check if it contains a colon ':' (Key-Value pairs)
        if(strpos($bonus['ext_ids'], ':') !== false){
            // Convert the colon-separated string into an array
            return phive()->fromDualStr($bonus['ext_ids']);
        }
        // If 'ext_ids' contains neither '|' nor ':', return it as is
        return $bonus['ext_ids'];
    }

    public function showWelcomeActivationPopup(int $userId): bool
    {
        return phive('Cashier')->hasOnlyOneApprovedDeposit($userId)
            && $this->shouldShowWelcomeOffer($userId);
    }

    /**
     * This method checks if the user jurisdiction is eligible for the welcome offer.
     *
     * @param int $userId The ID of the user.
     * @return bool Returns true if the welcome offer should be shown, false otherwise.
     */
    public function shouldShowWelcomeOffer(int $userId): bool
    {
        $is_brand_activation_enabled = phive("Config")->getValue('activation-based-on-brand', 'show-welcome-offer-activation-popup') === 'yes';
        if (!$is_brand_activation_enabled) {
            return false;
        }

        $jurisdictions = array_filter(
            explode(',', phive("Config")->getValue('activation-based-on-jurisdictions', 'hide-welcome-offer-activation-popup-jurisdictions'))
        );

        $user = cu($userId);

        return !in_array($user->getJurisdiction(), $jurisdictions);
    }

    /**
     * Activate pending welcome deposit bonuses for a user.
     *
     * @param int $user_id The ID of the user.
     * @return bool
     */
    public function activateWelcomeOffers(int $user_id): bool {


        $user = cu($user_id);

        $lic_bonus = lic('getFirstDepositBonus', [], $user);
        $bonus_type = empty($lic_bonus) ? $this->getDeposits($user->getAttr('register_date'), '')[0] : $lic_bonus;

        if (!empty($bonus_type)) {
            $bonus_id = $bonus_type['id'];
            $welcome_bonus = phive('SQL')->sh($user_id, '', 'bonus_entries')->loadAssoc("SELECT * FROM bonus_entries WHERE user_id = $user_id AND bonus_id = $bonus_id");

            return $this->activatePendingEntry($welcome_bonus['id'], $user_id);
        }

        return false;
    }


	/**
	 * Get casino wager progress
	 *
	 * @param array $bonus_entry
	 * @return array|false
	 */
	public function getCasinoWagerProgressPercentage(array $bonus_entry)
	{
		$bonus = phive('Bonuses')->getBonus($bonus_entry['bonus_id']);
		if (empty($bonus) || $bonus_entry['bonus_type'] !== 'casinowager' || $bonus_entry['stagger_percent'] <= 0) {
			return [
				'progress' => '0.00',
				'completed_instalments' => 0,
				'installment_count' => 0
			];
		}

		$completed_instalments = 0;
		$total_bonus = ($bonus['rake_percent'] / 100) * $bonus_entry['reward']; //5000000
		$user_progress = $bonus_entry['progress']; //1250
		$stagger_percentage = $bonus_entry['stagger_percent']; //0.00125

		$instalment_amount = $total_bonus * $stagger_percentage;
		$installment_count = $total_bonus / ($total_bonus * $stagger_percentage);

		if ($user_progress >= $instalment_amount) {
			$completed_instalments = floor($user_progress / $instalment_amount);
		}

		if ($user_progress == $instalment_amount && $completed_instalments < $installment_count) {
			$progress = '0.00';
		} else {
			while ($user_progress >= $instalment_amount) {
				$user_progress -= $instalment_amount;
			}

			$progress = number_format((($user_progress / $instalment_amount) * 100), 2);
		}

		if ($completed_instalments === ceil($installment_count)) {
			$progress = '100.00';
		}

		return [
			'progress' => $progress,
			'completed_instalments' => $completed_instalments,
			'installment_count' => ceil($installment_count)
		];
	}

	/**
	 * Return progress data for display
	 *
	 * @param $b
	 * @param $bar_width
	 * @param bool $divideProgress
	 * @return array
	 */
	function getBonusProgressData($b, $bar_width, bool $divideProgress = false): array
	{
		if ($b['bonus_type'] == 'casinowager') {
			$progressData = phive("Bonuses")->getCasinoWagerProgressPercentage($b);
			$progress_width = $progressData['progress'];
			$progress = $progressData['progress'] . '% ' . $progressData['completed_instalments'] . '/' . ($progressData['installment_count']);
		} else {
			$progress = phive("Bonuses")->progressPercent($b);
			$progress_width = $progress;
			$progress = number_format($progress, 2);
		}

		if ($divideProgress) {
			$progress_width = $progress_width / 100;
		}

		return [
            'bonus_type' => $b['bonus_type'],
			'progress' => $progress,
			'progress_width' => $progress_width * $bar_width
		];
	}
    /**
     * Determines if an associate bonus should be awarded based on the user's bonus details.
     * For MegaRiches, we always need to return false if certain conditions are met.
     *
     * @param DBUser $user The user object.
     * @param string|int $bonus_id The ID of the bonus.
     * @return bool True if the associate bonus should be awarded, otherwise false .
     */
    public function shouldAwardAssociateBonus($user, $bonus_id): bool
    {
        /** @var DBUser $user */
        $user = cu($user);

        $lic_bonus = lic('getFirstDepositBonus', [], $user);
        $is_megariches = phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_MEGARICHES;
        $is_welcome_bonus = (int)$lic_bonus['id'] === (int)$bonus_id;
        $is_megariches_welcome_bonus_award = $is_megariches && !empty($lic_bonus['award_id']);
        $jurisdiction = ($user->getJurisdiction() == 'UKGC' || $user->getJurisdiction() == 'SGA');

        if ($is_megariches_welcome_bonus_award && $jurisdiction && $is_welcome_bonus) {
            return true;
        }
        return false;
    }

    /**
     * Processes the welcome bonus trophy award for a user, if bonus contains award
     *
     * @param int $user_id The ID of the user.
     * @param string|int $bonus_id The ID of the bonus.
     * @return mixed The result of awarding the bonus or other actions.
     */
    public function welcomeBonusWithAward($user_id, $bonus_id)
    {
        $user = cu($user_id);
        $deposit_count = phive('Cashier')->getApprovedDepositsCount($user_id);
        $lic_bonus = lic('getFirstDepositBonus', [], $user);

        if (empty($lic_bonus)) {
            return;
        }

        if ($this->shouldAwardAssociateBonus($user, $bonus_id) && $deposit_count) {
			phive('Trophy')->giveAward($lic_bonus['award_id'], $user->data);
        }

        if ($this->isUserEligibleForBonusBatch($lic_bonus['id'], $user_id)) {
            $bonus_entry = $this->getBonusEntriesByUserAndBonusId($user_id, $bonus_id);
            if (empty($bonus_entry)) {
                return;
            }

            $this->createWelcomeBonusWeeklyAwardEntries($bonus_entry);
            $this->checkForActivatedWelcomeBonusTrophy($bonus_entry, $user_id);
        }
    }

    public function getLastGameSessionId($uid, $game_id)
    {
        $ins = [
            'user_id' => $uid,
            'game_ref' => $game_id,
            'device_type' => phive()->isMobile() ? 1 : 0
        ];

        $ugs =  phive('Casino')->getLastGameSession($ins);

        return $ugs['id'] ?? null;
    }

    /**
     * @param array $entry
     * @return bool
     */
    private function userActivatedBonus(array $entry): bool
    {
        // Invalid bonus entry supplied - Assume bonus was not activated
        if (empty($entry['bonus_id']) || empty($entry['user_id']) || empty($entry['bonus_type'])) return false;

        // Check if bonus was activated in the past
        $type = $this->getTransactionType($entry['bonus_type'], 'credit');
        $transaction = phive( 'SQL')
            ->sh($entry['user_id'], '', 'cash_transactions')
            ->loadAssoc( "SELECT COUNT(*) AS cnt FROM cash_transactions WHERE transactiontype = {$type} AND bonus_id = {$entry['bonus_id']} AND user_id = {$entry['user_id']}");

        // There is history of activation
        return isset($transaction['cnt']) && intval($transaction['cnt']) > 0;
    }

    /**
     * Get all users ids with welcome bonuses in needed state
     */
    public function getAllUserIdsWithWelcomeBonuses(string $status = 'pending')
    {
        $users_list = [];
        $bonus_entries = phive('SQL')->shs()->loadArray(
                "SELECT user_id, bonus_id, id, bonus_type, stagger_percent, progress, reward
                FROM bonus_entries
                WHERE status = '$status'
                AND bonus_type != 'freespin'"
            );

        foreach ($bonus_entries as $bonus_entry) {

            $user = phive('UserHandler')->getUser((int)$bonus_entry['user_id']);

            //if user not exist for any reason skip it
            if (is_null($user)) {
                continue;
            }
            $lic_bonus = lic('getFirstDepositBonus', [], $user);
            $bonus_type = empty($lic_bonus) ? $this->getDeposits($user->getAttr('register_date'), '')[0] : $lic_bonus;

            if (!empty($bonus_type) && $bonus_entry['bonus_id'] == $bonus_type['id']) {
                $users_bonus_data[] = [
                    'user_obj' => $user,
                    'bonus_entry' => $bonus_entry
                ];
            }
        }

        return $users_bonus_data;
    }

	/**
	 * Checks and handles the automatic activation of a welcome bonus weekly awards.
	 *
	 * @param array $welcome_bonus_entry record.
	 * @param int $user_id The ID of the user.
	 * @return bool
	 */
	public function isUserEligibleForBonusBatch(int $bonus_id, int $user_id): bool
	{
        if (!$this->getSetting('is_batched_welcome_bonus_enabled')) {
            return false;
		}

		$user = cu($user_id);
		if (empty($user) || empty($bonus_id)) {
			return false;
		}

        if ($this->didUserAlreadyActivateWelcomeBonusTrophy($user_id)) {
            return false;
        }

        $bonus = $this->getBonus($bonus_id);
        if (empty($bonus)) {
            return false;
        }

        if (!$this->isBonusBatchEnabled($bonus)) {
            return false;
        }

        return true;
	}

    private function didUserAlreadyActivateWelcomeBonusTrophy(int $user_id): bool
    {
        $user = cu($user_id);
        if (empty($user)) {
            return false;
        }

        return phive('SQL')->sh($user_id)->getValue("SELECT id FROM welcome_bonus_trophies WHERE user_id = $user_id");
    }

	/**
	 * Determines if a bonus should be automatically activated based on the provided bonus entry.
	 *
	 * @param array $bonus An associative array containing details about the bonus.
	 * @return bool Returns true if the bonus awards should be awarded, false otherwise.
	 */
	private function isBonusBatchEnabled(array $bonus): bool
	{
		return !empty($bonus['auto_activate_bonus_id']) &&
            !empty($bonus['auto_activate_bonus_day']) &&
			!empty($bonus['auto_activate_bonus_period']) &&
			!empty($bonus['auto_activate_bonus_send_out_time']);
	}

	/**
	 * Creates auto activation entries for a given bonus.
	 *
	 * @param array $welcome_bonus_entry The bonus entry data.
	 * @return void
	 */
	private function createWelcomeBonusWeeklyAwardEntries(array $welcome_bonus_entry): void
	{
		$this->insertWelcomeBonusTrophyEntry($welcome_bonus_entry, 'awarded', 0);
		$this->insertSubsequentWelcomeBonusTrophyEntries($welcome_bonus_entry);
	}

	/**
	 * Creates future weeks auto activation entries for a given bonus.
	 *
	 * @param array $welcome_bonus_entry The bonus entry data.
	 * @return void
	 */
    private function insertSubsequentWelcomeBonusTrophyEntries(array $welcome_bonus_entry): void
    {
        $date = new DateTime();
        $time = $welcome_bonus_entry['auto_activate_bonus_send_out_time'];

        $nextDate = $this->getNextOccurrenceDateTime($date, $welcome_bonus_entry['auto_activate_bonus_day'], $time);
        $formattedDate = $nextDate->format('Y-m-d H:i:s');

        $this->insertWelcomeBonusTrophyEntry($welcome_bonus_entry, 'pending', 1, $formattedDate);

        for ($i = 1; $i <= $welcome_bonus_entry['auto_activate_bonus_period'] - 1; $i++) {
            $formattedDate = $nextDate->copy()->addWeeks($i)->format('Y-m-d H:i:s');
            $this->insertWelcomeBonusTrophyEntry($welcome_bonus_entry, 'pending', $i, $formattedDate);
        }
    }

	/**
	 * Inserts a welcome bonus trophy entry for a user.
	 *
	 * @param array $welcome_bonus_entry The bonus entry.
	 * @param string $status The status of the entry.
	 * @param int $order In which order awards are given.
	 * @param string $execute_at Time when the award will be given.
	 *
	 * @return void
	 */
	private function insertWelcomeBonusTrophyEntry(array $welcome_bonus_entry, string $status, int $order, string $execute_at = ''): void
	{
		$data = [
			'user_id' => $welcome_bonus_entry['user_id'],
			'welcome_bonus_entry_id' => $welcome_bonus_entry['id'],
			'status' => $status,
			'step' => $order,
			'execute_at' => $execute_at ?: date('Y-m-d H:i:s')
		];

		phive('SQL')->sh($welcome_bonus_entry['user_id'])->insertArray('welcome_bonus_trophies', $data);
	}

	/**
	 * Calculates the date for the next welcome bonus award to be assigned.
	 *
	 * @param DateTime $date The current date and time.
	 * @param int $dayNumber The day of the week as an integer (0 for Sunday, 1 for Monday, ..., 6 for Saturday).
	 * @param string $time The time of the day in 'H:i:s' format.
	 *
	 * @return string The next occurrence date and time in 'Y-m-d H:i:s' format.
	 */
    private function getNextOccurrenceDateTime(DateTime $date, int $dayNumber, string $time): DateTime
    {
        // Ensure the week is considered to start on Sunday
        Carbon::setWeekStartsAt(Carbon::SUNDAY);

        // Get next week's start (i.e. next week's Sunday)
        $nextWeekStart = Carbon::now()->addWeek()->startOfWeek();

        // Add the given day number to get the target day.
        // For dayNumber = 0, this remains Sunday; for dayNumber = 1, it's Monday, and so on.
        $targetDate = $nextWeekStart->copy()->addDays($dayNumber);

        // Set the time on the target date using the provided time string
        list($hours, $minutes, $seconds) = array_map('intval', explode(':', $time));
        $targetDate->setTime($hours, $minutes, $seconds);

        return $targetDate;
    }

	/**
	 * Grants the welcome bonus weekly awards to eligible users.
	 *
	 * This method is responsible for distributing the weekly awards
	 * associated with the welcome bonus to users who qualify.
	 *
	 * @return void
	 */
	public function grantWelcomeBonusWeeklyAwards(): void
	{
        if (!$this->getSetting('is_batched_welcome_bonus_enabled')) {
            return;
		}

		$query      = "SELECT * FROM welcome_bonus_trophies WHERE status = 'pending' AND execute_at <= NOW()";
		$rewards    = phive('SQL')->shs()->loadArray($query);
		if (empty($rewards)) return;

		foreach ($rewards as $reward) {
			$user = cu($reward['user_id']);
			if (empty($user)) continue;

			if (!$this->isEligibleForNextWeeklyBonusBatch($reward, $user->getId())) {
				$this->forfeitFutureWelcomeBonusAwards($reward['welcome_bonus_entry_id'], $user->getId());
				continue;
			}

			// Assign the bonus with award to the user and update the auto activation entry status
			$entry = $this->getBonusEntry($reward['welcome_bonus_entry_id']);
			$query = "SELECT id FROM trophy_awards WHERE bonus_id = {$entry['auto_activate_bonus_id']} LIMIT 1";
			$award_id = phive("SQL")->getValue($query);

			if(!empty($award_id) && phive('Trophy')->giveAward($award_id, $user->getId())) {
				$query = "UPDATE welcome_bonus_trophies SET status = 'awarded' WHERE id = {$reward['id']}";
				phive("SQL")->sh($user)->query($query);

				// mark prev week award as completed
				$query = "UPDATE welcome_bonus_trophies SET status = 'completed' WHERE step = {$reward['step']} - 1 AND status IN ('awarded', 'active')";
				phive("SQL")->sh($user)->query($query);

                $this->sendWelcomeBonusWeeklyAwardNotification($user);
			}
		}
	}

	/**
	 * Handles the forfeiture of a welcome or freespin bonus associated with the welcome bonus.
	 *
	 * @param array $bonus_entry An associative array containing details of the bonus entry.
	 *
	 * @return void
	 */
	public function handleBonusForfeit(array $bonus_entry): void
	{
		if (!$this->getSetting('is_batched_welcome_bonus_enabled')) {
            return;
		}

        $entry_id = $bonus_entry['id'];

        $query = "SELECT *
        FROM welcome_bonus_trophies
        WHERE (welcome_bonus_entry_id = $entry_id OR bonus_entry_id = $entry_id)
          AND status NOT IN ('failed', 'completed')";

        $bonus_entries = phive('SQL')->sh($bonus_entry['user_id'])->loadArray($query);

        if (empty($bonus_entries)) {
            return;
        }

        // forfited welcome bonus
        if ($entry_id === $bonus_entries[0]['welcome_bonus_entry_id']) {

            $message = 'Failing bonus because welcome bonus was forfeited.';

            // fail welcome bonus
            $welcome_bonus_entry = $this->getBonusEntry($bonus_entries[0]['welcome_bonus_entry_id'], $bonus_entries[0]['user_id']);
            if ($welcome_bonus_entry['status'] === 'active') {
                $this->fail($welcome_bonus_entry, $message, $welcome_bonus_entry['user_id']);
            }

            foreach ($bonus_entries as $entry) {
                $this->fail($entry['bonus_entry_id'], $message, $entry['user_id']);
            }

            // expiring unactivated trophies
            $bonus = $this->getBonus($welcome_bonus_entry['bonus_id']);
            if (!empty($bonus['award_id'])) {
                $award_id = $bonus['award_id'];
                phive('SQL')->sh($welcome_bonus_entry['user_id'])->query("UPDATE trophy_award_ownership SET status = 3 WHERE award_id = $award_id AND status = 0");
            }

            $this->forfeitFutureWelcomeBonusAwards($bonus_entry['id'], $bonus_entry['user_id']);
        } else {
            // update status of the FS bonus batch
            phive('SQL')->sh($bonus_entry['user_id'])->query("UPDATE welcome_bonus_trophies SET status = 'failed' WHERE bonus_entry_id = {$bonus_entry['id']}");
        }
	}

	/**
	 * Forfeits the future welcome bonus awards for a specific user.
	 *
	 * @param int $entry_id
	 * @param int $user_id
	 * @return void
	 */
	function forfeitFutureWelcomeBonusAwards(int $entry_id, int $user_id): void
	{
		phive("SQL")->sh($user_id)->query("UPDATE welcome_bonus_trophies SET status = 'failed' WHERE user_id = $user_id AND welcome_bonus_entry_id = $entry_id AND status != 'completed'");
	}

	/**
	 * Checks if a user is eligible for the next weekly awards batch.
	 *
	 * @param array $reward
	 * @param int $user_id
	 * @return bool
	 */
	public function isEligibleForNextWeeklyBonusBatch(array $reward, int $user_id): bool
	{
		$welcome_bonus_entry = $this->getBonusEntry($reward['welcome_bonus_entry_id'], $user_id);
		if ($welcome_bonus_entry['status'] === 'failed') {
			return false;
		}

        $period = $this->getSetting('batched_welcome_bonus_period');
		if ($welcome_bonus_entry['status'] === 'pending' && $welcome_bonus_entry['created_at'] < date('Y-m-d H:i:s', strtotime($period))) {
			phive('Bonuses')->fail($welcome_bonus_entry, 'Have not activated bonus within a week', $user_id);
			return false;
		}

		return true;
	}

	/**
	 * Updates the welcome bonus entry if it exists and is in the awarded state.
	 *
	 * @param int $user_id The user ID.
	 * @param int $bonus_id The bonus ID.
	 * @param int $new_id The new bonus entry ID.
	 */
	private function updateWelcomeBonusEntry(int $user_id, int $bonus_id, int $new_id): void
	{
    	if (!$this->getSetting('is_batched_welcome_bonus_enabled')) {
            return;
		}

		$lic_bonus = lic('getFirstDepositBonus', [], cu($user_id));

        if (empty($lic_bonus)) {
            return;
        }

        $welcome_bonus_entry = $this->getBonusEntriesByUserAndBonusId($user_id, $lic_bonus['id'], 1);
        if (empty($welcome_bonus_entry)) {
            return;
        }

		if ($welcome_bonus_entry['auto_activate_bonus_id'] == $bonus_id && in_array($welcome_bonus_entry['status'], ['active', 'pending'])) {
			$query = "SELECT id FROM welcome_bonus_trophies WHERE user_id = $user_id AND welcome_bonus_entry_id = {$welcome_bonus_entry['id']} AND status = 'awarded' LIMIT 1";
			$welcome_bonuses_id = phive('SQL')->sh($user_id)->getValue($query);

			if ($welcome_bonuses_id) {
				$update = "UPDATE welcome_bonus_trophies SET status = 'active', bonus_entry_id = $new_id WHERE user_id = $user_id AND id = $welcome_bonuses_id";
				phive('SQL')->sh($user_id)->query($update);
			}
		}
	}

	/**
	 * Checking if the completed bonus is the last week FS bonus, closing welcome bonus as well if so.
     *
	 * @param int $user_id
	 * @param int $entry_id
	 * @return void
	 */
	private function completeWelcomeBonusTrophies(int $user_id, int $entry_id): void
	{
        if (!$this->getSetting('is_batched_welcome_bonus_enabled')) {
            return;
		}

		$sql = "SELECT welcome_bonus_entry_id
        FROM welcome_bonus_trophies
        WHERE bonus_entry_id = $entry_id
        AND step = (SELECT MAX(step)
        FROM welcome_bonus_trophies
        WHERE bonus_entry_id = $entry_id)";

		$welcome_bonus_entry_id = phive('SQL')->sh($user_id)->getValue($sql);
		if ($welcome_bonus_entry_id) {

			$welcome_bonus_entry = $this->getBonusEntry($welcome_bonus_entry_id, $user_id);
			if ($welcome_bonus_entry['status'] == 'active') {
				$this->close($welcome_bonus_entry_id, 'approved', [], $user_id);
				phive("SQL")->sh($user_id)->query("UPDATE welcome_bonus_trophies SET status = 'completed' WHERE user_id = $user_id AND bonus_entry_id = $entry_id");
			}
		}
	}


	/**
	 * Check if the user has already activated welcome bonus
     *
	 * @param array $welcome_bonus_entry
	 * @return void
	 */
    private function checkForActivatedWelcomeBonusTrophy(array $welcome_bonus_entry): void
    {
      	if (!$this->getSetting('is_batched_welcome_bonus_enabled')) {
            return;
		}

        $lic_bonus = lic('getFirstDepositBonus', [], cu($welcome_bonus_entry['user_id']));

        if (empty($lic_bonus)) {
            return;
        }

		if ($lic_bonus['id'] == $welcome_bonus_entry['bonus_id']) {

            $bonus_id = $welcome_bonus_entry['auto_activate_bonus_id'];
            $user_id = $welcome_bonus_entry['user_id'];

            $query = "SELECT id FROM bonus_entries WHERE user_id = $user_id AND bonus_id = $bonus_id LIMIT 1";
			$bonus_entry_id = phive('SQL')->sh($user_id)->getValue($query);

            $update = "UPDATE welcome_bonus_trophies
            SET status = 'active', bonus_entry_id = $bonus_entry_id
            WHERE user_id = $user_id AND step = 0 AND status = 'awarded'";
            phive('SQL')->sh($user_id)->query($update);
        }
    }

    /**
     * Send email & sms notification if the user allows for marketing material
     *
     * @param DBUser $user
     * @return void
     */
    private function sendWelcomeBonusWeeklyAwardNotification(DBUser $user)
    {
        $email_alias    = 'bonus.auto.payout';
        $sms_alias      = 'bonus.auto.payout.sms';
        $sms_replace    = [
            'user.username'     => $user->getUsername(),
            'user.firstname'    => $user->getFirstName(),
            'user.fullname'     => $user->getFullName()
        ];

        if (phive('DBUserHandler')->canSendTo($user, $email_alias, $sms_alias)) {
            phive("MailHandler2")->sendMail($email_alias, $user);

            if (phive()->moduleExists("Mosms")) {
                setCur($user);
                $msg = t2($sms_alias, $sms_replace, $user->getLang());
                if (phive('Mosms')->putInQ($user, $msg, false)) {
                    phive('UserHandler')->logAction($user->getId(), "Sent bonus auto payout sms", 'sms');
                }
            }
        }
    }

    public function getBonusEntriesByUserAndBonusId(int $user_id, int $bonus_id, ?int $limit = null)
    {
        $sql = "SELECT * FROM bonus_entries WHERE user_id = $user_id AND bonus_id = $bonus_id";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT $limit";
        }

        return phive('SQL')->sh($user_id)->loadAssoc($sql);
    }
}
