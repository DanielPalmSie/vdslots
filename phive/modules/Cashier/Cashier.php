<?php

use Videoslots\HistoryMessages\CashTransactionHistoryMessage;
use Videoslots\HistoryMessages\BonusHistoryMessage;
use Videoslots\HistoryMessages\BonusCancellationHistoryMessage;

require_once __DIR__ . '/../../api/PhModule.php';

/**
 * The basic Cashier class that is used for transactions, it is the base class that is powering both Casino
 * and Affiliate logic / sites.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_cash_transactions The wiki docs for the cash_transactions table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_deposits The wiki docs for the deposits table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_pending_withdrawals The wiki docs for the pending_withdrawals table.
 *
*/
class Cashier extends PhModule{

    const CASH_TRANSACTION_DEPOSIT = 3;
    const CASH_TRANSACTION_WITHDRAWAL = 8;
    const CASH_TRANSACTION_NORMALREFUND = 13;
    const CASH_TRANSACTION_UNDOWITHDRAWAL = 103;

    /**
     * Formats a number with two decimals regardless of separator character, attaches .00 to an int.
     *
     * - 4.295,45 -> 4295.45
     * - 4#295,45 -> 4295.45
     * - 4,295.45 -> 4295.45
     * - 4,295,45 -> 4295.45
     * - 4 295,45 -> 4295.45
     * - 4,295,455 -> 4295.46
     * - 4,295,455.045 -> 4295455.05
     * - 4,295,455.5 -> 4295455.50
     *
     * @param string $num The number to format.
     *
     * @return string The cleaned up number.
     */
    function cleanUpNumber($num){
        $num = trim($num);
        if(preg_match('|[^0-9]|', $num)){
            $arr   = preg_split('|[^0-9]|', $num);
            // We cut the decimals off if this part is longer than 2 chars.
            $cents = str_pad(substr(array_pop($arr), 0, 2), 2, 0);
            // No rounding as it won't add an extra 0 to a number like 7.5
            return (float)implode('', $arr).".$cents";
        }
        return (float)$num.'.00';
    }

    /**
     * Inserts a transaction into the cash_transactions table.
     *
     * @param mixed $uid User information.
     * @param int $amount Amount in cents.
     * @param int $type Transaction type.
     * @param string $descr Transaction description.
     * @param int $bid Optional bonus_types id that this transaction "belongs" to, ie the bonus that caused this transaction to happen.
     * @param string $stamp Optional timestamp, if left out NOW() will be used.
     * @param int $entry_id If we're connecting to a bonus (ie $bid is not empty) we typically pass in the bonus_entries id here.
     * @param int $parent_id The id of more data that exists for this transaction in the deposits table or the pending_withdrawals table,
     * depending on what type of transaction it is, it can also be a reference to a win.
     *
     * @return bool|int
     */
    function insertTransaction($uid, $amount, $type, $descr, $bid = 0, $stamp = '', $entry_id = 0, $parent_id = 0){
        $user = cu($uid);
        if(empty($user))
            return false;

		// Check for idempotency for Bonus Activation: avoid creating a duplicate record
        if ($desc == 'Bonus Activation') {
            $existingTransaction = phive('SQL')->sh($user)->loadAssoc(
                "SELECT id FROM cash_transactions WHERE user_id = $uid AND description = '$desc' AND bonus_id = {$bid} LIMIT 1"
            );

            if (!empty($existingTransaction)) {
                phive('Logger')->getLogger('casino')->debug('double-bonus-transactions insertTransaction', [
                    'user_id' => $uid,
                    'bonus_id' => $bid,
                    'desc' => $descr,
                    'params' => func_get_args(),
                    'time' => date('Y-m-d H:i:s')
                ]);
                
                return $existingTransaction['id']; // Return the existing transaction ID
            }
        }

        $ins = $this->prepareCashTransactionData($user, $amount, $type, $descr, $parent_id, null, $bid, $stamp, $entry_id);

        return phive('SQL')->sh($user)->insertArray('cash_transactions', $ins);
    }

    public function prepareCashTransactionData(
        DBUser $user,
        int    $amount,
        int    $type,
        string $descr,
        int    $parent_id = 0,
        ?int   $userBalance = null,
        int    $bid = 0,
        string $stamp = '',
        int    $entry_id = 0
    ): array
    {
        $transactionData = [
            'user_id' => $user->getId(),
            'bonus_id' => $bid,
            'entry_id' => (int)$entry_id,
            'amount' => $amount,
            'description' => $descr,
            'currency' => $user->getAttr('currency'),
            'transactiontype' => $type,
            'balance' => isset($userBalance) ? $userBalance : $user->getBalance(),
            'parent_id' => $parent_id,
            'session_id' => $user->getCurrentSession()['id']
        ];

        if (!empty($stamp)) {
            $transactionData['timestamp'] = $stamp;
        }

        return $transactionData;
    }

    /**
     * Logic run on a user in case a chargeback is made by that user.
     *
     * This method will:
     * 1. Block the user with 9 as the block reason.
     * 2. Check if the user actually have enough money in order for us to be able to claw back the money from the
     * casino account.
     * 3. If not we will book the missing amount as a transaction of type 87 and try and claw back whatever money is left on the account.
     * 4. Finally we deduct whatever we can from the user balance.
     * 5. Finally we potentially send an email notification to the P&F team and run Fr::onChargeBack()
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount that was charged back in cents.
     * @param string $descr Some comments / description related to the chargeback.
     * @param bool $send_mail Whether or not to send a notification mail to pre-configured email addresses in case this
     * logic is executed in an automatic fashion.
     * @param string $psp The Payment Service Provider.
     * @param bool $insufficient_balance_allowed If false, then the function will return false in case the user has not
     * enough balance, otherwise the chargback is allowed and withdraw will take place (and returns true).
     * @param int $user_block_status The user block reason status eg. 2 -> wrong country, 15 -> death. We can block
     * different users with different reasons in different psps. -1 -> no user block
     *
     * @return bool Returns false if there are insufficient balance in the account and chargeback is not allowed in that
     * case, otherwise returns true
     */
    function chargeback($user, $amount, $descr, $send_mail = false, $psp = '', $insufficient_balance_allowed = true, $user_block_status = 9){
        if ($user_block_status != DBUserHandler::USER_BLOCK_STATUS_NO_BLOCK) {
            phive('UserHandler')->addBlock($user, $user_block_status);
        }
        $cur_balance = $user->getBalance();
        $diff        = $cur_balance - $amount;
        //not enough cash on hand?
        if($diff < 0){
            if(!$insufficient_balance_allowed)
                return false;
            $withdraw_amount = $cur_balance;
            $this->insertTransaction($user, abs($diff), 87, "Cargeback overcharge difference.");
        }else{
            $withdraw_amount = $amount;
        }
        $this->withdrawFromUser($user, $withdraw_amount, $descr, 9, false);
        if($send_mail){
            $mh = phive('MailHandler2');
            $mh->mailLocal('Chargeback', $user->getUserName()." just issued a chargeback for $amount cents. Description: ".$descr, 'chargeback_mail');
        }
        phive('Cashier/Arf')->invoke('onChargeback', $user->getId(), $psp, $amount, $user->getCurrency());
        return true;
    }

