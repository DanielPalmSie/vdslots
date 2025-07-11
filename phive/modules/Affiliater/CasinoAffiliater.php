<?php
 require_once __DIR__ . '/Affiliater.php';

class CasinoAffiliater extends Affiliater {

  function phAliases () { return array( 'Affiliater' ); }

    function getExtProfits($date, $update = true){
        $arr = [
            'key'    => $this->getSetting('pr_key'),
            'action' => 'get_profits',
            'params' => [
                'sdate' => $date,
                'edate' => $date,
                'cols'  => 'user_id, real_aff_fee, cpa_fee',
                'table' => 'users_daily_stats'
            ]
        ];
        $res = json_decode(phive()->post($this->getSetting('pr_url'), json_encode($arr)), true);
        if(!$update)
            return $res;
        $sql = phive('SQL');
        $c   = phive('Cashier');
        $date = phive('SQL')->escape($date,false);
        foreach($res as $r){
            //TODO this doesn't seem to work, we don't get any real aff fee saved?
            //the real task is anyway to get the fee from what was actually paid
            $total_fee           = $r['real_aff_fee'] - $r['cpa_fee'];
            $uds                 = $sql->loadAssoc('', 'users_daily_stats', ['date' => $date, 'user_id' => intval($r['user_id'])], true);
            //$site_rev            = $c->calcSiteRev($uds);
            $uds['site_prof']    = $uds['site_rev'] - $total_fee;
            $uds['real_aff_fee'] = $total_fee;
            $sql->save('users_daily_stats', $uds);
            //$site_prof = $r['before_deal'] - $total_fee;
            //$sql->updateArray('users_daily_stats', ['real_aff_fee' => $total_fee, 'site_prof' => $site_prof], ['date' => $date, 'user_id' => $r['user_id']]);
        }
        $sql->query("UPDATE users_daily_stats SET site_prof = site_rev WHERE real_aff_fee = 0 AND `date` = '$date'");
    }
    
  function getAffiliateFromUser ( $user_id ) {
    $user_id = intval($user_id);
    return phive( 'SQL' )->loadAssoc( "SELECT * FROM users WHERE id = (SELECT affe_id FROM users WHERE id = $user_id)" );
  }

  function pixelUrlFromUser ( $user_id ) {
    if ( !empty( $user_id ) )
      $aff = $this->getAffiliateFromUser( $user_id );
    else
      $aff['affe_id'] = $this->getAffIdByBonusCode( phive('Bonuses')->getBonusCode() );
    $setting = phive( "UserHandler" )->getRawSetting( $aff['affe_id'], 'pixel_url' );
    return $setting['value'];
  }

  function getAffiliateUserFromUser ( $user_id ) {
    $rel = $this->getAffiliateFromUser( $user_id );
    return phive( 'UserHandler' )->getUser( $rel['affe_id'] );
  }

  function getRateTable ( $key = '', $default = false ) {
    if ( !$default ) {
      if ( empty( $key ) )
        return 'affiliate_rates';
      $map = array( 'poker' => 'affiliate_poker_rates', 'sub' => 'sub_affiliate_poker_rates' );

      return $map[ $key ];
    } else {
      if ( empty( $key ) )
        return 'affiliate_default_rates';
      $map = array( 'poker' => 'affiliate_default_poker_rates', 'sub' => 'sub_affiliate_default_poker_rates' );

      return $map[ $key ];
    }
  }

  function getRates ( $aff_id, $poker = '' ) {
    $aff_id = intval($aff_id);
    return phive( 'SQL' )->loadArray( "SELECT * FROM {$this->getRateTable($poker)} WHERE affe_id = $aff_id ORDER BY start_amount" );
  }

  function getDefaultRates ( $poker = '' ) {

    return phive( 'SQL' )->loadArray( "SELECT * FROM {$this->getRateTable($poker, true)} ORDER BY start_amount" );
  }

  function insertRate ( $aff_id, $rate, $start_amount, $poker = '' ) {

    return phive( 'SQL' )->insertArray( $this->getRateTable( $poker ), array( 'affe_id'      => $aff_id,
                                                                              'rate'         => $rate,
                                                                              'start_amount' => $start_amount
    ) );
  }

  function changeRate ( $rid, $rate, $amount, $poker = '' ) {

    return phive( 'SQL' )->updateArray( $this->getRateTable( $poker ),
                                        array( 'rate' => $rate, 'start_amount' => $amount ), array( 'id' => $rid ) );
  }

  function deleteRate ( $rate_id, $poker = '' ) {

    phive( "SQL" )->query( "DELETE FROM {$this->getRateTable($poker)} WHERE id = " .
                           phive( "SQL" )->escape( $rate_id ) );
  }

  function changeInsertRates ( $p, $aff_id, $poker = '' ) {

    foreach ( $p['start_amount'] as $key => $amount ) {
      if ( empty( $amount ) && empty( $p['rate'][ $key ] ) ) {
        if ( !empty( $p['rate_id'][ $key ] ) )
          $this->deleteRate( $p['rate_id'][ $key ], $poker );
      } else {
        if ( !empty( $p['rate_id'][ $key ] ) )
          $this->changeRate( $p['rate_id'][ $key ], $p['rate'][ $key ], $amount, $poker );
        else
          $this->insertRate( $aff_id, $p['rate'][ $key ], $amount, $poker );
      }
    }
  }

    function getInfo ( $aff_id ) {
        $aff_id = intval($aff_id);
        $str = "SELECT * FROM affiliate_info WHERE affe_id = $aff_id";
        return phive( 'SQL' )->loadAssoc( $str );
    }

    function updateExtAffInfo($aff_id){
        $username = phive( 'UserHandler' )->getUsernameById( $aff_id );
        $ext_aff = phive( 'ExtAffiliater' )->insertAffiliate( $username );
        if(!empty($ext_aff)){
            $info = $this->getInfo($aff_id);
            $info['ext_pwd'] = $ext_aff['pw'];
            phive( 'SQL' )->save('affiliate_info', $info);
        }
    }

    function insertAffiliateInfo ( $aff_id, $ext = true ) {
        $info = $this->getInfo( $aff_id );
        if ( empty( $info ) ) {
            if ( $ext ) {
                $username = phive( 'UserHandler' )->getUsernameById( $aff_id );
                $ext_aff = phive( 'ExtAffiliater' )->insertAffiliate( $username );
                if ( !empty( $ext_aff ) )
                    return phive( 'SQL' )->insertArray( 'affiliate_info', array( 'affe_id' => $aff_id, 'ext_pwd' => $ext_aff['pw'] ) );
            } else
                return phive( 'SQL' )->insertArray( 'affiliate_info', array( 'affe_id' => $aff_id, 'ext_pwd' => uniqid() ) );
        }

        return false;
    }

  function insertCasinoAffiliate ( $aff_id, $ext = true ) {

    if ( $this->getSetting( 'skip_ext' ) == true )
      $ext = false;
    if ( $this->insertAffiliateInfo( $aff_id, $ext ) ) {
      foreach ( $this->getDefaultRates() as $drate ) {
        $this->insertRate( $aff_id, $drate['rate'], $drate['start_amount'] );
      }
      if ( phive( 'QuickFire' )->getSetting( 'poker' ) === true ) {
        foreach ( $this->getDefaultRates( 'poker', true ) as $drate ) {
          $this->insertRate( $aff_id, $drate['rate'], $drate['start_amount'], 'poker' );
        }
        foreach ( $this->getDefaultRates( 'sub', true ) as $drate ) {
          $this->insertRate( $aff_id, $drate['rate'], $drate['start_amount'], 'sub' );
        }
      }

      return true;
    }

    return false;
  }

