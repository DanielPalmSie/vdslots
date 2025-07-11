<?php
require_once __DIR__ . '/Affiliater.php';


class PRDistributerAffiliater extends Affiliater {

  function __construct () {

  }

  function phAliases () { return array( 'PRDistAffiliater' ); }

    function doAll ( $message ) {
        $result = array();
        foreach ( phive( 'Affiliater' )->getSetting( 'machines' ) as $machine_k => $machine_v )
            $result[ $machine_k ] = json_decode( phive()->post( $machine_v, json_encode( $message ) ), true );
        return $result;
    }

  function doOnly ( $message, $machine ) {
    $result = array();
    $result[ $machine ] = json_decode( phive()->post( phive( 'Affiliater' )->getSetting( 'machines' )[ $machine ], json_encode( $message ), 'application/json', '', 'pr_ext' ), true );
    return $result;
  }

    function getCheckTimeDefaults($date = null, $sstamp = null, $estamp = null){
        return [
            empty($date)   ? phive()->today()           : $date,
            empty($sstamp) ? phive()->hisMod('-1 hour') : $sstamp,
            empty($estamp) ? phive()->hisNow()          : $estamp,
        ];
    }

    function getBaseMsg($action, $date, $sstamp, $estamp){
        return [
            'key'    => phive( 'Affiliater' )->getSetting( 'rc_key' ),
            'action' => $action,
            'params' => [
                'date'       => $date,
                'sstamp'     => $sstamp,
                'estamp'     => $estamp
            ]
        ];
    }

    function checkSignups ( $machine, $date = null, $sstamp = null, $estamp = null ) {
        list($date, $sstamp, $estamp) = $this->getCheckTimeDefaults($date, $sstamp, $estamp);
        $registrars                   = phive( 'UserHandler' )->getAllUsersIDRegistrar( $machine, $date );
        $message                      = $this->getBaseMsg('check_signups', $date, $sstamp, $estamp);
        $result                       = $this->doOnly( $message, $machine );
        if ( !empty( $result ) ) {
            foreach ( $result as $machine => $data ) {
                foreach ( $data as $item ) {
                    if ( !in_array( $item[ 'id' ], array_column( $registrars[ $machine ], 'uid' ) ) ) {
                        $row = array(
                            'product'    => $machine,
                            'uid'        => $item[ 'id' ],
                            'bonus_code' => $item[ 'bonus_code' ],
                            'country'    => $item[ 'country' ],
                            'currency'   => $item[ 'currency' ],
                            'date'       => $date,
                        );
                        phive( 'SQL' )->save( 'pixel_registrar', $row );
                    }
                }
            }
            return true;
        }
        return false;
    }

    function checkFirstDeposits ( $machine, $date = null, $sstamp = null, $estamp = null ) {
        list($date, $sstamp, $estamp) = $this->getCheckTimeDefaults($date, $sstamp, $estamp);
        $first_deposit                = phive( 'UserHandler' )->getFirstDeposits( $date, $date, $machine );
        $message                      = $this->getBaseMsg('check_first_deposits', $date, $sstamp, $estamp);
        $result                       = $this->doOnly( $message, $machine );

        if ( !empty( $result ) ) {
            foreach ( $result[ $machine ] as $data ) {

                if ( !in_array( $data[ 'user_id' ], array_column( $first_deposit, 'uid' ) ) ) {
                    $row = [
                        'product'    => $machine,
                        'uid'        => $data[ 'user_id' ],
                        'amount'     => $data[ 'amount' ],
                        'currency'   => $data[ 'currency' ],
                        'bonus_code' => $data[ 'bonus_code' ],
                        'date'       => $data[ 'timestamp' ]
                    ];
                    phive( 'SQL' )->save( 'pixel_first_deposit', $row );
                }
            }
            return true;
        }
        return false;
    }

    function checkDeposits ( $machine, $date = null, $sstamp = null, $estamp = null ) {
        list($date, $sstamp, $estamp) = $this->getCheckTimeDefaults($date, $sstamp, $estamp);
        $message                      = $this->getBaseMsg('check_deposits', $date, $sstamp, $estamp);
        $result                       = $this->doOnly( $message, $machine );
        if ( !empty( $result ) ) {
            foreach ( $result[ $machine ] as $data ) {
                $row = [
                    'product'    => $machine,
                    'uid'        => $data[ 'user_id' ],
                    'amount'     => $data[ 'amount' ],
                    'count'      => 1,
                    'currency'   => $data[ 'currency' ],
                    'bonus_code' => $data[ 'bonus_code' ],
                    'date'       => $data['timestamp']
                ];

                phive( 'SQL' )->save( 'pixel_deposit', $row );
            }
            return true;
        }
        return false;
    }