    /**
     * Debit the player, this is just an alias of Cashier::depositToUser() but with the amount inverted.
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount in cents.
     * @param string $description Some comments / description for the transaction.
     * @param int $ttype Transaction type.
     * @param bool $go_below Whether ot not to allow the user balance to go below 0 or not, typically not.
     *
     * @return mixed The new transaction id in case of success, null or false otherwise.
     */
    public function withdrawFromUser($user, $amount, $description, $ttype = null, $go_below = true){
        return $this->depositToUser($user, -$amount, $description, $ttype, $go_below);
    }

    /**
     * This is just an alias of Cashier::transactUser() with an additional check that will abort if the amount is 0 / empty.
     *
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount in cents.
     * @param string $description Some comments / description for the transaction.
     * @param int $ttype Transaction type.
     * @param bool $go_below Whether ot not to allow the user balance to go below 0 or not, typically not.
     *
     * @return mixed The new transaction id in case of success, null or false otherwise.
     */
    public function depositToUser($user, $amount, $description, $ttype = null, $go_below = true){
        if(!empty($amount)){
            return $this->transactUser($user, $amount, $description, null, null, $ttype, $go_below);
        }else
            return false;
    }

    /**
     * Summary
     *
     * Description
     *
     * TODO henrik why not use insertTransaction here instead of the inserts array?
     * TODO henrik remove the multi currency conditional.
     * TODO henrik return false instead of implicit null and change doc blocks to reflect that.
     *
     * @uses User::incrementAttribute()
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount in cents.
     * @param string $description Some comments / description for the transaction.
     * @param $timestamp=null
     * @param $meta_info=null
     * @param int $trans_type Transaction type.
     * @param bool $go_below Whether ot not to allow the user balance to go below 0 or not, typically not.
     * @param int $bonus_id The bonus_types id for this transaction if applicable.
     * @param int $entry_id The bonus_entries id for this transaction if applicable.
     * @param int $parent_id The deposits or pending_withdrawals id for this transaction if applicable.
     *
     * @return mixed The new transaction id in case of success, null otherwise.
     */
    public function transactUser($user, $amount, $description, $timestamp = null, $meta_info = null, $trans_type = null, $go_below_zero = true, $bonus_id = '', $entry_id = 0, $parent_id = 0){
        $user = cu($user);
        if($user && is_numeric($amount)){

            $inserts = [
                "user_id"     => $user->getId(),
                "amount"      => $amount,
                "description" => $description,
                'parent_id'   => $parent_id,
                'session_id'  => $user->getCurrentSession()['id']
            ];

            if(phive("Currencer")->getSetting('multi_currency') == true)
                $inserts['currency'] = $user->getAttr('currency');

            if($timestamp)
                $inserts['timestamp'] = $timestamp;

            if($meta_info)
                $inserts['meta_info'] = $meta_info;

            $inserts['entry_id'] = $entry_id;

            if($bonus_id)
                $inserts['bonus_id'] = $bonus_id;

            if($trans_type)
                $inserts['transactiontype'] = $trans_type;

            if(!$go_below_zero && $amount < 0)
                $incr_with = max($amount, -$user->getBalance());
            else
                $incr_with = $amount;

            $cash_transaction = $this->getCashTransaction($parent_id, $trans_type, $user->getId());
            if (!empty($cash_transaction)) {
                $this->logCashTransaction($inserts, $cash_transaction);
                return null;
            }

            $res = $user->incrementAttribute("cash_balance", $incr_with);

            if($res) {
                $new_balance        = $user->getBalance();
                $inserts['balance'] = $new_balance;
                $new_id = phive("SQL")->sh($inserts, 'user_id', 'cash_transactions')->insertArray('cash_transactions', $inserts);
                realtimeStats()->onCashTransaction($user, $trans_type, $amount);

                $data = [
                    'user_id'          => (int) $user->getId(),
                    'transaction_id'   => (int) $new_id,
                    'amount'           => (int) $amount,
                    'currency'         => $user->getCurrency(),
                    'transaction_type' => (int) $trans_type,
                    'parent_id'        => (int) $parent_id,
                    'description'      => $description,
                    'event_timestamp'  => time(),
                ];
                try {
                    if (!$bonus_id) {// if it has a bonus_id, we come from creating a bonus
                        $report_type = 'cash_transaction';
                        $history_message = new CashTransactionHistoryMessage($data);
                    } else {
                        if ($amount >= 0) {
                            $report_type = 'bonus';
                            $history_message = new BonusHistoryMessage($data);
                        } else {
                            $report_type = 'bonus_cancellation';
                            $history_message = new BonusCancellationHistoryMessage($data);
                        }
                    }
                    /** @uses Licensed::addRecordToHistory() */
                    lic('addRecordToHistory', [
                        $report_type,
                        $history_message,
                    ],
                        $user
                    );
                } catch (InvalidMessageDataException $exception) {
                    phive('Logger')
                        ->getLogger('history_message')
                        ->error("Invalid message on Cashier", [
                            'report_type' => $report_type,
                            'args' => $data,
                            'validation_errors' => $exception->getErrors(),
                            'user_id' => $user->getId(),
                        ]);
                }
            }

            if(lic('hasBalanceTypeLimit', [], $user)) {
                rgLimits()->onBalanceChanged($user, $new_balance);
            }

            return $new_id;
        }
    }