  function getCasinoRatePercent ( $aff_id, $rev, $poker = '' ) {

    if ( $rev <= 0 )
      $str = "SELECT rate FROM {$this->getRateTable($poker)} WHERE affe_id = $aff_id AND start_amount = 0 LIMIT 0,1";
    else
      $str = "SELECT rate FROM {$this->getRateTable($poker)} WHERE affe_id = $aff_id AND start_amount < $rev ORDER BY start_amount DESC LIMIT 0,1";

    $rate = phive( 'SQL' )->getValue( $str );

    return empty( $rate ) ? 0 : $rate;
  }

  function getAllAffiliatesExt () {

    return phive( 'SQL' )->loadArray( "SELECT * FROM affiliate_info ai, users u WHERE ai.affe_id = u.id" );
  }

  function sumUp () {

    $args = func_get_args();
    $func = array_shift( $args );

    return phive()->sum2d( call_user_func_array( array( $this, $func ), $args ), 'amount', true );
  }

  //$table, $affe_id, $sdate, $edate, $by_month = false, $status = ''
  function getBankDeductionsByDate ( $aid, $sdate, $edate, $by_month = 'day', $split = false ) {

    $pfee = $this->getPendDepsFromAffiliate( 'pending_withdrawals', $aid, $sdate, $edate, $by_month );
    $dfee = $this->getPendDepsFromAffiliate( 'deposits', $aid, $sdate, $edate, $by_month );
    $chargebacks = $this->getTransactionsFromAffiliate( $aid, $sdate, $edate, $by_month, ' = 9 ' );
    if ( $split ) {
      $rtfees = array();
      $rchbacks = array();
    }
    $rarr = array();
    $col = $by_month == 'day' ? 'day_date' : 'month_num';
    $tvcol = $by_month == 'day' ? 'day_total' : 'month_total';
    foreach ( range( 0, 31 ) as $i ) {
      if ( !empty( $pfee[ $i ] ) ) {
        if ( $split )
          $rtfees[ $pfee[ $i ][ $col ] ] += $pfee[ $i ]['cost_total'];

        $rarr[ $pfee[ $i ][ $col ] ] += $pfee[ $i ]['cost_total'];
      }

      if ( !empty( $dfee[ $i ] ) ) {
        if ( $split )
          $rtfees[ $dfee[ $i ][ $col ] ] += $dfee[ $i ]['cost_total'];

        $rarr[ $dfee[ $i ][ $col ] ] += $dfee[ $i ]['cost_total'];
      }

      if ( !empty( $chargebacks[ $i ] ) ) {
        if ( $split )
          $rchbacks[ $chargebacks[ $i ][ $col ] ] += abs( $chargebacks[ $i ][ $tvcol ] );

        $rarr[ $chargebacks[ $i ][ $col ] ] += abs( $chargebacks[ $i ][ $tvcol ] );
      }
    }

    return $split ? array( $rtfees, $rchbacks, $rarr ) : $rarr;
  }

  function getBankDeductionSum ( $aid, $sdate, $edate ) {

    $pfee = $this->getPendDepsSumFromAffiliate( 'pending_withdrawals', $aid, $sdate, $edate, '', 'real_cost' );
    $dfee = $this->getPendDepsSumFromAffiliate( 'deposits', $aid, $sdate, $edate, '', 'real_cost' );
    $chargebacks = $this->getTransactionSumFromAffiliate( $aid, $sdate, $edate, '', ' = 9 ' );

    return $pfee + $dfee + $chargebacks;
  }

  function getAffStats ( $aid, $sdate, $edate, $table = 'affiliate_daily_stats' ) {
    $aid = intval($aid);
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    return phive( 'SQL' )->loadArray(
      "SELECT * FROM $table
        WHERE affe_id = $aid
        AND day_date >= '$sdate'
        AND day_date <= '$edate'"
    );
  }

    function calcProfit ( $aid, $total ) {
        if ( $total < 0 )
            return $total;
        $rl = $this->getRates( $aid );
        //TODO should the below 0.25 not be 0?
        //Currently people without levels get nothing so why should be use 25% here?
        if ( empty( $rl ) )
            return $total * 0.25;
        if ( count( $rl ) == 1 )
            return $total * $rl[0]['rate'];
        $prof = 0;
        for ( $i = 0; $i < count( $rl ); $i++ ) {
            $rate = $rl[ $i ]['rate'];
            if ( empty( $rl[ $i + 1 ] ) )
                return $prof + ( $rate * $total );
            else
                $amount = $rl[ $i + 1 ]['start_amount'] - $rl[ $i ]['start_amount'];
            if ( $total < $amount )
                return $prof + ( $rate * $total );
            $prof += $rate * $amount;
            $total -= $amount;
        }

        return $prof;
    }

  function calcRowsProfit($tbl, $aid, $sdate, $edate, $total, $tot_prof, $rate){
    $aid = intval($aid);
    $where_aff = empty( $aid ) ? '' : "AND affe_id = $aid";
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    $stats = phive( 'SQL' )->loadArray(
      "SELECT * FROM $tbl
       WHERE `date` >= '$sdate'
       AND `date` <= '$edate' $where_aff"
    );

    foreach($stats as $r){
      $aff_fee = $this->calcRowProfit($r, $total, $tot_prof, $rate);
      //$aff_fee = $r['before_deal'] * $rate;
      phive('SQL')->updateArray($tbl, array( 'aff_fee' => $aff_fee, 'aff_rate' => $rate ), array( 'id' => $r['id'] ) );
    }
  }

  function calcRowProfit(&$r, $total_gross, $total_prof, $rate){
    if ( empty( $rate ) || empty( $r['before_deal'] ) || empty( $total_prof ) )
      $prof = 0;
    else
      $prof = $total_prof * ( $r['before_deal'] / $total_gross );
    return $prof;
  }

  function calcCachedProfits ( $sdate, $edate, $user_only = false, $aid = '', $echo = false ) {
    foreach($this->getAffInfo( $aid ) as $info){
      $aid  = $info['affe_id'];
      $affe = phive( "UserHandler" )->getUser( $aid );
      if(!is_object($affe))
        continue;
      $stats       = $this->getAffStats( $aid, $sdate, $edate );
      $bcode_stats = $this->getAffStats( $aid, $sdate, $edate, 'affiliate_daily_bcodestats' );
      $total       = chg( $affe, 'EUR', phive()->sum2d( $stats, 'before_deal' ), 0.99, $sdate);
      $tot_prof    = $this->calcProfit( $aid, $total );
      $rate        = $this->getCasinoRatePercent( $aid, $total );

      if($echo)
        print_r(array('total' => $total, 'tot_prof' => $tot_prof, 'rate' => $rate));

      if(!$user_only){
        foreach(array('affiliate_daily_stats' => $stats, 'affiliate_daily_bcodestats' => $bcode_stats ) as $tbl => $data) {
          foreach($data as $r){
            if(!empty($r['affe_id'])){
              //$prof = $r['before_deal'] * $rate;
              $prof = $this->calcRowProfit($r, $total, $tot_prof, $rate);
              if($echo)
                print_r( array( 'prof' => $prof ) );
              phive( 'SQL' )->insertOrUpdate( $tbl, $r, array( 'prof' => $prof ) );
            }
          }
        }
      }

      $this->calcRowsProfit('users_daily_stats', $aid, $sdate, $edate, $total, $tot_prof, $rate);

      if(hasMp()){
        $this->calcRowsProfit('users_daily_stats_mp', $aid, $sdate, $edate, $total, $tot_prof, $rate);
        $this->calcRowsProfit('users_daily_stats_total', $aid, $sdate, $edate, $total, $tot_prof, $rate);
      }
    }
  }

  function getSubAffs($aff_id) {
    $aff_id = intval($aff_id);
    $str = "SELECT DISTINCT id FROM users WHERE affe_id = $aff_id AND id IN(SELECT affe_id FROM affiliate_info)";
    return phive( 'SQL' )->load1DArr( $str, 'id' );
  }

  function hasSubDeal ( $affe_id ) {
    $deal = $this->getCasinoRatePercent( $affe_id, 1000000000000, 'sub' );
    return !empty( $deal );
  }