    function checkWithdrawals ( $machine, $date = null, $sstamp = null, $estamp = null ) {
        list($date, $sstamp, $estamp) = $this->getCheckTimeDefaults($date, $sstamp, $estamp);
        $message                      = $this->getBaseMsg('check_withdrawals', $date, $sstamp, $estamp);
        $result                       = $this->doOnly($message, $machine );
        if ( !empty( $result ) ) {
            foreach ( $result[ $machine ] as $data ) {
                $row = [
                    'product'    => $machine,
                    'uid'        => $data[ 'user_id' ],
                    'amount'     => $data[ 'amount' ],
                    'pay_type'   => $data[ 'payment_method' ],
                    'count'      => 1,
                    'currency'   => $data[ 'currency' ],
                    'bonus_code' => $data[ 'bonus_code' ],
                    'date'       => $data[ 'created_at' ]
                ];
                phive( 'SQL' )->save( 'pixel_withdrawals', $row );
            }
            return true;
        }
        return false;
    }

    function checkLogins ( $machine, $date = null, $sstamp = null, $estamp = null ) {
        list($date, $sstamp, $estamp) = $this->getCheckTimeDefaults($date, $sstamp, $estamp);
        $message                      = $this->getBaseMsg('check_logins', $date, $sstamp, $estamp);
        $result                       = $this->doOnly($message, $machine );
        if ( !empty( $result ) ) {
            foreach ( $result[ $machine ] as $data ) {
                $row = [
                    'product'    => $machine,
                    'uid'        => $data[ 'user_id' ],
                    'bonus_code' => $data[ 'bonus_code' ],
                    'date'       => $data['created_at']
                ];
                phive( 'SQL' )->save( 'pixel_user_login', $row );
            }
            return true;
        }
        return false;
    }

  function getUsersDailyStats ( $machine, $date = null ) {

    if ( $date == null )
      $date = date( 'Y-m-d' );

    $columns = array(
      'uds_id', 'username', 'affe_id', 'firstname', 'user_id', 'aff_rate', 'lastname', 'bets', 'wins',
      'deposits', 'withdrawals', 'rewards', 'fails', 'gross', 'op_fee', 'bank_fee', 'aff_fee', 'site_rev',
      'date', 'before_deal', 'bank_deductions', 'jp_contrib', 'real_aff_fee', 'site_prof', 'chargebacks',
      'transfer_fees', 'gen_loyalty', 'paid_loyalty', 'ndeposits', 'nwithdrawals', 'nbusts', 'currency',
      'frb_wins', 'jp_fee', 'frb_ded', 'tax', 'frb_cost', 'country', 'cpa_fee', 'product', 'bonus_code'
    );

    $message[ 'key' ] = phive( 'Affiliater' )->getSetting( 'rc_key' );
    $message[ 'action' ] = 'send_users_daily_stats';
    $message[ 'params' ] = array(
      'date' => $date
    );

      $result = $this->doOnly( $message, $machine );

    if ( !empty( $result ) ) {

      phive( 'SQL' )->delete( 'users_daily_stats', "date = '$date'" );

      foreach ( $result as $machine => $data ) {

        foreach ( $data[ 'uds' ] as $row ) {

          $uds = array();

          foreach ( $columns as $column )
            $uds[ $column ] = $row[ $column ];

          $uds[ 'product' ] = $machine;

          phive( 'SQL' )->save( 'users_daily_stats', $uds );
        }
      }

      return true;
    }
    return false;
  }

  function updateUserDailyStats ( $machine, $update ) {

    $message[ 'key' ] = phive( 'Affiliater' )->getSetting( 'rc_key' );
    $message[ 'action' ] = 'update_user_daily_stats';
    $message[ 'params' ] = array(
      'update' => $update
    );

    return $this->doOnly( $message, $machine );
  }

  function setUserCampaign ( $machine, $campaign, $username ) {

    $message[ 'key' ] = phive( 'Affiliater' )->getSetting( 'rc_key' );
    $message[ 'action' ] = 'update_user_campaign';
    $message[ 'params' ] = array(
      'campaign' => $campaign,
      'username' => $username
    );

    return $this->doOnly( $message, $machine );
  }

  function transferCashBalanceToProduct ( $machine, $username, $amount, $currency, $unique_id ) {

    $message[ 'key' ] = phive( 'Affiliater' )->getSetting( 'rc_key' );
    $message[ 'action' ] = 'update_user_balance';
    $message[ 'params' ] = array(
      'username' => $username,
      'amount'   => $amount,
      'currency' => $currency,
      'unique'   => $unique_id
    );

    return $this->doOnly( $message, $machine );
  }

  function checkUsername ( $machine, $username ) {

    $message[ 'key' ] = phive( 'Affiliater' )->getSetting( 'rc_key' );
    $message[ 'action' ] = 'check_username';
    $message[ 'params' ] = array(
      'username' => $username
    );

    return $this->doOnly( $message, $machine );
  }

  function checkTransaction ( $machine, $unique_id ) {

    $message[ 'key' ] = phive( 'Affiliater' )->getSetting( 'rc_key' );
    $message[ 'action' ] = 'check_transaction';
    $message[ 'params' ] = array(
      'unique' => $unique_id
    );

    return $this->doOnly( $message, $machine );
  }

  function test ( $machine ) {

    return json_decode( phive()->post( phive( 'Affiliater' )->getSetting( 'machines' )[ $machine ], json_encode( '{"hello": "hi"}' ) ), true );
  }
}