    public function getCashTransaction(int $parent_id, int $type, int $user_id): array
    {
        if ($parent_id && in_array($type, [self::CASH_TRANSACTION_DEPOSIT, self::CASH_TRANSACTION_NORMALREFUND, self::CASH_TRANSACTION_WITHDRAWAL, self::CASH_TRANSACTION_UNDOWITHDRAWAL])) {
            $sql = phive('SQL')->sh($user_id, '', 'cash_transactions');
            $query = "SELECT * FROM cash_transactions WHERE user_id = $user_id AND transactiontype = $type AND parent_id = $parent_id ORDER BY id DESC LIMIT 0,1";
            $cash_transaction = $sql->loadAssoc($query);

            if (!empty($cash_transaction)) {
                return $cash_transaction;
            }
        }

        return [];
    }

    public function logCashTransaction(array $insert_data, array $cash_transaction): void
    {
        phive('Logger')->getLogger('payments')->info("CashTransaction Already Exists:", [
            'data' => $insert_data,
            'cash_transaction' => $cash_transaction,
            'backtrace' => array_merge([['file' => __FILE__, 'line' => __LINE__]], debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
        ]);
    }

    /*
  public function getTransactionsFromUser($user,$start = null,$limit = null,$order_by = null,$order = "DESC"){
      if($order_by === null)
          $order_by = "`timestamp`";
      if($start !== null && $limit !== null)
          $q_limit = " LIMIT ".(float)$start.",".(float)$limit;
      else if($limit !== null)
          $q_limit = " LIMIT ".(float)$limit;
      // This should be enough to catch any unwanted entries.
      $uid = $user->getId();
      $order = phive("SQL")->escapeAscDesc($order);
      $order_by = phive("SQL")->escapeOrderBy($order_by);
      $query = "SELECT * FROM cash_transactions WHERE `user_id` = $uid ORDER BY $order_by $order $q_limit";
      return phive("SQL")->sh($uid, '', 'cash_transactions')->loadArray($query);
  }
    */

    /**
     * Get distinct dep types from deposits.
     *
     * TOOD henrik add (int) transform on $user_id.
     *
     * @param int $user_id The user id.
     *
     * @return array The array of deposit types (ie PSP names).
     */
    function getDepositMethodsByUserId($user_id)
    {
        return phive('SQL')->sh($user_id)->loadArray(
            "SELECT DISTINCT dep_type AS dep_type
            FROM deposits
            WHERE user_id = $user_id"
        );
    }

    /**
     * Get distinct payment methods from pending_withdrawals.
     *
     * TOOD henrik add (int) transform on $user_id.
     *
     * @param int $user_id The user id.
     *
     * @return array The array of withdraw networks (ie PSP names), as opposed to SQL::getDepositMethodsByUserId()
     * this column stores the network, so if we have PSP X via PSP Network Y, then this method will return Ys.
     * This is due to the MTS routing which naturally is by network as the API calls / endpoints ofc are per network.
     */
    function getWithdrawalMethodsByUserId($user_id)
    {
        return phive('SQL')->sh($user_id)->loadArray(
            "SELECT DISTINCT payment_method AS payment_method
            FROM pending_withdrawals
            WHERE user_id = $user_id"
        );
    }

    /**
     * Gets a sum of deposits and a sum of withdrawals for a user and optionally with filtering on a PSP.
     *
     * TOOD henrik add (int) transform on $user_id.
     *
     * @param int $user_id The user id.
     * @param string $start_date The start timestamp or date.
     * @param string $end_date The end timestamp or date.
     * @param string $provider Optional PSP name.
     * @return array An array of length 2 with the two sums.
     */
    function getTransactionSumsByUserIdProvider($user_id, $start_date = '', $end_date = '', $provider = '')
    {
        $where_dates = $provider_deposits = $provider_withdrawals = '';
        if (!empty($provider)) {
            $provider_deposits = "AND dep_type = '$provider'";
            $provider_withdrawals = "AND payment_method = '$provider'";
        }
        if (!empty($start_date) && !empty($end_date)) {
            $where_dates = "AND timestamp BETWEEN '$start_date' AND '$end_date'";
            $where_dates_pw = "AND pending_withdrawals.timestamp BETWEEN '$start_date' AND '$end_date'";
        }
        return phive('SQL')->sh($user_id)->loadAssoc(
            "SELECT
                (SELECT SUM(amount)
                    FROM deposits
                    WHERE user_id = $user_id
                    $where_dates
                    $provider_deposits
                    AND status = 'approved') AS sum_deposits,
                (SELECT SUM(pending_withdrawals.amount) - SUM(IFNULL(ct.amount, 0))
                    FROM pending_withdrawals
                    LEFT JOIN cash_transactions ct ON ct.parent_id = pending_withdrawals.id AND ct.transactiontype = 103 AND ct.user_id = $user_id
                    WHERE pending_withdrawals.user_id = $user_id
                    $where_dates_pw
                    $provider_withdrawals
                    AND status = 'approved') AS sum_withdrawals"
        );
    }

    /**
     * Gets Worldpay customers with transactions for the last 30 days
     * and checks their PEP last settings update.
     *
     * @return array An array of user IDs and their last check dates.
     */
    public function getWorldpayUsersForPepVerification(): array
    {
        $last30Days = date('Y-m-d H:i:s', strtotime('-30 days'));

        // Query for Worldpay deposit transactions
        $worldpayDeposits = phive('SQL')->shs('merge', '', null, 'deposits')->loadArray(
            "SELECT d.user_id AS id, MAX(us.created_at) AS lastCheckDate
         FROM deposits d
         LEFT JOIN users_settings us
                   ON us.user_id = d.user_id
                   AND (us.setting = 'id3global_pep_res' OR us.setting = 'acuris_pep_res')
         WHERE d.dep_type = 'worldpay'
           AND d.timestamp >= '$last30Days'
           AND d.status = 'approved'
         GROUP BY d.user_id
         HAVING lastCheckDate IS NULL OR lastCheckDate < '$last30Days'
         "
        );

        // Query for Worldpay withdrawal transactions
        $worldpayWithdrawals = phive('SQL')->shs('merge', '', null, 'pending_withdrawals')->loadArray(
            "SELECT pw.user_id AS id, MAX(us.created_at) AS lastCheckDate
         FROM pending_withdrawals pw
         LEFT JOIN users_settings us
                   ON us.user_id = pw.user_id
                   AND (us.setting = 'id3global_pep_res' OR us.setting = 'acuris_pep_res')
         WHERE payment_method = 'worldpay'
         AND timestamp >= '$last30Days'
         AND status = 'approved'
         GROUP BY pw.user_id
         HAVING lastCheckDate IS NULL OR lastCheckDate < '$last30Days'
         "

        );

        // Combine and remove duplicates
        $transactions = array_unique(array_merge(
            array_column($worldpayDeposits, 'id'),
            array_column($worldpayWithdrawals, 'id')
        ));

        return $transactions;
    }


    /**
     * Returns opening balance for a specified date
     *
     * @param $user_id
     * @param $date
     * @return mixed
     */
    function getBalanceSumByDate($user_id, $date){
        $data = phive('SQL')->sh($user_id)->loadAssoc(
            "SELECT sum(cash_balance) + sum(bonus_balance) as balance FROM users_daily_balance_stats WHERE user_id='$user_id' AND date='$date'"
        );

        return $data['balance'];
    }


    /**
     * Retrieves all user fees for a specific period of a time
     *
     * @param $user_id
     * @param $start_date
     * @param $end_date
     * @return mixed
     */
    function getFeeSumByPeriod($user_id, $type = 'wager', $start_date, $end_date){
        if($type == 'wager'){
            $data = phive('SQL')->sh($user_id)->loadAssoc(
                "SELECT SUM(bets) as fee FROM users_daily_stats WHERE user_id='$user_id' AND date BETWEEN '$start_date' AND '$end_date';"
            );


        } elseif ($type == 'fee'){
            $data = phive('SQL')->sh($user_id)->loadAssoc(
                "SELECT SUM(op_fee) + SUM(transfer_fees) + SUM(jp_fee) + SUM(bank_fee) + SUM(real_aff_fee) as fee FROM users_daily_stats WHERE user_id='$user_id' AND date BETWEEN '$start_date' AND '$end_date';"
            );
        }

        return $data['fee'];
    }

    /**
     * Gets a cash_transaction for a specific user, note that the $where data in combination with the user id
     * needs to to match something unique as SQL::loadAssoc() only returns one row.
     *
     * @param DBUser $user The user object.
     * @param array $where The where info where each key corresponds to a table column and value the value in said column.
     *
     * @return array The transaction.
     */
    function getTransaction($user, $where = []){
        $where['user_id'] = $user->getId();
        return phive('SQL')->sh($where, 'user_id', 'cash_transactions')->loadAssoc('', 'cash_transactions', $where);
    }

    /**
     * Gets all withdrawals in a certain time period for a certain user.
     *
     * @param int $uid The user id of the person whose withdrawals we want.
     * @param string $sdate The start dater / stamp.
     * @param string $status Optional statuses the withdrawals must be in.
     * @param string $edate Optional end date / stamp, if left out NOW will be used.
     * @param string $stamp_col The pending_withdrawals table contains several timestamp columns, this argument indicates which one is to be used.
     * @param string $select Which columns to select, defaults to *.
     *
     * @return array The withdrawals.
     */
    function getWithdrawalsInPeriod($uid, $sdate, $status = "IN('approved', 'pending')", $edate = '', $stamp_col = 'timestamp', $select = '*'){
        $edate = empty($edate) ? phive()->hisNow() : $edate;
        $str = "SELECT {$select} FROM pending_withdrawals WHERE user_id = $uid AND `$stamp_col` >= '$sdate' AND `$stamp_col` <= '$edate' AND status $status ORDER BY `$stamp_col`";
        return phive("SQL")->sh($uid, '', 'pending_withdrawals')->loadArray($str);
    }

    /**
     * Gets the row count of withdrawals.
     *
     * TODO henrik, refactor, call it getTotalPendingsCount.
     *
     * @param string $status The status to filter on (optional).
     * @param string $method The PSP network to filter on (optional).
     *
     * @return int The withdrawal count.
     */
    public function getTotalPendings($status = null,$method = null){
        $where = " WHERE 1 ";
        if($status !== null)
            $where.= " AND status = ".phive("SQL")->escape($status);
        if($method !== null)
            $where.=" AND payment_method = ".phive("SQL")->escape($method);
        $res = phive("SQL")->shs('merge', '', null, 'pending_withdrawals')->loadArray("SELECT COUNT(*) FROM pending_withdrawals $where");
        return array_sum(phive()->flatten($res));
    }

    /**
     * Gets the latest approved withdrawal.
     *
     * @param string $method The PSP network, if null is passed in here then that column will ge ignored.
     * @param int $user_id The user id, if null all nodes will be queried for the latest withdrawal.
     *
     * @return array The latest withdrawal.
     */
    function getLastPending($method, $user_id){
        $lpends = $this->getPendings(0, 1, 'timestamp', 'DESC', 'approved', $method, $user_id);
        return $lpends[0];
    }

    /**
     * Fetches withdrawals count for a user with multiple options for filtering etc.
     *
     * @param int $user_id The user id.
     * @param string $where_status Optional status filtering.
     * @param string $limit Optional limit.
     * @param string $type Optional PSP network.
     * @param string $where_extra Optional free from SQL for more WHERe filtering.
     * @param bool $get_approver If true we join on users to get info on who approved the withdrawal in question.
     * @param string $from Optional from / start date.
     * @param string $to Optional to / end date.
     * @param string $join Free form optional SQL JOIN statements.
     * @param string $extra_select Optional extra select, typically used when doing extra join in order to select the joined info.
     *
     * @return array An array of withdrawals.
     */
    public function getTotalPendingsCounts($user_id, $where_status = '', $limit = '', $type = '', $where_extra = '', $get_approver = false, $from = '', $to = '', $join = '', $extra_select = ''){
        $where_status = empty($where_status) ? '' : " AND `status` $where_status";
        $where_type = empty($type) ? '' : " AND payment_method = '$type'";
        $where_timestamp = empty($from) ? '' : " AND DATE(pending_withdrawals.timestamp) >= '$from'";
        $where_timestamp .= empty($to) ? '' : " AND DATE(pending_withdrawals.timestamp) <= '$to'";

        $approver_join = $get_approver ? " LEFT JOIN users u ON u.id = pending_withdrawals.approved_by" : "";

        $sql = "
        SELECT COUNT(*) AS total
        FROM pending_withdrawals
        $join
        $approver_join
        WHERE pending_withdrawals.user_id = $user_id
        $where_status
        $where_type
        $where_extra
        $where_timestamp
    ";

        $res = phive('SQL')->sh($user_id, '', 'pending_withdrawals')->loadArray($sql);
        return array_sum(phive()->flatten($res));
    }

    /**
     * Gets withdrawals with a slew of potential options for filtering and ordering.
     *
     * @param int $start The LIMIT offset.
     * @param int $limit The LIMIT count.
     * @param string $order_by Which column to order by.
     * @param string $order DESC or ASC.
     * @param string $status The status to filter on.
     * @param string $method The PSP network to filter on.
     * @param int $user_id The user id.
     *
     * @return array The withdrawals.
     */
    public function getPendings($start = null, $limit = null, $order_by="status", $order = "DESC", $status = null, $method = null, $user_id = null){
        $where = " WHERE 1 ";
        if($status !== null)
            $where .= " AND status = ".phive("SQL")->escape($status);

        if($method !== null)
            $where .=" AND payment_method = ".phive("SQL")->escape($method);

        if($user_id !== null)
            $where .=" AND user_id = ".(int)$user_id;

        if($start !== null && $limit !== null)
            $q_limit = " LIMIT ".(int)$start .",".(int)$limit;
        else if($limit !== null)
            $q_limit = " LIMIT ".(int)$limit;

        $pendings = $this->getSetting("db_pendings");
        $users    = phive("UserHandler")->getSetting("db_users");
        $q_order  = " ORDER BY ".phive("SQL")->escapeOrderBy($pendings.".".$order_by)." ".phive("SQL")->escapeAscDesc($order);
        $query    = "SELECT pending_withdrawals.*, users.username, users.firstname, users.lastname, users.country FROM $pendings INNER JOIN $users ON $pendings.user_id = $users.id $where $q_order $q_limit";
        if(!empty($user_id))
            return phive('SQL')->sh($user_id, '', 'pending_withdrawals')->loadArray($query);
        return phive("SQL")->shs('merge', $order_by, $order, 'pending_withdrawals')->loadArray($query);
    }

    /**
     * Gets a withdrawal by the primary key (id).
     *
     * @param int $pend_id The id.
     * @param int $user_id As the primary key is unique over all nodes the user id is only used in order to select the correct node.
     *
     * @return array The withdrawal.
     */
    public function getPending($pend_id, $user_id = null){
        $str = "SELECT * FROM pending_withdrawals WHERE id = ".(int)$pend_id;
        if(!empty($user_id)){
            return phive("SQL")->sh($user_id)->loadAssoc($str);
        }
        return phive("SQL")->shs('merge')->loadAssoc($str);
    }

    /**
     * Fetches withdrawals for a user with multiple options for filtering etc.
     *
     * @param int $user_id The user id.
     * @param string $where_status Optional status filtering.
     * @param string $limit Optional limit.
     * @param string $type Optional PSP network.
     * @param string $where_extra Optional free from SQL for more WHERe filtering.
     * @param bool $get_approver If true we join on users to get info on who approved the withdrawal in question.
     * @param string $from Optional from / start date.
     * @param string $to Optional to / end date.
     * @param string $join Free form optional SQL JOIN statements.
     * @param string $extra_select Optional extra select, typically used when doing extra join in order to select the joined info.
     *
     * @return array An array of withdrawals.
     */
    function getPendingsUser($user_id, $where_status = '', $limit = '', $type = '', $where_extra = '', $get_approver = false, $from = '', $to = '', $join = '', $extra_select = ''){
        $approver_field   = $get_approver ? ", u.username AS approver" : "";
        $approver_join    = $get_approver ? " LEFT JOIN users u ON u.id = pending_withdrawals.approved_by " : "";
        $where_status     = empty($where_status) ? '' : " AND `status` $where_status";
        $where_type       = empty($type) ? '' : " AND payment_method = '$type' ";
        $where_timestamp  = empty($from) ? '' : " AND DATE(pending_withdrawals.timestamp) >= '$from' ";
        $where_timestamp .= empty($to) ? '' : " AND DATE(pending_withdrawals.timestamp) <= '$to' ";
        $sql              = "SELECT pending_withdrawals.* $extra_select $approver_field FROM pending_withdrawals $join $approver_join WHERE pending_withdrawals.user_id = $user_id $where_status $where_type $where_extra $where_timestamp ORDER BY timestamp DESC $limit";
        return phive('SQL')->sh($user_id, '', 'pending_withdrawals')->loadArray($sql);
    }

    /**
     * Gets a list of withdrawals to undo, not the join with cash_transactions on transaction type 103 and the ct.id IS NULL filter
     * this makes sure the user can't undo a withdrawal more than once.
     *
     * TODO henrik remove the ; from the SQL.
     *
     * @param int $user_id The user id.
     * @param string $type PSP network.
     * @param int $limit The LIMIT.
     *
     * @return array The withdrawals that can be undone.
     */
    public function getWithdrawalsForUndo($user_id, $type, $limit)
    {
        $query = "SELECT p.* FROM pending_withdrawals p
                    LEFT JOIN cash_transactions ct on p.id = ct.parent_id AND ct.transactiontype = 103 AND ct.user_id = {$user_id}
                    WHERE p.user_id = {$user_id} AND p.payment_method = '{$type}' AND p.approved_at > NOW() - INTERVAL 60 DAY AND ct.id IS NULL
                        AND p.status = 'approved'
                    ORDER BY p.approved_at DESC
                    LIMIT {$limit};";

        return phive('SQL')->sh($user_id, '', 'pending_withdrawals')->loadArray($query);
    }

    public function isManualPendingWithdrawal(array $pendingWithdrawal): bool
    {
        return $pendingWithdrawal['user_id'] !== $pendingWithdrawal['created_by'];
    }

    /**
     * This is the basic logic that runs anytime a P&F agent approves a withdrawal.
     *
     * This method first logs the action in the approver's action log, and then the IP of the approver in the approver's ip log.
     * Finally it updates the status on the withdrawal. The real bank fee / cost can also be overridden.
     *
     * @param int $pend_id The id of the withdrawal.
     * @param int $approver_id Approver id override, if left out the currently logged in user will be the approver.
     *
     * @return null
     */
    public function approvePending($pend_id, $approver_id = ''){
        $pend_id = (int)$pend_id;
        $pend = $this->getPending($pend_id);
        if(in_array($pend['status'], ['disapproved', 'approved']))
            return 'nok';
        $user = cu($approver_id);
        $target = cu($pend['user_id']);
        if(empty($user))
            $user = cu('system');
        if (empty($user)) {
            phive('Logger')
                ->getLogger('payments')
                ->warning('System user not found!', [
                    'comment' => 'Please run "./console seeder:up 20240605081429" in admin2 to fix this issue!'
                ]);

            return false;
        }

        $updates = ["status" => "approved", "approved_by" => $user->getId(), "approved_at" => phive()->hisNow()];
        $descr = "approved withdrawal by {$pend['payment_method']} of {$pend['amount']} with internal id of $pend_id for user {$target->getUsername()}";
        phive('UserHandler')->logAction($pend['user_id'], $descr, 'approved-withdrawal', true, $user);
        phive('UserHandler')->logIp($user, $pend['user_id'], 'pending_withdrawals', $user->getUsername().$descr, $pend_id);
        realtimeStats()->onWithdrawalApproval($pend['user_id'], $pend['amount'], $pend['currency']);
        if(!empty($_REQUEST['real_cost']))
            $updates['real_cost'] = $_REQUEST['real_cost'];
        phive("SQL")->sh($pend, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', $updates, "id = ".phive("SQL")->escape($pend_id));

        // report transaction to license
        phive('Cashier/CashierNotify')->reportTransaction('withdraw', $pend['amount'], $pend['payment_method'], $target);
    }

    public function withdrawalAttemptMonitoring(int $withdrawalId): void
    {
        $withdrawal = $this->getPending($withdrawalId);
        if (empty($withdrawal)) {
            return;
        }

        $this->commentAml52Payout($withdrawal);

        if ($withdrawal['status'] === 'approved') {
            $user = cu($withdrawal['user_id']);
            if(empty($user)){
                return;
            }

            (new Fraud())->clearFraudFlags($user);
        }
    }

    public function commentAml52Payout(array $payout, ?string $additionalInfo = null): void
    {
        if (!cu(DBUserHandler::SYSTEM_AML52_PAYOUT_USER)) {
            phive('Logger')
                ->getLogger('payments')
                ->warning('AML52 user not found!', [
                    'comment' => 'Please run "./console seeder:up 20240611080908" in admin2 to fix this issue!',
                ]);

            return;
        }

        if (!cu(DBUserHandler::SYSTEM_AML52_PAYOUT_USER) || (string) $payout['created_by'] !== (string) cu(DBUserHandler::SYSTEM_AML52_PAYOUT_USER)->getId()) {
            return;
        }

        $amount = number_format($payout['amount'] / 100, 2, '.', '');

        $comment = "AML 52 auto payout attempted via {$payout['payment_method']} method for {$amount} {$payout['currency']} - "
            . "Id: {$payout['id']}, "
            . "Status: {$payout['status']}, "
            . "Mts Id: " . ($payout['mts_id'] ?: 'No transaction present');

        if (!empty($payout['ref_code'])) {
            $comment .= ", Card: {$payout['ref_code']}";
        }

        if ($additionalInfo) {
            $comment .= ' - Additional Info: ' . $additionalInfo;
        }

        phive('SQL')->sh($payout['user_id'], '', 'users_comments')->insertArray('users_comments', array(
            'user_id' => $payout['user_id'],
            'comment' => $comment,
            'sticky'=> 0,
            'tag' => 'automatic-flags',
            'foreign_id' => 0,
            'foreign_id_name' => 'id'
        ));
    }

    /**
     * The basic logic that gets executed when an P&F agent disapproves a withdrawal.
     *
     * @param int|array $pend_id The withdrawal id.
     * @param bool $redeposit Whether ot not to credit the user back the money, typially yes as we debit the money when the withdrawal
     * row is created.
     * @param bool $send_email Whether or not to send an email to the user to notify that the withdrawal was disapproved.
     * @param int|string $actorId User who performed the action
     *
     * @return null
     */
    public function disapprovePending($pend_id, $redeposit = true, $send_email = false, $actorId = null){
        $pend_id = is_numeric($pend_id) ? (int)$pend_id : $pend_id['id'];
        $dclick_key = "pending-$pend_id-Cashier";
        dclickStart($dclick_key);
        $pend = $this->getPending($pend_id);
        if($pend['status'] == 'disapproved')
            return dclickEnd($dclick_key, true);

        $u           = cu($actorId);
        $target      = cu($pend['user_id']);
        $approved_by = is_object($u) ? $u->getId() : '';
        $updates     = ["status" => "disapproved", "approved_by" => $approved_by, "approved_at" => phive()->hisNow()];

        phive('UserHandler')->logAction($target, " disapproved withdrawal by {$pend['payment_method']} of {$pend['amount']} with internal id of $pend_id for user {$target->getUsername()}", 'disapproved-withdrawal', true, $u);

        phive("SQL")->sh($pend, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', $updates, "id = $pend_id");

        $pend = $this->getPending($pend_id);

        if(phive()->moduleExists("MailHandler2") && $send_email){
            $replacers 			= phive('MailHandler2')->getDefaultReplacers($target);
            $replacers["__METHOD__"] 	= ucfirst($pend['payment_method']);
            $replacers["__AMOUNT__"] 	= nfCents($pend['amount'], true);
            phive("MailHandler2")->sendMail('withdrawal.denied', $target, $replacers);
        }

        if(!empty($pend) && $redeposit){
            if($pend['status'] == 'disapproved'){
                $desc = strtr(phive("Localizer")->getString("cashier.pending_not_approved", $target->getAttribute("preferred_lang")), "", "");
                $this->depositToUser($target, $pend['amount'], $desc);
            }
        }

        if (!empty($pend['user_id']) && $pend['user_id'] == $approved_by) {
            phive()->pexec('Cashier/Arf', 'invoke', ['onDisapprovePending', $pend['user_id']]);
        }

        dclickEnd($dclick_key);
    }

  /*
     public function getTotalBalance(){
     $str = "SELECT SUM(cash_balance) AS amount FROM users_extended";
     return phive("SQL")->getValue($str);
     }
   */

    // TODO henrik remove this
    function cleanUpAccNum($acc_num){
        return trim(str_replace(array(' ', ',', '.', '-', '/'), '', $acc_num));
    }

  /**
   * Used to generate data for country selection drop downs.
   *
   * We display a special section at the top of the drop down with commonly selected countries.
   *
   * TODO henrik, remove FR and AU from the array, and remove the pass by reference too?
   *
   * @param array $countries Commonly just the whole bank_countries table as returned by SQL::loadArray().
   * @param array $rarr The array
   * @param boolean $formatted
   * @param array $datas list of additional properties to populate the data value in the returned array
   *
   * @return array The array of countries.
   */
    function displayBankCountries($countries, $returned_array = [], $formatted = true, $datas = [])
    {
        $display_common_countries = $this->getSetting('display_common_countries', true);

        foreach ($countries as $c) {
            if (phive('Localizer')->doExtraLocalization()) {
                $country_name = t("country.name.{$c['iso']}");
            } else {
                $country_name = "{$c['printable_name']} ( {$c['iso']} ) ";
            }
            if(empty($datas)) {
                $returned_array[ $c['iso'] ] = $country_name;
            } else {
                $returned_array[ $c['iso'] ] = ['name' => $country_name];
                foreach($datas as $data) {
                    $returned_array[ $c['iso'] ]['data'][$data] = $c[$data];
                }
            }
        }

        if ($display_common_countries && $formatted) {
            $common_countries = [];
            foreach (['SE', 'FI', 'DE', 'FR', 'NL', 'NO', 'PL', 'IT', 'AU', 'CA', 'GB'] as $ciso) {
                if(empty($datas)) {
                    $common_countries[$ciso] = $c[$ciso]['printable_name'] . " ( {$ciso} ) ";
                } else {
                    $common_countries[$ciso] = ['name' => "{$c['printable_name']} ( {$c['iso']} ) "];
                    foreach($datas as $data) {
                        $common_countries[$ciso]['data'] = [
                            $data => $c[$ciso][$data]
                        ];
                    }
                }
            }

            return array_merge(
                ['common.countries' => ['type' => 'optgroup']],
                $common_countries,
                ['uncommon.countries' => ['type' => 'optgroup']],
                $returned_array);
        }

        return $returned_array;
    }

    /**
     * This method basically gets the full bank_countries table.
     *
     * @param string $first_lbl If $as_arr is set to true this will control what we put in the first element of the
     * returned optimized-for-drop-down array.
     * @param bool $as_arr If true we simply return the table as is, if false we return an custom array for use with a select drop down.
     *
     * @return array The result array.
     */
    function getBankCountries($first_lbl = 'Choose Country', $as_arr = false){
        $countries = phive('SQL')->readOnly()->loadArray("SELECT * FROM bank_countries ORDER BY printable_name");

        if($as_arr){
            return $countries;
        }

        $rarr = !empty($first_lbl) ? ['choose country' => $first_lbl] : [];

        return $this->displayBankCountries($countries, $rarr);
    }

    /**
     * A simple wrapper to get calling code with the ISO2
     *
     * @param string $iso The ISO2 code.
     *
     * @return int The calling code.
     */
    function phoneFromIso($iso){
        return phive('SQL')->getValue("SELECT calling_code FROM bank_countries WHERE iso = '$iso'");
    }


    // TODO henrik remove this
    function cleanUpField($val, $limit = 34){
        if(empty($val))
            return " ";
        return substr(trim($val), 0, 34);
    }

  /*
     NOK         / 42849026 / LU31 2294 2849 0260 0000
     EUR        /  42849 051 /  LU03 2294 2849 0510 0000
     GBP         / 42849 014 /   LU91 2294 2849 0140 0000
     CAD         / 42849 030 /   LU11 2294 2849 0300 0000
     AUD         / 42849 048 /   LU18 2294 2849 0480 0000
     USD         / 42849 010 /   LU14 2294 2849 0100 0000
     SEK          / 42849 024 /   LU41 2294 2849 0240 0000
   */
  public function getShbFile($sdate, $edate)
    {
        echo "Is this used?";
        return false;

      $id = uniqid("", true);

      $body = '';

      $header = '<?xml version="1.0" encoding="UTF-8"?>
        <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <CstmrCdtTrfInitn>
                <GrpHdr>
                    <MsgId>'.$id.'</MsgId>
                    <CreDtTm>'.date("c").'</CreDtTm>
                    <NbOfTxs>%transactions_cnt%</NbOfTxs>
                    <InitgPty>
                        <Id>
                            <OrgId>
                                <Othr>
                                    <Id>Initiating Party Id</Id>
                                    <SchmeNm>
                                        <Cd>BANK</Cd>
                                    </SchmeNm>
                                </Othr>
                            </OrgId>
                        </Id>
                    </InitgPty>
                </GrpHdr>
                ';

    $pay_date = date('ymd', strtotime("+1 day"));
    $nl = "\r\n";
    $str = 	":20:DAT$pay_date".$nl.
            ":50H:/".$this->getSetting('shb_acc_num').$nl.
            $this->getSetting('shb_cust_ident').$nl.
            ":52A:".$this->getSetting('shb_bic').$nl.
            ":30:$pay_date".$nl;

    echo '<br><br>';

    $cnt = 0;

    foreach ($this->getPendingsBetween($sdate, $edate, 'pending') as $t)
    {
        $amount = number_format($t['amount'] / 100, 2, '.', '');

        $iban = $this->cleanUpAccNum($t['iban']);
        $acc_num = $this->cleanUpAccNum($t['bank_account_number']);
        $swift = strtoupper($this->cleanUpAccNum($t['swift_bic']));

        $bank_rec = $this->cleanUpField($t['bank_receiver']);
        $bank_adr = trim($this->cleanUpField($t['bank_address']));
        $bank_ctr = $this->cleanUpField($t['bank_country']);
        $bank_city 	= empty($t['bank_city']) ? '' : $this->cleanUpField($t['bank_city']);

        if(strlen($acc_num) < strlen($iban))
            $acc_num = $iban;

        if(empty($iban) && $t['currency'] == 'EUR')
            echo "IBAN is needed for withdrawal with pending id: {$t['id']}. So not adding it to file.<br>";
        else if(empty($acc_num))
            echo "An IBAN or account number is needed for: {$t['id']}. So not adding it to file.<br>";
        else if(!in_array(strlen($swift), array(8, 11)))
            echo "Wrong Swift/BIC for: {$t['id']}. So not adding it to file.<br>";
        else {
        /*$bank_city 	= empty($t->bank_city) ? '' : $this->cleanUpField($t->bank_city);
        $str .= ":21:PWID{$t->id}$nl".
                ":32B:{$t->currency}{$amount}$nl".
                ":57A:".$swift.$nl.
                ":59:/$acc_num".$nl.$bank_rec.$nl.$bank_adr.$nl.$bank_city." ".$bank_ctr.$nl.
                ":70:PWID{$t->id}$nl".
                ":77B:/382/{$t->country_code}$nl".
                ":71A:SHA$nl";
        */

          if (strlen($acc_num) < strlen($iban)) {
              $acc = '
                <IBAN>'.$iban.'</IBAN>
            ';
          }
          else {
              $acc = '
                <Othr>
                    <Id>'.$acc_num.'</Id>
                    <SchmeNm>
                        <Cd>BBAN</Cd>
                    </SchmeNm>
                </Othr>
                ';
          }

          $cnt++;

          //$currency = 'EUR';
          //$iban = 'LU032294284905100000';
          $bic = 'HANDSESS';

          $ibans = [
             'NOK' => 'LU312294284902600000',
             'EUR' => 'LU032294284905100000',
             'GBP' => 'LU912294284901400000',
             'CAD' => 'LU112294284903000000',
             'AUD' => 'LU182294284904800000',
             'USD' => 'LU142294284901000000',
             'SEK' => 'LU412294284902400000'
          ];

          $body .= '
                <PmtInf>
                    <PmtInfId>'.$t['id'].'</PmtInfId>
                    <PmtMtd>TRF</PmtMtd>
                    <PmtTpInf>
                        <SvcLvl>
                            <Cd>NURG</Cd>
                        </SvcLvl>
                        <CtgyPurp>
                            <Cd>SUPP</Cd>
                        </CtgyPurp>
                    </PmtTpInf>
                    <ReqdExctnDt>'.date('Y-m-d').'</ReqdExctnDt>
                    <Dbtr>
                        <Id>
                            <OrgId>
                                <BICOrBEI>'.$bic.'</BICOrBEI>
                                <Othr>
                                    <Id>'.$ibans[$t['currency']].'</Id>
                                    <SchmeNm>
                                        <Cd>BANK</Cd>
                                    </SchmeNm>
                                </Othr>
                            </OrgId>
                        </Id>
                    </Dbtr>
                    <DbtrAcct>
                        <Id>
                            <IBAN>'.$ibans[$t['currency']].'</IBAN>
                        </Id>
                    </DbtrAcct>
                    <DbtrAgt>
                        <FinInstnId>
                            <Othr>
                                <Id>NOTPROVIDED</Id>
                            </Othr>
                        </FinInstnId>
                    </DbtrAgt>
                    <CdtTrfTxInf>
                        <PmtId>
                            <EndToEndId>'.$t['id'].'</EndToEndId>
                        </PmtId>
                        <Amt>
                            <InstdAmt Ccy="'.$t['currency'].'">'.$amount.'</InstdAmt>
                        </Amt>
                        <Cdtr>
                            <Nm>'.$bank_rec.'</Nm>
                            <PstlAdr>
                                <TwnNm>'.$bank_city.'</TwnNm>
                                <Ctry>'.$bank_ctr.'</Ctry>
                                <AdrLine>'.(!empty($bank_adr) ? $bank_adr : 'Empty').'</AdrLine>
                            </PstlAdr>
                        </Cdtr>
                        <CdtrAcct>
                            <Id>'.$acc.'</Id>
                        </CdtrAcct>
                    </CdtTrfTxInf>
                </PmtInf>';

        echo "Id: {$t['id']} OK.<br>";
      }
    }

      $footer = "\n</CstmrCdtTrfInitn>\n</Document>";

      $header = str_replace('%transactions_cnt%', $cnt, $header);
      $str = $header.$body.$footer;


    //phive('Filer')->downloadStr($str, 'text', 'bank_transfer_'.date('Y-m-d').'.txt');

      //echo $str;

      //echo getcwd();

    $file_name = 'file_uploads/'.uniqid().'.xml';       // TODO: image_service

      //echo $file_name;

    file_put_contents($file_name, $str);

    echo '<br><br><a href="/'.$file_name.'" target="_blank" rel="noopener noreferrer">Download</a> <strong><a href="?delete='.$file_name.'">Delete</a> Important for security reasons!</strong><br><br>';

  }

  // TODO henrik remove this
  public function getPendingsBetween($sdate, $edate, $status = 'approved', $method = 'bank'){
    return phive('SQL')->shs('merge', '', null, 'pending_withdrawals')->loadArray(
      "SELECT * FROM pending_withdrawals
      WHERE `timestamp` >= '$sdate'
      AND `timestamp` <= '$edate'
      AND payment_method = '$method'
      AND status = '$status'");
  }

    /**
     * Filter non-zero and non-empty values from the array.
     *
     * @param array $array The input array to filter.
     *
     * @return array The filtered array.
     */
    public function filterNonZeroNonEmptyArray(array $array): array {
        $isAssociative = array_keys($array) !== range(0, count($array) - 1);

        foreach ($array as $key => $value) {
            if (($isAssociative && ($key === 0 || $key === '')) || (!$isAssociative && ($value == 0 || $value == ''))) {
                unset($array[$key]);
            }
        }

        return $isAssociative ? $array : array_values($array);
    }

    /**
     * Get PSP names (deposits.dep_type) that user had previously deposited with as array.
     */
    function getDepositPspsByUserId(int $userId): array
    {
        $psps = phive('SQL')->sh($userId)->loadArray(
            "SELECT dep_type, MAX(timestamp) as max_timestamp_by_supplier
             FROM deposits
             WHERE user_id = $userId
             GROUP BY dep_type
             ORDER BY max_timestamp_by_supplier DESC"
        );

        return array_column($psps, 'dep_type');
    }
}