  function calcCachedSubProfits ( $sdate, $edate, $from_tbl, $tbl, $net_key, $aid = '' ) {

    foreach ( $this->getAffInfo( $aid ) as $info ) {
      $aid = $info['affe_id'];
      $affe = phive( "UserHandler" )->getUser( $aid );
      if ( !is_object( $affe ) )
        continue;
      $affe_cur = $affe->getAttr( 'currency' );

      if ( $this->hasSubDeal( $aid ) ) {
        $sub_ids = $this->getSubAffs( $aid );
        $total = 0;
        $sub_stats = array();
        $rate = 0;
        $date_col = 'day_date';
        $prof_col = 'prof';
        foreach ( $sub_ids as $sub_id ) {
          $sub_id = intval($sub_id);
          $sdate = phive('SQL')->escape($sdate,false);
          $edate = phive('SQL')->escape($edate, false);
          $stats = phive( 'SQL' )->loadArray( "SELECT * FROM $from_tbl WHERE affe_id = $sub_id AND $date_col >= '$sdate' AND $date_col <= '$edate'" );
          if ( !empty( $stats ) ) {
            $tmp = phive()->sum2d( $stats, 'before_deal' );
            $total += ( $affe_cur == $stats[0]['currency'] ? $tmp :
              phive( "Currencer" )->changeMoney( $stats[0]['currency'], $affe_cur, $tmp ) );
            foreach ( $stats as &$r ) {
              $r['sub_affe_id'] = $sub_id;
              $r['affe_id'] = $aid;
            }
            $sub_stats[] = $stats;
          }
        }

        $rate = $this->getCasinoRatePercent( $aid, $total, 'sub' );

        $sub_cols = phive( 'SQL' )->getColumns( $tbl, true );

        foreach ( $sub_stats as $subs ) {
          foreach ( $subs as $r ) {
            $profit = $r['before_deal'] * $rate;
            $r[ $net_key ] = $profit;
            $insert = array_intersect_key( $r, $sub_cols );
            phive( 'SQL' )->insertOrUpdate( $tbl, $insert, array( $net_key => $profit ) );
          }
        }
      }
    }
  }

  function calcCachedCasinoSubProfits ( $sdate, $edate, $aid = '' ) {

    $this->calcCachedSubProfits( $sdate, $edate, 'affiliate_daily_stats', 'sub_casino_affiliate_earnings', 'prof',
                                 $aid );
  }

  function getCachedAffProfitsByDayAndAff ( $sdate, $edate ) {

    $ret = array();

    //$sums = phive('SQL')->makeSums(array('gross', 'bets', 'wins', 'op_fees', 'bank_fees', 'before_deal', 'fails', 'rewards', 'prof'));
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    foreach ( phive( 'SQL' )->loadArray( "SELECT * FROM affiliate_info" ) as $info ) {
      $ret[ $info['affe_id'] ] = phive( 'SQL' )->loadArray(
        "SELECT *
        FROM affiliate_daily_stats
        WHERE affe_id = {$info['affe_id']}
        AND day_date >= '$sdate'
        AND day_date <= '$edate'",
        'ASSOC',
        'day_date'
      );
    }

    return $ret;
  }

    function sumCachedColsForAff ( $aid, $sdate, $edate, $tbl = 'affiliate_daily_stats', $extra = array() ) {

        if ( $tbl == 'affiliate_daily_stats' || $tbl == 'affiliate_daily_bcodestats' ) {
            $num_cols = array( 'gross', 'bets', 'wins', 'op_fees', 'bank_fees', 'before_deal', 'fails', 'rewards', 'prof',
                               'paid_loyalty'
            );
        }

        if ( $tbl == 'sub_casino_affiliate_earnings' ) {
            $num_cols = array( 'before_deal', 'prof' );
        }

        $sums = phive( 'SQL' )->makeSums(
            array_merge( $num_cols, $extra )
        );

        $aid = intval($aid);
        $where = empty( $aid ) ? "" : "AND affe_id = $aid";
        $sdate = phive('SQL')->escape($sdate,false);
        $edate = phive('SQL')->escape($edate,false);
        $str = "SELECT $sums FROM $tbl WHERE day_date >= '$sdate' AND day_date <= '$edate' $where";

        return phive( 'SQL' )->loadAssoc( $str );
    }

    function getUDSNumCols () {

        return array( 'bets', 'wins', 'deposits', 'withdrawals', 'rewards', 'fails', 'gross', 'op_fee', 'bank_fee',
                      'aff_fee', 'site_rev', 'before_deal', 'bank_deductions', 'jp_contrib', 'real_aff_fee', 'site_prof',
                      'chargebacks', 'transfer_fees', 'gen_loyalty', 'paid_loyalty'
        );
    }

    function getCacheForAff ( $affe_id, $sdate, $edate, $by_month = false, $tbl = 'affiliate_daily_stats', $extra = array(), $cur = '', $only_real = false, $bonus_code = '', $with_manager = false) {

        if ( !empty( $bonus_code ) || $by_month === 'bonus_code' )
            $tbl = 'affiliate_daily_bcodestats';

        $where = empty( $affe_id ) ? " AND $tbl.affe_id != 0 " : " AND $tbl.affe_id = $affe_id ";

        if ( !empty( $only_real ) )
            $where = " AND $tbl.affe_id IN (SELECT affe_id FROM affiliate_rates WHERE rate > 0) ";

        if ( !empty( $bonus_code ) )
            $where .= " AND bonus_code = '$bonus_code' ";

        if ( $tbl == 'affiliate_daily_stats' || $tbl == 'affiliate_daily_bcodestats' ) {
            $num_cols =
            array( 'gross', 'admin_fee', 'bets', 'wins', 'op_fees', 'bank_fees', 'before_deal', 'fails', 'rewards', 'prof',
                   'real_prof', 'paid_loyalty', 'deposits', 'tax', 'paid_loyalty', 'jp_fee'
            );
            $date_col = 'day_date';
        }

        if ( $tbl == 'sub_casino_affiliate_earnings' ) {
            $num_cols = array( 'before_deal', 'prof' );
            $date_col = 'day_date';
        }

        if ( $tbl == 'users_daily_stats' ) {
            $num_cols = $this->getUDSNumCols();
            $date_col = 'date';
        }

        $sums = phive( 'SQL' )->makeSums( array_merge( $num_cols, $extra ) );
        $where_cur = empty( $cur ) ? '' : "AND $tbl.currency = '$cur'";

        if ( $by_month === true || $by_month == 'month_num' ) {
            $group1 = "`$date_col` AS day_date, DATE_FORMAT($date_col, '%Y-%m') AS month_num, $sums";
            $group2 = ' GROUP BY month_num ';
            $group_by = 'month_num';
        } else if ( $by_month == 'day_date' ) {
            $group1 = "`$date_col` AS day_date, $sums";
            $group2 = ' GROUP BY day_date ';
            $group_by = 'day_date';
        } else if ( $by_month == 'bonus_code' ) {
            $group1 = "bonus_code, $sums";
            $group2 = ' GROUP BY bonus_code ';
            $group_by = 'bonus_code';
        } else if ( $by_month == 'day' ) {
            $group_by = 'day_num';
            $group1 = "*, DAYOFMONTH($date_col) AS day_num, $sums";
            $group2 = " GROUP BY day_num ";
        } else if ( $by_month == 'affiliate' ) {
            $group_by = 'affe_id';
            $group1 = "$tbl.affe_id, u.email, u.currency, u.firstname, u.username, u.username as affiliate, $sums";
            $group2 = ' GROUP BY affe_id ';
            $join = "LEFT JOIN users AS u ON u.id = $tbl.affe_id";
        } else {
            $group1 = '*';
            $group_by = false;
        }

        if($with_manager){
            $group1 .= ", us.value AS aff_manager";
            $join   .= " LEFT JOIN users_settings AS us ON us.user_id = u.id AND us.setting = 'aff_manager' ";
        }

        $str = "SELECT $group1
                FROM $tbl
                    $join
                WHERE `$date_col` >= '$sdate'
                    AND `$date_col` <= '$edate'
                    $where
                    $where_cur
                    $group2
                    ORDER BY `$date_col` DESC";

        return phive( 'SQL' )->loadArray( $str, 'ASSOC', $group_by );
    }

