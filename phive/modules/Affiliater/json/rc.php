<?php
require_once __DIR__ . '/../../../api.php';

$rc = json_decode( file_get_contents( "php://input" ), true );

if ( $rc[ 'key' ] != phive( 'Affiliater' )->getSetting( 'rc_key' ) )
  die( 'nok' );


if (! in_array(remIp(), phive( 'Affiliater' )->getSetting('whitelisted_ips'))) {
    die('ip blocked');
}

call_user_func( $rc[ 'action' ] );
die();

function send_users_daily_stats () {
  global $rc;
  $sql = phive( 'SQL' );
  $result = array();
  $uds = $sql->shs('merge', '', null, 'users')->loadArray( "SELECT uds.*, uds.id as uds_id, u.bonus_code FROM users_daily_stats uds
                            INNER JOIN users u
                            ON u.id = uds.user_id
                            WHERE date = '{$rc['params']['date']}'
                            AND bonus_code != ''" );

  $result[ 'uds' ] = $uds;
  echo json_encode( $result );
}

function check_signups () {
    global $rc;
    $str = "
        SELECT * FROM users
        WHERE DATE(register_date) = '{$rc['params']['date']}'
        AND bonus_code != ''";
    phive()->dumpTbl('rc_check_signups', $str);
    $result = phive( 'SQL' )->shs('merge', '', null, 'users')->loadArray($str);
    echo json_encode( $result );
}

function check_username () {
  global $rc;
  $username = phive( 'SQL' )->shs('merge', '', null, 'users')->loadAssoc( "SELECT username FROM users WHERE username = '{$rc['params']['username']}'" );
  echo json_encode( $username );
}


// TODO this can most probably be removed?
function check_transaction () {
  global $rc;
  $transaction = phive( 'SQL' )->loadAssoc( "SELECT * FROM cash_transactions WHERE transactiontype = 5 AND entry_id = {$rc['params']['unique']}" );
  $result = false;
  if ( !empty( $transaction ) )
    $result = true;
  echo json_encode( $result );
}

function check_first_deposits () {
    global $rc;
    
    $str = "SELECT fd.*, u.bonus_code FROM first_deposits fd
                INNER JOIN users u
                ON u.id = fd.user_id
            WHERE fd.timestamp BETWEEN '{$rc['params']['sstamp']}' AND '{$rc['params']['estamp']}'
                AND u.bonus_code != ''";
    
    $result = phive( 'SQL' )->shs('merge', '', null, 'first_deposits')->loadArray( $str );
    
    if(empty($result))
        phive()->dumpTbl('pr_rc_check_first_deposits', $str);
    
    echo json_encode( $result );
}

function check_deposits () {
  global $rc;
  $str = "SELECT d.*, u.bonus_code FROM deposits d
          INNER JOIN users u ON u.id = d.user_id
          WHERE d.timestamp BETWEEN '{$rc['params']['sstamp']}' AND '{$rc['params']['estamp']}'
          AND u.bonus_code != ''";
  $result = phive( 'SQL' )->shs('merge', '', null, 'deposits')->loadArray($str);
  echo json_encode( $result );
}

function check_withdrawals () {
  global $rc;
  $str = "SELECT p.*, u.bonus_code FROM pending_withdrawals p
          INNER JOIN users u ON u.id = p.user_id
          WHERE p.timestamp BETWEEN '{$rc['params']['sstamp']}' AND '{$rc['params']['estamp']}'
          AND u.bonus_code != ''";
  $result = phive( 'SQL' )->shs('merge', '', null, 'pending_withdrawals')->loadArray($str);
  echo json_encode( $result );
}

function check_logins () {
  global $rc;
  $str = "SELECT l.*, u.bonus_code FROM users_sessions l
          INNER JOIN users u
          ON u.id = l.user_id
          WHERE l.created_at BETWEEN '{$rc['params']['sstamp']}' AND '{$rc['params']['estamp']}'
          AND u.bonus_code != ''";
  phive()->dumpTbl('check_logins', $str);
  $result = phive( 'SQL' )->shs('merge', '', null, 'users_sessions')->loadArray( $str );
  echo json_encode( $result );
}

function get_balance_from_stats() {
    global $rc;
    $str = "
        SELECT *
        FROM users_daily_balance_stats
        WHERE date = '{$rc['params']['date']}'
        AND source = 1
    ";
    $result = phive( 'SQL' )->loadArray($str);
    echo json_encode($result);
}

/**
* SQL query for videoslots to get self-excluded users to partnerroom
*
*/
function check_self_exclusion ()
{
    $str = "SELECT 
                us.user_id
            FROM users_settings us
                INNER JOIN users u ON u.id = us.user_id and bonus_code != ''
            WHERE
                setting IN ('excluded-date' , 'lock-date', 'profile-lock')
                GROUP BY user_id;";
    $result = phive( 'SQL' )->shs('merge', '', null, 'users_settings')->loadArray( $str );
    echo json_encode( $result );
}

/**
 *
 */
function check_low_wager_fraud ()
{
    global $rc;
    $str = "SELECT 
                user_id, created_at
            FROM
                users_settings
            WHERE
                setting = 'loww-fraud-flag'
                AND value = 1
                AND created_at BETWEEN '{$rc['params']['sstamp']}' AND '{$rc['params']['estamp']}'
                GROUP BY user_id;";
    $result = phive( 'SQL' )->shs('merge', '', null, 'users_settings')->loadArray( $str );
    echo json_encode( $result );
}