  function insertStats ( $day, $aid = '') {

    //TODO don't do people under a cpa bcode and ignore cpa rows when doing retroative calculations, you can lock on to the fact that all columns except real_prof will be zero

    if ( !empty( $aid ) )
      $aid = intval($aid);
      $where = " WHERE affe_id = $aid ";

    $sql = phive( 'SQL' );
    $curs = phive( "Currencer" )->getAllCurrencies();

    $num_cols = array(
      'gross'         => 'gross',
      'bets'          => 'bets',
      'wins'          => 'wins',
      'deposits'      => 'deposits',
      'op_fees'       => 'op_fee',
      'bank_fees'     => 'bank_fee',
      'before_deal'   => 'before_deal',
      'fails'         => 'fails',
      'rewards'       => 'rewards',
      'prof'          => 'aff_fee',
      'real_prof'     => 'real_aff_fee',
      'chargebacks'   => 'chargebacks',
      'transfer_fees' => 'transfer_fees',
      'paid_loyalty'  => 'paid_loyalty',
      'tax'           => 'tax',
      'jp_fee'        => 'jp_fee'
    );

    $currencer = phive( "Currencer" );
    $uds_table = phive('UserHandler')->dailyTbl();
    //$mp_rows   = hasMp() ? phive('UserHandler')->getDailyStats($day, $day, '', 'users_daily_stats_mp', 'user_id') : array();

    foreach ( $sql->loadArray( "SELECT * FROM affiliate_info $where" ) as $info ) {
      $insert    = array();
      $bc_insert = array();
      $aid       = $info['affe_id'];
      $affe      = phive("UserHandler")->getUser($aid);
      if(!is_object($affe))
        continue;
      $admin_pc = $affe->getSettingOrGlobal( 'affiliate_admin_fee', 'affiliate', 'admin-fee' );
      $str      = "SELECT uds.*, u.bonus_code FROM $uds_table uds, users u WHERE u.id = uds.user_id AND uds.affe_id = $aid AND uds.date = '$day'";

      $affe_cur = $affe->getAttr('currency');
      $bcodes   = $this->getBonusCodes( $aid, true );

      foreach($sql->loadArray( $str ) as $r){
        $cur     = array();
        $cur_uid = $r['user_id'];
        foreach($num_cols as $affkey => $usrkey){
          $val                = $r['currency'] == $affe_cur ? $r[$usrkey] : chg($r['currency'], $affe_cur, $r[$usrkey], 0.99, $day);
          $cur[$affkey]       = $val + 0;
          $insert[ $affkey ] += $val + 0;
          if(!empty($bcodes[$r['bonus_code']]))
            $bc_insert[$r['bonus_code']][$affkey] += $val + 0;
        }

        //$admin_fee              = ($cur['gross'] + $mp_rows[$cur_uid]['gross']) * $admin_pc;
        $admin_fee              = $cur['gross'] * $admin_pc;
        $gross_deduction        = $admin_fee + $cur['jp_fee'];
        $insert['gross']       -= $gross_deduction;
        $insert['before_deal'] -= $admin_fee;
        $insert['admin_fee']   += $admin_fee;
        //if(!empty($bc_insert[$r['bonus_code']])){
        $bc_insert[ $r['bonus_code'] ]['before_deal'] -= $admin_fee;
        $bc_insert[ $r['bonus_code'] ]['gross']       -= $gross_deduction;
        $bc_insert[ $r['bonus_code'] ]['admin_fee']   += $admin_fee;
        //}
          if ( !empty( $admin_fee ) )
              $sql->updateArray( phive('UserHandler')->dailyTbl(), ['before_deal' => $r['before_deal'] - ( $r['gross'] * $admin_pc )], ['id' => $r['id']] );
      }

      if ( !empty( $insert )) {
        $insert['day_date'] = $day;
        $insert['affe_id']  = $info['affe_id'];
        $insert['currency'] = $affe_cur;
        $sql->insertArray( 'affiliate_daily_stats', $insert );
        foreach ( $bc_insert as $bc => $bc_arr ) {
          $bc_arr['day_date']   = $day;
          $bc_arr['affe_id']    = $info['affe_id'];
          $bc_arr['currency']   = $affe_cur;
          $bc_arr['bonus_code'] = $bc;
          $sql->insertArray( 'affiliate_daily_bcodestats', $bc_arr );
        }
      }
    }
  }

  function getAffProfitsByDay ( $sdate, $edate, $debug = false ) {

    $res = $this->getCachedAffProfitsByDayAndAff( $sdate, $edate );

    if ( $debug )
      return $res;

    $ret = array();

    foreach ( $res as $aid => $data ) {
      $ret[ $aid ] = phive()->sum2d( $data, 'prof' ) > 0 ? $data : array();
    }

    return $ret;
  }

    /*
       function sumVipPayouts ( $user_id, $sdate, $edate ) {
       $where = empty( $user_id ) ? '' : "AND user_id = $user_id";
       $str   = "SELECT SUM(points) AS points, SUM(cents) AS cents FROM vip_payouts WHERE `timestamp` >= '$sdate' AND `timestamp` <= '$edate' $where";
       return phive( 'SQL' )->loadAssoc( $str );
       }
     */

  function getDailyTbls(){
    $ret = array('users_daily_stats');
    if(hasMp())
      $ret[] = 'users_daily_stats_total';
    return $ret;
  }

  function calcRealProfits ( $aid, $prof, $sdate, $edate) {
    $aid = intval($aid);
    $sql = phive( 'SQL' );
    if($prof <= 0){
      $str = "UPDATE affiliate_daily_stats SET real_prof = 0 WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' AND affe_id = $aid";
      $sql->query($str);
    }else{
      $str = "UPDATE affiliate_daily_stats SET real_prof = prof WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' AND affe_id = $aid";
      $sql->query($str);
      $str = "UPDATE affiliate_daily_bcodestats SET real_prof = prof WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' AND affe_id = $aid";
      $sql->query($str);
    }

    foreach($this->getDailyTbls() as $tbl){
      if($prof <= 0){
        $str = "UPDATE $tbl SET real_aff_fee = 0, site_prof = site_rev WHERE `date` >= '$sdate' AND `date` <= '$edate' AND affe_id = $aid";
        $sql->query($str);
      }else{
        $str = "UPDATE $tbl SET real_aff_fee = aff_fee, site_prof = site_rev - aff_fee WHERE `date` >= '$sdate' AND `date` <= '$edate' AND affe_id = $aid";
        $sql->query($str);
      }
    }
  }

  function calcRealNonAffProfits($sdate, $edate){
    foreach($this->getDailyTbls() as $tbl)
      phive( 'SQL' )->query( "UPDATE $tbl SET site_prof = site_rev WHERE `date` >= '$sdate' AND `date` <= '$edate' AND affe_id = 0" );
  }

  function getAffInfo ( $aid = '' ) {

    if ( !empty( $aid ) )
      $where = " WHERE affe_id = $aid ";

    return phive( 'SQL' )->loadArray( "SELECT * FROM affiliate_info $where" );
  }

  /*
   * Needs to always be called with month start and end date
  */
  function generateCasinoAffPayouts ( $sdate, $edate, $pay = false, $aid = '') {

    $ret = array();
    foreach($this->getAffInfo( $aid ) as $info) {
      $sums      = $this->sumCachedColsForAff( $info['affe_id'], $sdate, $edate );
      $this->calcRealProfits($info['affe_id'], (int)$sums['prof'], $sdate, $edate);
      $sub_net   = $this->sumSubCasinoProfit( $info['affe_id'], $sdate, $edate );
      $main_prof = (int)$sums['prof'];
      $sub_prof  = (int)$sub_net;
      $map       = array();

      if($pay){
        if($main_prof < 0 || $sub_prof < 0 ){
          $tot_prof = $main_prof + $sub_prof;
          if($tot_prof > 0){
            $type = $main_prof < 0 ? 20 : 5;
            $map = array(array($tot_prof, $type, "Combined commissions for $sdate to $edate"));
          }
        }else{
          $map = array();
          if($main_prof > 0)
            $map[] = array($main_prof, 5, "Commissions for $sdate to $edate");
          if($sub_prof > 0)
            $map[] = array( $sub_prof, 20, "Sub commissions for $sdate to $edate");
        }

        foreach($map as $m){
          $q                    = array();
          $q['user_id']         = $info['affe_id'];
          $q['amount']          = $m[0];
          $q['transactiontype'] = $m[1];
          $q['description']     = $m[2];
          phive( "SQL" )->insertArray('queued_transactions', $q);
        }
      }
    }
    $this->calcRealNonAffProfits($sdate, $edate);
  }

  function affSetting ( $aid, $setting, $default = '' ) {
    $val = phive( 'SQL' )->getValue("SELECT value FROM users_settings WHERE setting = 'affiliate_$setting' AND user_id = $aid");
    return $val === false ? $default : $val;
  }

  /*
   * sdate and edate should be first and last day of any given month
   */
  function recalcAff( $sdate, $edate, $aid = '' ) {


    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate, false);
    $sql = phive( 'SQL' );
    if ( !empty( $aid ) )
      $where = " AND affe_id = $aid ";
    $sql->query( "DELETE FROM affiliate_daily_stats WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' $where" );
    $sql->query( "DELETE FROM affiliate_daily_bcodestats WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' $where" );
    $sql->query( "DELETE FROM sub_casino_affiliate_earnings WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' $where" );

    $ustats = $sql->loadArray("SELECT * FROM users_daily_stats WHERE `date` >= '$sdate' AND `date` <= '$edate' $where GROUP BY `date`");

    foreach ( $ustats as $s ) {
      $this->insertStats( $s['date'], $aid );
    }

    $this->calcCachedProfits( $sdate, $edate, false, $aid );
    $this->calcCachedCasinoSubProfits( $sdate, $edate, $aid );
    $this->generateCasinoAffPayouts( $sdate, $edate, false, $aid );
  }

  function sumSubCasinoProfit($aid, $sdate, $edate){
    $aff = cu($aid);
    if(empty($aff))
      return 0;
    $str = "SELECT SUM(prof) FROM sub_casino_affiliate_earnings WHERE affe_id = $aid AND `day_date` >= '$sdate' AND `day_date` <= '$edate'";
    if(phive()->isEmpty($aff->getSetting('sub_aff_no_neg_carry')))
      return phive( 'SQL' )->getValue( $str );
    else{
      $str .= " GROUP BY sub_affe_id";
      $res = 0;
      foreach(phive('SQL')->loadArray($str) as $r){
        if((int)$r['prof'] > 0)
          $res += $r['prof'];
      }
      return $res;
    }
  }

  function getUsersFromAffiliate ($affe_id, $sdate, $edate, $by_month = false, $cur = '', $where_extra = '', $ver_email = false, $node = -1, $join_province = '') {
    $sdate = phive('SQL')->escape(empty( $sdate ) ? '2000-01-01' : $sdate,false);
    $edate = phive('SQL')->escape(empty( $edate ) ? date( 'Y-m-d' ) : $edate,false);
    $join  = '';
    if($by_month === true) {
      $group1 = ", COUNT(users.id) AS month_count, DATE_FORMAT(users.register_date, '%Y-%m') AS month_num";
      $group2 = 'GROUP BY month_num';
      $group_by = 'month_num';
    } else if ( $by_month == 'day' ) {
      $group1 = ', COUNT(users.id) AS day_count, DAYOFMONTH(register_date) AS day_num';
      $group2 = 'GROUP BY day_num';
      $group_by = 'day_num';
    } else if ( $by_month == 'date' ) {
      $group1 = ', COUNT(users.id) AS day_count, DATE(register_date) AS date';
      $group2 = 'GROUP BY date';
      $group_by = 'date';
    } else if ( $by_month == 'affiliate' ) {
      $group1 = ", COUNT(users.id) AS {$by_month}_count, affe_id";
      $group2 = "GROUP BY affe_id";
      $group_by = 'affe_id';
    } else if ( !empty( $by_month ) ) {
      $group1 = ", COUNT(users.id) AS {$by_month}_count";
      $group2 = "GROUP BY $by_month";
      $group_by = $by_month;
    } else
      $group_by = false;

    if ( $affe_id == 'all' )
      $where_aff = "AND users.bonus_code != ''";
    else
      $where_aff = empty( $affe_id ) ? '' : "AND users.{$this->getAffUserCol($affe_id)} = '$affe_id'";

    $where_cur = empty( $cur ) ? '' : "AND currency = '$cur'";

    if ( $ver_email ) {
      $join .= "INNER JOIN users_settings AS us ON us.user_id = users.id AND us.setting = 'email_code_verified' AND us.value = 'yes'";
    }

    if(!empty($join_province)) {
        $join .= $join_province;
    }
     
    $str = "SELECT users.*$group1 FROM users
      $join
      WHERE DATE(register_date) >= '$sdate'
      AND DATE(register_date) <= '$edate'
                $where_aff $where_cur $where_extra $group2";

      //return phive( 'SQL' )->shs(['action' => 'sum', 'do_not' => [$group_by]], '', null, 'users')->loadArray( $str, 'ASSOC', $group_by );
    return phive( 'SQL' )->getDbOrShsById($node, [['action' => 'sum', 'do_not' => [$group_by]], '', null, 'users'])->loadArray( $str, 'ASSOC', $group_by );
  }

  function getXtorsCommon ( $by_month ) {

    if ( $by_month === true ) {
      $group1 = ", COUNT(DISTINCT cs.user_id) AS month_count, DATE_FORMAT(cs.timestamp, '%Y-%m') AS month_num";
      $group2 = ' GROUP BY month_num ';
      $group_by = 'month_num';
    } else if ( $by_month == 'day' ) {
      $group1 = ', COUNT(DISTINCT cs.user_id) AS day_count, DAYOFMONTH(cs.timestamp) AS day_num';
      $group2 = ' GROUP BY day_num ';
      $group_by = 'day_num';
    } else if ( !empty( $by_month ) ) {
      $group1 = ", COUNT(DISTINCT cs.user_id) AS {$by_month}_count";
      $group2 = "GROUP BY u.$by_month";
      $group_by = $by_month;
    } else {
      $group2 = ' GROUP BY u.id';
      $group_by = false;
    }

    return array( $group1, $group2, $group_by );
  }

    function getWhereAffe ( $affe_id ) {
        if(is_numeric($affe_id))
            die("getWhereAffe with numeric affe id doesn't work anymore");
        if(empty($affe_id))
            return "1";
        // $affe_id == 'all'
        return "u.bonus_code != ''";
    }

  function getDepositorsFromAffiliate ($affe_id, $sdate, $edate, $by_month = false, $dep_type = '', $cur = '', $where_extra = '', $node = -1, $join_province = '') {
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    $cur = phive('SQL')->escape($cur,false);
    $dep_type = phive('SQL')->escape($dep_type,false);
    list( $group1, $group2, $group_by ) = $this->getXtorsCommon( $by_month );
    $in_aff = $this->getWhereAffe( $affe_id );
    $where_cur = empty( $cur ) ? '' : "AND u.currency = '$cur'";
    $where_dep_type = empty( $dep_type ) ? '' : "AND cs.dep_type = '$dep_type' ";
    $str = "SELECT u.*, cs.timestamp$group1
      FROM  deposits cs, users u
      $join_province
      WHERE $in_aff
      $where_cur
      $where_extra
      AND u.id = cs.user_id
      AND cs.timestamp >= '$sdate'
      AND cs.timestamp <= '$edate'
      $where_dep_type
      $group2
      ORDER BY cs.timestamp DESC";
      
      return phive( 'SQL' )->getDbOrShsById($node, [['action' => 'sum', 'do_not' => [$group_by]], '', null, 'deposits'])->loadArray( $str, 'ASSOC', $group_by );
      //return phive( 'SQL' )->shs(['action' => 'sum', 'do_not' => [$group_by]], '', null, 'deposits')->loadArray( $str, 'ASSOC', $group_by );
  }

  function getTransactorsFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false, $trans_type = 3, $cur = '', $where_extra = '' ) {
    $cur = phive('SQL')->escape($cur,false);
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    list( $group1, $group2, $group_by ) = $this->getXtorsCommon( $by_month );
    $in_aff = $this->getWhereAffe( $affe_id );
    $where_cur = empty( $cur ) ? '' : "AND u.currency = '$cur'";

    $str = "SELECT u.*, cs.timestamp$group1
      FROM users u, cash_transactions cs
      WHERE $in_aff
      $where_cur
      $where_extra
      AND u.id = cs.user_id
      AND cs.transactiontype = $trans_type
      AND cs.timestamp >= '$sdate'
      AND cs.timestamp <= '$edate'
      $group2
      ORDER BY cs.timestamp DESC";

    return phive( 'SQL' )->loadArray( $str, 'ASSOC', $group_by );
  }

  function getTrDepsCommon ( $by_month, $sum_deducted = true, $tbl ) {

    //$sel_ded_sum = $sum_deducted === true ? "SUM(deducted_amount) AS ded_month_total," : '';
    if ( $by_month === true ) {
      $group1 =
        ", SUM(ABS(amount)) AS month_total, SUM(deducted_amount) AS ded_month_total, COUNT($tbl.id) AS month_count, DATE_FORMAT(`timestamp`, '%Y-%m') AS month_num";
      $group2 = 'GROUP BY month_num';
      $group_by = 'month_num';
    } else if ( $by_month == 'day' ) {
      $group1 =
        ", SUM(ABS(amount)) AS day_total, SUM(deducted_amount) AS ded_day_total, COUNT($tbl.id) AS day_count, DAYOFMONTH(`timestamp`) AS day_num, DATE(timestamp) AS day_date";
      $group2 = 'GROUP BY day_num';
      $group_by = 'day_num';
    } else if ( $by_month == 'affiliate' ) {
      $group1 =
        " , SUM(ABS(amount)) AS affiliate_total, SUM(deducted_amount) AS ded_affiliate_total, ar.affe_id AS affe_id";
      $group2 = "GROUP BY ar.affe_id";
      $group_by = 'affe_id';
    } else if ( !empty( $by_month ) ) {
      $group1 =
        ", users.*, SUM(ABS(amount)) AS {$by_month}_total, SUM(deducted_amount) AS ded_{$by_month}_total, COUNT($tbl.id) AS {$by_month}_count";
      $group2 = "GROUP BY users.$by_month";
      $group_by = $by_month;
      $join_users = true;
    } else
      $group_by = false;

    return array( $group1, $group2, $group_by, $join_users );
  }

  function getAffUserCol ( $affe_id ) {

    if ( is_numeric( $affe_id ) )
      return 'affe_id';

    return 'bonus_code';
  }

  function getDepositsFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false, $dep_type = '', $cur = '', $where_extra = '', $in_cur = '', $in_extra = '', $node = -1, $join_province = '' ) {

    $cur = phive('SQL')->escape($cur,false);
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    list( $group1, $group2, $group_by, $join_users ) = $this->getTrDepsCommon( $by_month, true, 'deposits' );
    $ucol = $this->getAffUserCol( $affe_id );
    $where = $affe_id == 'all' ? "WHERE $ucol != ''" : "WHERE $ucol = '$affe_id'";
    $in_aff = empty( $affe_id ) ? '' : "AND deposits.user_id IN(SELECT id FROM users $where $in_extra)";

    $where_cur = empty( $cur ) ? '' : "AND deposits.currency = '$cur'";
    $where_dep_type = empty( $dep_type ) ? '' : "AND dep_type = '$dep_type' ";
    
    if ( !empty( $where_extra ) || $join_users === true )
      $join = " LEFT JOIN users ON users.id = deposits.user_id $where_extra";

    if ( $by_month === 'affiliate' )
      $join = " INNER JOIN users AS ar ON ar.id = deposits.user_id ";

    if ( !empty( $in_cur ) ) {
      $in_cur_join = "LEFT JOIN currencies ON currencies.code = deposits.currency";
      $group1 = str_replace( array( 'ABS(amount)', 'deducted_amount' ),
                             array( 'ABS(amount) / currencies.multiplier', 'deducted_amount / currencies.multiplier' ),
                             $group1 );
    }

    $sql = "SELECT deposits.*$group1, DATE_FORMAT(deposits.timestamp, '%Y-%m') AS ym FROM deposits
      $in_cur_join
      $join
      $join_province
      WHERE `timestamp` >= '$sdate'
      AND `timestamp` <= '$edate'
      $where_dep_type
      $in_aff
      $where_cur
      $where_extra
      $group2";

      return phive( 'SQL' )->getDbOrShsById($node, [['action' => 'sum', 'do_not' => [$group_by]], '', null, 'deposits'])->loadArray( $sql, 'ASSOC', $group_by );
      //return phive( 'SQL' )->shs(['action' => 'sum', 'do_not' => [$group_by]], '', null, 'deposits')->loadArray( $sql, 'ASSOC', $group_by );
  }

  function getTransactionsFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false, $trans_type = ' = 3 ', $cur = '', $where_extra = '', $in_cur = '' ) {
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    list( $group1, $group2, $group_by, $join_users ) = $this->getTrDepsCommon( $by_month, true, 'cash_transactions' );
    $where = $affe_id == 'all' ? "WHERE affe_id != 0" : "WHERE affe_id = $affe_id";
    $in_aff = empty( $affe_id ) ? '' : "AND cash_transactions.user_id IN(SELECT id FROM users $where)";
    $where_cur = empty( $cur ) ? '' : "AND cash_transactions.currency = '$cur'";

    if ( !empty( $where_extra ) || $join_users === true )
      $join = " LEFT JOIN users ON users.id = cash_transactions.user_id $where_extra";

    if ( !empty( $in_cur ) ) {
      $in_cur_join = "LEFT JOIN currencies ON currencies.code = cash_transactions.currency";
      $group1 = str_replace( 'ABS(amount)', 'ABS(amount) / currencies.multiplier', $group1 );
    }

    $sql = "SELECT cash_transactions.*$group1 FROM cash_transactions
      $in_cur_join
      $join
      WHERE transactiontype $trans_type
      AND `timestamp` >= '$sdate'
      AND `timestamp` <= '$edate'
      $in_aff
      $where_cur
      $where_extra
      $group2";

    return phive( 'SQL' )->loadArray( $sql, 'ASSOC', $group_by );
  }

  function getPendDepsFromAffiliate ( $table, $affe_id, $sdate, $edate, $by_month = false, $status = '' ) {
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    $affe_id = intval($affe_id);
    $id_name = $table == 'deposits' ? 'id' : 'id';

    if ( $by_month === true ) {
      $group1 =
        ", SUM(ABS(amount)) AS month_total, SUM(ABS(real_cost)) AS cost_total, COUNT($id_name) AS month_count, MONTH(`timestamp`) AS month_num";
      $group2 = 'GROUP BY month_num';
      $group_by = 'month_num';
    } else if ( $by_month == 'day' ) {
      $group1 =
        ", SUM(ABS(amount)) AS day_total, SUM(ABS(real_cost)) AS cost_total, COUNT($id_name) AS day_count, DAYOFMONTH(`timestamp`) AS day_num, DATE(timestamp) AS day_date";
      $group2 = 'GROUP BY day_num';
      $group_by = 'day_num';
    } else
      $group_by = false;

    $where = empty( $affe_id ) ? "WHERE affe_id != 0" : "WHERE affe_id = $affe_id";
    $where_status = empty( $status ) ? "" : "AND status = '$status'";

    $sql = "SELECT $table.*$group1 FROM $table
      WHERE `timestamp` >= '$sdate'
      AND `timestamp` <= '$edate'
      $where_status
      AND id IN(SELECT id FROM users $where) $group2";

    return phive( 'SQL' )->loadArray( $sql, 'ASSOC', $group_by );
  }

  //'pending_withdrawals', $aid, $sdate, $edate, '', 'real_cost'
  function getPendDepsSumFromAffiliate ( $table, $affe_id, $sdate, $edate, $status = '', $col = 'amount' ) {
    return phive()->sum2d( $this->getPendDepsFromAffiliate( $table, $affe_id, $sdate, $edate, false, $status ), $col, true);
  }

  function getTransactionSumFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false, $trans_type = ' = 3 ' ) {
    return phive()->sum2d( $this->getTransactionsFromAffiliate( $affe_id, $sdate, $edate, $by_month, $trans_type ), 'amount', true );
  }

  function getRewardsFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false ) {
    return $this->getTransactionsFromAffiliate( $affe_id, $sdate, $edate, $by_month, ' IN(6,9,7,14) ' );
  }

  function getRewardSumFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false ) {
    return phive()->sum2d( $this->getRewardsFromAffiliate( $affe_id, $sdate, $edate, $by_month ), 'amount', true );
  }

  function getFirstDepositorsFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false, $cur = '', $where_extra = '', $dep_type = '', $node = -1, $join_province = '') {
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    $dep_type = phive('SQL')->escape($dep_type,false);
    if ( $by_month === true ) {
      $group1 =
        ", SUM(first_deposits.amount) AS month_total, COUNT(first_deposits.id) AS month_count, DATE_FORMAT(first_deposits.`timestamp`, '%Y-%m') AS month_num";
      $group2 = 'GROUP BY month_num';
      $group_by = 'month_num';
    } else if ( $by_month == 'day' ) {
      $group1 = ', SUM(first_deposits.amount) AS day_total, COUNT(first_deposits.id) AS day_count, DAYOFMONTH(first_deposits.`timestamp`) AS day_num';
      $group2 = 'GROUP BY day_num';
      $group_by = 'day_num';
    } else if ( $by_month == 'affiliate' ) {
      $group1 = ", COUNT(user_id) AS {$by_month}_count, ar.affe_id AS affe_id";
      $group2 = "GROUP BY ar.affe_id";
      $join = " INNER JOIN users AS ar ON ar.id = first_deposits.user_id ";
      $group_by = 'affe_id';
    } else if ( !empty( $by_month ) ) {
      $group1 = ", SUM(first_deposits.amount) AS {$by_month}_total, COUNT(first_deposits.id) AS {$by_month}_count";
      $group2 = "GROUP BY users.$by_month";
      $group_by = $by_month;
      $join_users = true;
    } else
      $group_by = false;

    $aff_col    = $this->getAffUserCol( $affe_id );
    $where_aff  = $affe_id == 'all' ? "WHERE bonus_code != ''" : "WHERE $aff_col = '$affe_id'";
    $in_aff     = empty( $affe_id ) ? '' : "AND first_deposits.user_id IN(SELECT id FROM users $where_aff)";
    $where_cur  = empty( $cur ) ? '' : "AND first_deposits.currency = '$cur'";
    $where_type = empty( $dep_type ) ? '' : "AND first_deposits.dep_type = '$dep_type'";
    $join_start = empty( $bonus_code ) ? 'LEFT' : 'INNER';

    if ( !empty( $where_extra ) || $join_users === true ) {
      $join = "
        LEFT JOIN users ON users.id = first_deposits.user_id
        INNER JOIN deposits ON deposits.id = first_deposits.deposit_id $where_extra
        ";
    }

    if(!empty($join_province)){
        $join .= $join_province;
    }

    $str = "SELECT * $group1 FROM first_deposits
      $join
      WHERE first_deposits.timestamp >= '$sdate'
      AND first_deposits.timestamp <= '$edate'
      $where_cur
      $where_type
      $where_extra
      $in_aff
      $group2";

      return phive( 'SQL' )->getDbOrShsById($node, [['action' => 'sum', 'do_not' => [$group_by]], '', null, 'first_deposits'])->loadArray( $str, 'ASSOC', $group_by );
      //return phive( 'SQL' )->shs(['action' => 'sum', 'do_not' => [$group_by]], '', null, 'first_deposits')->loadArray( $str, 'ASSOC', $group_by );
  }

    /*
  function getBetWinPeriodFromAffiliate ( $affe_id, $sdate, $edate, $by_month = false ) {
    return array(
      'amount_staked' => $this->getBetWinPeriodCommon( 'bets' . phive('Casino')->suffix, $affe_id, $sdate, $edate, $by_month ),
      'amount_won'    => $this->getBetWinPeriodCommon( 'wins' . phive('Casino')->suffix, $affe_id, $sdate, $edate, $by_month )
    );
  }

  function getBetWinPeriodCommon ( $table, $affe_id, $sdate, $edate, $by_month = false ) {

    if ( $by_month === true ) {
      $group1 = ', SUM(amount) AS month_total, MONTH(created_at) AS month_num, SUM(op_fee) AS opfee_total';
      $group2 = 'GROUP BY month_num';
      $group_by = 'month_num';
    } else if ( $by_month == 'day' ) {
      $group1 = ', SUM(amount) AS day_total, DAYOFMONTH(created_at) AS day_num, DATE(created_at) AS day_date, SUM(op_fee) AS opfee_total';
      $group2 = 'GROUP BY day_num';
      $group_by = 'day_num';
    } else if ( $by_month == 'all' ) {
      $group1 = 'SUM(amount) AS amount, SUM(op_fee) AS opfee_total';
      $group_by = false;
    } else if ( empty( $by_month ) )
      $group_by = false;

    $where = empty( $affe_id ) ? "" : "WHERE affe_id = $affe_id";

    $from = $by_month == 'all' ? $group1 : "$table.* $group1";

    if ( empty( phive('Casino')->suffix ) ) {
      $sql = "SELECT $from
        FROM $table
        WHERE `created_at` >= '$sdate'
        AND `created_at` <= '$edate'
        AND user_id IN(SELECT id FROM users $where) $group2";
    } else
      $sql = "SELECT $from FROM $table WHERE user_id IN(SELECT id FROM users $where) $group2";

    return phive( "SQL" )->loadArray( $sql, 'ASSOC', $group_by );
  }
    */
    
  function groupByMonth ( $col, $field = 'register_date' ) {

    $rarr = array();
    foreach ( $col as $el ) {
      $month = date( 'm', strtotime( $el[ $field ] ) );
      $rarr[ $month ][] = $el;
    }

    return $rarr;
  }

  function isCasinoAffiliate ( $aff_id ) {
    $info = $this->getInfo( $aff_id );
    return empty( $info ) ? false : true;
  }

  function untagCasinoPlayer ( $user_id ) {
    return phive( 'UserHandler' )->getUser( $user_id )->setAttr( 'affe_id', 0 );
  }

  function relationFromAffUsername($aff_username, $user_id){
    $this->untagCasinoPlayer( $user_id );
    return $this->createRelation( phive( "UserHandler" )->getIdByUsername( $aff_username ), $user_id, false );
  }

  function relationFromBonusCode ( $bonus_code, $user_id ) {

    $affe_id = $this->getAffIdByBonusCode( $bonus_code );

    if ( empty( $affe_id ) )
      return false;

    $result = $this->createRelation( $affe_id, $user_id, false );

    return $result;
  }

    // TODO henrik remove
  function validateAffiliate ( $username, $password ) {

    $affe = phive( 'UserHandler' )->simpleValidation( $username, $password );
    if ( empty( $affe ) )
      return false;
    $affe_info = $this->getInfo( $affe->getId() );

    return empty( $affe_info ) ? false : $affe;
  }

  function getAffIdByBonusCode ( $bonus_code ) {

    $bonus_code = phive('SQL')->escape($bonus_code,false);
    return phive( 'SQL' )->getValue( "SELECT affe_id FROM bonus_codes WHERE bonus_code = '$bonus_code'" );
  }

  function getAffByBonusCode ( $bonus_code ) {

    $aff_id = $this->getAffIdByBonusCode( $bonus_code );
    return phive( "SQL" )->loadAssoc( "SELECT * FROM users WHERE id = '$aff_id'" );
  }

  function getAffTrackerUrl ( $step, $user_id = '' ) {

    $aff = $this->getAffiliateUserFromUser( empty( $user_id ) ? $_SESSION['mg_id'] : $user_id );
    if ( empty( $aff ) )
      return '';
    $country = strtolower( cuCountry() );
    $url = phive( "Config" )->getValue( $aff->getUsername() . "-trackers", $country . "-" . $step );
    if ( empty( $url ) )
      $url = phive( "Config" )->getValue( $aff->getUsername() . "-trackers", "default-" . $step );

    return $url;
  }

  function echoPixel ( $step, $user_id = '' ) {

    if ( phive( 'IpBlock' )->getSetting( 'test' ) === true )
      return;
    $url = $this->getAffTrackerUrl( $step, $user_id );
    if ( empty( $url ) )
      return;
    $urls = explode( '||', $url );
    ?>
    <?php foreach ( $urls as $url ): ?>
      <img width="1" height="1" src="<?php echo $url ?>"/>
    <?php endforeach ?>
  <?php }

  function deleteBonusCode ( $id, $aff_id ) {
    $id = intval($id);
    $aff_id = intval($aff_id);
    if ( $aff_id == $_SESSION['mg_id'] )
      phive( 'SQL' )->query( "DELETE FROM bonus_codes WHERE id = $id AND affe_id = $aff_id" );
  }

  function getAffiliateAndBonusCodes ( $where = '' ) {

    $str = "SELECT * FROM users u, bonus_codes bc WHERE u.id = bc.affe_id $where";

    return phive( "SQL" )->loadArray( $str );
  }

  function getBonusCodes ( $aff_id, $as_key = false ) {

    $key = $as_key ? 'bonus_code' : false;
    $aff_id = intval($aff_id);
    return phive( 'SQL' )->loadArray( "SELECT * FROM bonus_codes WHERE affe_id = $aff_id", 'ASSOC', $key );
  }

  function getBonusCode ( $bid ) {

    $bid = intval($bid);
    return phive( 'SQL' )->loadAssoc( "SELECT * FROM bonus_codes WHERE id = $bid" );
  }

  function bonusCodesSelect ( $aff_id, $val_field = 'id' ) {

    $rarr = array();
    foreach ( $this->getBonusCodes( $aff_id ) as $c ) {
      $rarr[ $c[ $val_field ] ] = $c['bonus_code'];
    }

    return $rarr;
  }

  function scanSiteCodes ( $code ) {
    $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
    $code = phive('SQL')->escape($code,false);
    $sql = phive( 'SQL' );
    $vouchers = $sql->loadArray( "SELECT * FROM vouchers WHERE voucher_name = '$code' GROUP BY voucher_name" );
    $reloads = $sql->loadArray( "SELECT * FROM bonus_types WHERE reload_code = '$code' AND brand_id = {$brandId}" );
    $bonuses = $sql->loadArray( "SELECT * FROM bonus_types WHERE bonus_code = '$code' AND brand_id = {$brandId}" );
    $aff_codes = $sql->loadArray( "SELECT * FROM bonus_codes WHERE bonus_code = '$code'" );
      $user_codes = $sql->loadArray( "SELECT * FROM users WHERE bonus_code = '$code'" );
    if ( empty( $vouchers ) && empty( $reloads ) && empty( $bonuses ) && empty( $aff_codes ) && empty($user_codes) )
      return false;

    return array( 'vouchers' => $vouchers, 'reloads' => $reloads, 'bonuses' => $bonuses, 'aff_codes' => $aff_codes );
  }

  function insertBonusCode ( $affe_id, $bonus_code, $description = '' ) {
      $affe_id = intval($affe_id);
      $bonus_code = phive()->rmNonAlphaNums($bonus_code);
      if(empty($bonus_code))
          return false;
      if ( !$this->scanSiteCodes( $bonus_code ) )
          return phive( 'SQL' )->insertArray( 'bonus_codes', array( 'affe_id'     => $affe_id, 'bonus_code' => $bonus_code,
                                                                    'description' => $description
          ) );

      return false;
  }

  function updateBonusCode ( $bid, $affe_id, $bonus_code, $description = '' ) {
      $bonus_code = phive()->rmNonAlphaNums($bonus_code);
      if(empty($bonus_code))
          return false;
      if ( !$this->scanSiteCodes( $bonus_code ) ) {
          if ( $affe_id == $_SESSION['mg_id'] )
              return phive( 'SQL' )->updateArray( 'bonus_codes',
                                                  array( 'bonus_code' => $bonus_code, 'description' => $description ),
                                                  array( 'id' => $bid ) );
      }

      return false;
  }

  function getBonusCodeRegisteredCount ( $bcode, $sdate, $edate, $ver_email = false ) {
    $bcode = phive('SQL')->escape($bcode,false);
    $sdate = phive('SQL')->escape($sdate,false);
    $edate = phive('SQL')->escape($edate,false);
    if ( $ver_email ) {
      $join =
        "INNER JOIN users_settings AS us ON us.user_id = u.id AND us.setting = 'email_code_verified' AND us.value = 'yes'";
    }

    return phive( 'SQL' )->getValue( "SELECT COUNT(*) FROM users u $join WHERE u.bonus_code = '$bcode' AND DATE(u.register_date) >= '$sdate' AND DATE(u.register_date) <= '$edate'" );
  }

  function getAllUsers () {

    return phive( 'SQL' )->loadArray( "SELECT * FROM users WHERE affe_id != 0" );
  }

  function getBonusCodeDeposits ( $affe_id, $sdate = '', $edate = '', $date_type = 'reg', $ver_email = true ) {
    $edate = phive('SQL')->escape($edate,false);
    $sdate = phive('SQL')->escape($sdate,false);
    $affed_id = intval($affe_id);
    $where_extra = '';

    if ( !empty( $sdate ) && !empty( $edate ) ) {
      if ( $date_type == 'reg' )
        $where_extra = "AND DATE(u.register_date) >= '$sdate' AND DATE(u.register_date) <= '$edate'";
      else if ( $date_type == 'dep' )
        $where_extra = "AND cs.timestamp >= '$sdate 00:00:00' AND cs.timestamp <= '$edate 24:00:00'";
    }

    if ( $ver_email ) {
      $join =
        "LEFT JOIN users_settings AS us ON us.user_id = u.id AND us.setting = 'email_code_verified' AND us.value = 'yes'";
      $select = ', us.setting AS ver_email';
    }

    $str = "SELECT u . * , count( cs.user_id ) AS dep_num, cs.timestamp$select FROM users u
        $join
        LEFT JOIN cash_transactions AS cs ON cs.user_id = u.id AND cs.transactiontype = 3
        WHERE u.bonus_code IN (SELECT bonus_code FROM bonus_codes WHERE affe_id = $affe_id)
        $where_extra
        GROUP BY u.id ORDER BY dep_num DESC";

    return phive( 'SQL' )->loadArray( $str );
  }
}
