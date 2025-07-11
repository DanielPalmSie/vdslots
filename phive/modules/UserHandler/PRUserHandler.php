<?php

// TODO henrik remove this, PR Phive is its own repo.

require_once __DIR__ . '/UserHandler.php';
require_once __DIR__ . '/../Former/Validator.php';

class PRUserHandler extends UserHandler {

  protected $sql = null;

  function __construct () {

    $this->sql = phive( 'SQL' );
  }

  function phAliases () { return array( 'UserHandler' ); }

    function getAttr($attr, $where){
        return $this->sql->getValue("SELECT $attr FROM users WHERE $where");
    }
    
  function validateSetup ( $validators ) {

    $valid_rules = array();

    foreach ( $validators as $id_field => $value_field ) {

      $conf = array();

      foreach ( $value_field[ 0 ] as $id_rule => $value_rule ) {

        if ( is_array( $value_rule ) ) {

          $conf2 = array();

          foreach ( $value_rule as $id_rule2 => $value_rule2 ) {

            if ( is_array( $value_rule2 ) ) {

              $conf3 = array();

              foreach ( $value_rule2 as $id_rule3 => $value_rule3 ) {

                $conf3[ $id_rule3 ] = $value_rule3;
              }

              $conf2[ $id_rule2 ] = $conf3;
            }
            else {
              $conf2[ $id_rule2 ] = $value_rule2;
            }
          }

          $conf[ $id_rule ] = $conf2;
        }
        else {
          $conf[ $id_rule ] = $value_rule;
        }
      }

      $valid_rules[ $id_field ] = $conf;
    }

    $valid_messages = array();
    foreach ( $validators as $id_field => $value_field ) {

      $msgs = array();

      foreach ( $value_field[ 1 ] as $id_msg => $value_msg ) {

        $msgs[ $id_msg ] = $value_msg;
      }

      $valid_messages[ $id_field ] = $msgs;
    }

    return array( $valid_rules, $valid_messages );
  }

  public function checkIfAdmin () {

    if ( cu()->getAttribute( 'username' ) == 'admin' )
      return true;
    else
      phive( 'Redirect' )->to( '/' );
  }

  public function getCompanyID ( $uid = null ) {

    if ( !is_null( $uid ) ) {
      return $this->getCompanyIDByUserID( $uid );
    }else {
        $cur_user = cu();
        if(empty($cur_user)){
            phive('Logger')->logTrace();
            return 0;
        }
        return $cur_user->getAttribute( 'company_id' );
    }
  }

  public function getCompanies ( $name = null ) {

    $where = '';

    if ( !empty( $name ) )
      $where = "WHERE name = '%$name%";

    return $this->sql->loadArray( "SELECT * FROM companies $where" );
  }

  public function getUserDetailsByUsername ( $username ) {

    return $this->sql->loadAssoc( "SELECT * FROM users WHERE username = '$username'" );
  }

  public function getUserByID ( $uid ) {
    $uid = intval($uid);
    return $this->sql->loadAssoc( "SELECT * FROM users WHERE id = $uid" );
  }

  public function updateUserResetPass ( $username, $config = 1 ) {

    return $this->sql->updateArray( 'users', [ 'reset_pass' => $config ], "username = '$username'" );
  }

  public function processForgotPassword ( $dob, $username ) {

    $errors = array();

    $user_dob = $this->getUserDetailByKey( 'dob', 'username', $username );

    if ( !empty( $user_dob ) ) {

      if ( $user_dob == $dob )
        $this->resetPassword( $username );
      else
        $errors[] = 'fp.dob.mismatch';
    }
    else
      $errors[] = 'fp.username.notfound';

    return $errors;
  }

  public function resetPassword ( $username ) {

    $this->updateUserResetPass( $username );
    $user = $this->getUserByUsername( $username );

    if ( !empty( $user ) ) {

      $password = phive()->randCode();
      $user->setPassword( $password, true );
      $replacers = phive( 'UserHandler' )->getDefaultReplacers( $user );
      $replacers[ '__PASSWORD__' ] = $password;
      $replacers[ '__FIRSTNAME__' ] = $user->getAttribute( 'firstname' );
      $this->sendMailPR( "resetpassword", $user, $replacers );

      return true;
    }

    return false;
  }

  public function getUserByEmail ( $email ) {

    return phive( 'SQL' )->loadAssoc( "SELECT * FROM users WHERE company_id = ( SELECT company_id FROM emails WHERE email = '$email' )" );
  }

  public function getCompanyByID ( $cid ) {
    $cid = intval($cid);
    return phive( 'SQL' )->loadAssoc( "SELECT * FROM companies WHERE company_id = $cid" );
  }

  public function getCompanyRegDate ( $uid = null ) {

    $date = phive( 'SQL' )->getValue( "SELECT register_date from companies where company_id = {$this->getCompanyID($uid)}" );

    $date = explode( '-', $date );

    return array( 'year' => $date[ 0 ], 'month' => $date[ 1 ], 'day' => $date[ 2 ] );
  }

    public function getCompanyIDByUserID ( $uid ) {
        $uid = intval($uid);
        return phive( 'SQL' )->getValue( "SELECT company_id from users where id = $uid" );
    }

  public function companyAttr ( $field, $uid = null ) {

    return phive( 'SQL' )->getValue( "SELECT $field from companies where company_id = {$this->getCompanyID( $uid )}" );
  }

  public function countCompany ( $field, $value, $where = '' ) {

    return phive( 'SQL' )->getValue( "SELECT COUNT(*) FROM companies WHERE $field = '$value'$where" );
  }

  public function countRegistrationIP ( $ip ) {

    return phive( 'SQL' )->getValue( "SELECT COUNT(*) FROM users WHERE reg_ip = '$ip'" );
  }

  public function getCompanyManager ( $cid ) {
    $cid = intval($cid);
    return phive( 'SQL' )->loadAssoc( "SELECT * FROM users WHERE company_id = $cid AND id = manager_id" );
  }

  public function getMarketSourceGroups ( $uid ) {

    return phive( 'SQL' )->loadArray( "SELECT * from market_source_group where company_id = '{$this->getCompanyID($uid )}'" );
  }

  public function getMarketSourceGroup ( $id ) {
    $id = intval($id);
    return phive( 'SQL' )->loadAssoc( "SELECT * from market_source_group where mrk_src_grp_id = $id" );
  }

  public function getMarketSourceGroupFieldsWhere ( $fields, $where ) {

    return phive( 'SQL' )->loadArray( "SELECT $fields from market_source_group where $where" );
  }

  public function getMarketSource ( $mid ) {
    $mid = intval($mid);
    return phive( 'SQL' )->loadAssoc( "SELECT * from market_source where mrk_src_id = $mid" );
  }

  public function getMarketSources ( $uid = null ) {

    return phive( 'SQL' )->loadArray( "SELECT * FROM market_source WHERE company_id = '{$this->getCompanyID( $uid )}'" );
  }

  public function getMarketSourcesByRewardPlan ( $rid, $uid = null, $compare = '=', $deal = null ) {

    $deal_where = '';

    if ( !empty( $deal ) )
      $deal_where = " AND plan = $deal";

    return phive( 'SQL' )->loadArray( "SELECT * FROM market_source WHERE reward_id $compare $rid AND company_id = '{$this->getCompanyID( $uid )}' $deal_where" );
  }

  public function getMarketSourcesByGroups ( $gids ) {

    return $this->sql->loadArray( "SELECT * FROM market_source
                                  WHERE mrk_src_grp_id IN ($gids)" );
  }

  public function getMarketSourceByID ( $mid ) {
    $mid = intval($mid);
    return $this->sql->loadAssoc( "SELECT * FROM market_source WHERE mrk_src_id = " . $mid );
  }

  public function getMarketSourceByBonusCode ( $bonus ) {

    return $this->sql->loadAssoc( "SELECT * FROM market_source WHERE bonus_code = '$bonus'" );
  }

  public function getMarketSourceTypes () {

    return $this->sql->loadArray( "SELECT * FROM market_source_types" );
  }

  public function getMarketSourceTypeByID ( $tid ) {
    $tid = intval($tid);
    return $this->sql->loadAssoc( "SELECT * FROM market_source_types WHERE mrk_src_typ_id = " . $tid );
  }

  public function getMarketSourceCategories () {

    return $this->sql->loadArray( "SELECT * FROM market_source_categories" );
  }

  public function getMarketSourceCategoryByID ( $cid ) {
    $cid = intval($cid);
    return $this->sql->loadAssoc( "SELECT * FROM market_source_categories WHERE mrk_src_cat_id = " . $cid );
  }

  public function getMarketSourcebyCampaignExtID ( $ext_id ) {

    return $this->sql->loadAssoc( "SELECT ms.* FROM market_source ms
                                    INNER JOIN campaigns c
                                    ON ms.mrk_src_id = c.ms_id
                                    WHERE c.ext_id = '$ext_id'" );
  }

  public function getMarketSourcebyCampaign ( $campaign ) {

    return $this->sql->loadAssoc( "SELECT ms.* FROM market_source ms
                                    INNER JOIN campaigns c
                                    ON ms.mrk_src_id = c.ms_id
                                    WHERE c.name = '$campaign'" );
  }

  public function getMarketSourceByFilters ( $filters ) {

    return $this->sql->loadArray( "SELECT * FROM market_source
                                  WHERE mrk_src_id IN ({$filters['mrksrc']})
                                  AND mrk_src_typ_id IN ({$filters['type']})
                                  AND mrk_src_cat_id IN ({$filters['category']})" );
  }

  public function getUserDetailByKey ( $column, $key, $value ) {

    return $this->sql->getValue( "SELECT $column FROM users WHERE $key = '$value'" );
  }

  public function getUsersByDetail ( $col, $detail, $like = false, $extra = '' ) {

    if ( $like )
      $where = "$col LIKE '%$detail%'";
    else
      $where = "$col = $detail";

    if ( !empty( $extra ) )
      $where .= " AND $extra";

    return $this->sql->loadArray( "SELECT * FROM users WHERE $where" );
  }

  public function getCompanyByDetail ( $col, $detail, $like = false, $extra = '' ) {

    if ( $like )
      $where = "$col LIKE '%$detail%'";
    else
      $where = "$col = $detail";

    if ( !empty( $extra ) )
      $where .= " AND $extra";

    return $this->sql->loadArray( "SELECT * FROM companies WHERE $where" );
  }

  public function getManagers ( $username = null ) {

    $where = '';

    if ( !empty( $username ) )
      $where = " AND username LIKE '%$username%'";

    return $this->sql->loadArray( "SELECT * FROM users WHERE id = manager_id $where" );
  }

  public function getUserManager ( $user_id = null ) {

    $user_id = !empty( $user_id ) ? $user_id : $this->getAttr( 'id' );

    return $this->sql->getValue( "SELECT manager_id FROM users WHERE id = $user_id" );
  }

  public function getUserCompany ( $uid = null ) {
    $uid = intval($uid);
    return $this->sql->loadAssoc( "SELECT * FROM companies WHERE company_id = {$this->getCompanyID( $uid )}" );
  }

  public function getUsersByCompany ( $uid = null ) {
    $uid = intval($uid);
    return $this->sql->loadArray( "SELECT * FROM users WHERE company_id = {$this->getCompanyID( $uid )}" );
  }

  public function getCompanyIDByKey ( $identifier, $table, $key ) {

    return $this->sql->getValue( "SELECT company_id FROM $table WHERE $key = '$identifier'" );
  }

  public function checkDataWithCompany ( $identifier, $table, $key ) {

    if ( $this->getCompanyIDByKey( $identifier, $table, $key ) == $this->getCompanyID( $_SESSION[ 'user' ] ) )
      return true;
    else return false;
  }

  public function getUserByCompany ( $cid ) {

    return $this->sql->loadAssoc( "
                SELECT *
                FROM users u
                INNER JOIN companies c
                ON c.company_id = u.company_id
                INNER JOIN emails e
                ON c.company_id = e.company_id
                WHERE u.company_id = $cid
                AND u.id = u.id
                AND e.admin = 1" );
  }

  public function getUsersCount () {

    return $this->sql->loadAssoc( "SELECT COUNT(*) AS count FROM users" )[ 'count' ];
  }

  public function populateJQGrid ( $sidx, $sord, $start, $limit ) {
    $start = intval($start);
    $limit = intval($limit);
    return $this->sql->loadArray( "SELECT * FROM users ORDER BY  $sidx $sord LIMIT $start , $limit" );
  }

  public function checkIfManager ( $uid = null ) {

    $uid = !empty( $uid ) ? $uid : $this->getAttr( 'id' );
    $uid = intval($uid);
    $result = $this->sql->loadArray( "SELECT COUNT(*) as count FROM users WHERE manager_id = {$uid}" );

    return $result[ 0 ][ 'count' ] != 0 ? true : false;
  }

  public function getRewardPlans () {

    return $this->sql->loadArray( "SELECT * FROM reward_plans" );
  }

  public function getCompanyRewardPlans ( $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM  companies_reward_plans crp
                                          INNER JOIN reward_plans rp
                                          ON crp.reward_plans_id = rp.reward_plans_id
                                          WHERE company_id = '{$this->getCompanyID( $uid )}'" );
  }

  public function getCompanyRewardPlanByID ( $crpid ) {
    $crpid = intval($crpid);
    return $this->sql->loadAssoc( "SELECT * FROM companies_reward_plans WHERE companies_reward_plans = $crpid" );
  }

  public function getRewardPlanTableByID ( $rid ) {

    //TODO use an assoc array for dispatch of logic instead of if else
    if ( $rid == 1 )
      return 'affiliate_revshare_rates';
    else if ( $rid == 2 )
      return 'affiliate_hybrid_rates';
    else if ( $rid == 3 )
      return 'affiliate_cpa_rates';
  }

  public function getRewardPlanByID ( $rid ) {
    $rid = intval($rid);
    return $this->sql->loadAssoc( "SELECT name FROM reward_plans WHERE reward_plans_id = $rid" );
  }

  public function getAffiliateDefaultRates ( $rid ) {

    //TODO use an assoc array for dispatch of logic instead of if else
    if ( $rid == 1 )
      return $this->sql->loadArray( "SELECT * FROM affiliate_revshare_rates WHERE base = 1" );
    else if ( $rid == 2 )
      return $this->sql->loadArray( "SELECT * FROM affiliate_hybrid_rates WHERE base = 1" );
    else if ( $rid == 3 )
      return $this->sql->loadArray( "SELECT * FROM affiliate_cpa_rates WHERE base = 1" );
    else if ( $rid == 4 )
      return $this->sql->loadArray( "SELECT * FROM sub_affiliate_revshare_rates WHERE base = 1" );
    else if ( $rid == 5 )
      return $this->sql->loadArray( "SELECT * FROM affiliate_ndc_rates WHERE base = 1" );
  }

    public function getAffiliateRates ( $config, $cid = null, $uid = null, $plan = null, $plan_group = false ) {

        $plan_where = '';
        $plan_group_by = '';

        if ( !empty( $plan ) ) {
            if ( $plan == 'new' )
                $plan = count( $this->getAffiliateRates( $config, $cid, null, null, true ) ) + 1;

            $plan_where = " AND tbl2.plan = $plan";
        }

        if ( !empty( $plan_group ) )
            $plan_group_by = " GROUP BY plan";

        $cid = !is_null( $cid ) ? $cid : $this->getCompanyID( $uid );

        $str = "SELECT * FROM {$config['table1']} tbl1
                    INNER JOIN {$config['table2']} tbl2
                    ON tbl1.id = tbl2.{$config['rate_id']}
                WHERE tbl2.company_id = '$cid'
                    $plan_where
                    $plan_group_by";
        
        return $this->sql->loadArray($str);
    }

  public function getAffiliateRateLevels ( $table, $base = null ) {

    $where = '';

    if ( $base != null )
      $where = " WHERE base = $base";

    return $this->sql->loadArray( "SELECT * FROM $table$where" );
  }

  public function updateAffiliateRatesDefaults ( $config ) {

    $levels = $this->getAffiliateDefaultRates( $config[ 'reward_id' ] );

    foreach ( $config[ 'rows' ] as $key => $data ) {

      $rate = $this->sql->loadAssoc( "SELECT * FROM {$config['table1']} WHERE id = $key" );

      if ( !empty( $key ) ) {

        if ( !empty( $rate ) ) {

          if ( !empty( array_diff( $data, $rate ) ) )
            $this->sql->updateArray( $config[ 'table1' ], $data, "id = $key" );
        }
        else {

          $this->sql->insertArray( $config[ 'table1' ], $data );
          $new_rate_id = $this->sql->insertBigId();

          $companies = $this->getCompanies();

          foreach ( $companies as $company ) {

            $insert = [
              'company_id'         => $company[ 'company_id' ],
              $config[ 'rate_id' ] => $new_rate_id,
              'plan'               => 1
            ];

            $this->sql->insertArray( $config[ 'table2' ], $insert );
          }
        }
      }

      foreach ( $levels as $index => $level ) {

        if ( $level[ 'id' ] == $key ) {

          unset( $levels[ $index ] );
          break;
        }
      }
    }

    foreach ( $levels as $level ) {

      $level_id = $level[ 'id' ];

      $this->sql->delete( $config[ 'table1' ], "id = $level_id" );
      $this->sql->delete( $config[ 'table2' ], "{$config['rate_id']} = $level_id" );
    }
  }

  public function updateAffiliateRates ( $config, $cid ) {

    $new_company_rate_ids = array();
    $old_rate_ids = array();

    foreach ( $config[ 'rows' ] as $plan => $ladder ) {

      foreach ( $ladder as $key => $data ) {

        if ( !empty( $key ) && $plan != 'new' ) {

          $new_company_rate_ids[] = $key;

          $rate_id = $this->sql->getValue( "SELECT {$config['rate_id']} FROM {$config['table2']} WHERE id = $key" );

          $rate = null;
          $rate_diff = [ ];
          $new_data = [ ];

          if ( $rate_id != false ) {
            $rate_id = intval($rate_id);
            $rate = $this->sql->loadAssoc( "SELECT * FROM {$config['table1']} WHERE id = $rate_id" );
            $rate_diff = $rate;
          }

          foreach ( $data as $key1 => $val ) {

            $new_data[ $key1 ] = (float)str_replace( ',', '', $val );
          }

          foreach ( $rate_diff as $key1 => $val ) {

            if ( !array_key_exists( $key1, $new_data ) )
              unset( $rate_diff[ $key1 ] );
          }

          if ( !empty( array_diff( $new_data, $rate_diff ) ) || !empty( array_diff( $rate_diff, $new_data ) ) ) {

            $levels = $this->getAffiliateRateLevels( $config[ 'table1' ] );

            $insert = true;
            $id = null;
            $id_temp = null;

            foreach ( $levels as $level ) {

              foreach ( $level as $key1 => $val ) {

                if ( $key1 == 'id' )
                  $id_temp = $val;

                if ( !array_key_exists( $key1, $new_data ) && ( $level[ 'base' ] != 1 || $plan == 1 ) )
                  unset( $level[ $key1 ] );
              }

              if ( empty( array_diff( $level, $new_data ) ) && empty( array_diff( $new_data, $level ) ) ) {

                $id = $id_temp;
                $insert = false;
                break;
              }
            }

            if ( $insert ) {

              if ( !empty( $rate ) && $rate[ 'base' ] == 0 ) {

                if ( empty( $this->sql->loadArray( "SELECT * FROM {$config['table2']} WHERE {$config['rate_id']} = $rate_id" ) ) )
                  $this->sql->updateArray( $config[ 'table1' ], $new_data, "id = $rate_id" );
                else {

                  $insert_base = true;

                  if ( $plan == 1 ) {

                    $base_levels = $this->getAffiliateRateLevels( $config[ 'table1' ], '1' );

                    foreach ( $base_levels as $base_level ) {

                      foreach ( $base_level as $key1 => $val ) {

                        if ( $key1 == 'id' )
                          $id_temp = $val;

                        if ( !array_key_exists( $key1, $new_data ) )
                          unset( $base_level[ $key1 ] );
                      }

                      if ( empty( array_diff( $base_level, $new_data ) ) ) {

                        $id = $id_temp;
                        $insert_base = false;
                        break;
                      }
                    }
                  }

                  $old_rate_ids[] = $rate_id;

                  if ( $insert_base ) {

                    $this->sql->insertArray( $config[ 'table1' ], $new_data );
                    $this->sql->updateArray( $config[ 'table2' ], [ $config[ 'rate_id' ] => $this->sql->insertBigId() ], "id = $key" );
                  }
                  else
                    $this->sql->updateArray( $config[ 'table2' ], [ $config[ 'rate_id' ] => $id ], "id = $key" );
                }
              }
              else {

                $this->sql->insertArray( $config[ 'table1' ], $new_data );

                $new_rate_id = $this->sql->insertBigId();

                if ( $rate_id != false )
                  $this->sql->updateArray( $config[ 'table2' ], array( $config[ 'rate_id' ] => $new_rate_id ), "id = $key" );
                else {

                  $this->sql->insertArray( $config[ 'table2' ], array( 'company_id' => $cid, $config[ 'rate_id' ] => $new_rate_id, 'plan' => $plan ) );
                  $new_company_rate_ids[] = $this->sql->insertBigId();
                }
              }
            }
            else {
              $cid = intval($cid);
              $plan = intval($plan);
              $aff_rank = $this->sql->loadArray( "SELECT * FROM {$config['table2']} WHERE company_id = $cid AND {$config['rate_id']} = $id AND plan = $plan" );

              if ( !empty( $aff_rank ) ) {

                foreach ( $aff_rank as $rank )
                  $this->sql->delete( $config[ 'table2' ], "id = {$rank[ 'id' ]}" );
              }

              $this->sql->updateArray( $config[ 'table2' ], array( $config[ 'rate_id' ] => $id ), "id = $key" );
              $rate_id = intval($rate_id);
              if ( empty( $this->sql->loadArray( "SELECT * FROM {$config['table2']} WHERE {$config['rate_id']} = $rate_id" ) ) )
                $this->sql->delete( $config[ 'table1' ], "id = $rate_id" );
            }
          }
        }
        else {

          if ( $plan != '1' )
            $levels = $this->getAffiliateRateLevels( $config[ 'table1' ], '0' );
          else
            $levels = $this->getAffiliateRateLevels( $config[ 'table1' ] );

          if ( $plan == 'new' ) {

            $plans = phive()->arrCol( $this->getAffiliateRates( $config, $cid, null, null, true ), 'plan' );

            if ( !empty( $plans ) )
              $plan = $plans[ count( $plans ) - 1 ] + 1;
            else {

              if ( empty( $this->sql->loadAssoc( "SELECT * FROM companies_reward_plans WHERE company_id = $cid AND reward_plans_id = {$config[ 'reward_id']}" ) ) )
                $this->sql->insertArray( 'companies_reward_plans', [ 'company_id' => $cid, 'reward_plans_id' => $config[ 'reward_id' ] ] );

              $plan = 1;
            }
          }

          foreach ( $data as $new ) {

            $insert = true;
            $id = null;
            $id_temp = null;

            foreach ( $levels as $level ) {

              foreach ( $level as $key => $val ) {

                if ( $key == 'id' )
                  $id_temp = $val;

                $level[ $key ] = (string)$val;

                if ( !array_key_exists( $key, $new ) )
                  unset( $level[ $key ] );
              }

              if ( empty( array_diff( $new, $level ) ) && empty( array_diff( $level, $new ) ) ) {

                $id = $id_temp;
                $insert = false;
                break;
              }
            }

            if ( $insert ) {

              $this->sql->insertArray( $config[ 'table1' ], $new );
              $id = $this->sql->insertBigId();
            }
            $id = intval($id);
            $cid = intval($cid);
            $plan = intval($plan);
            $aff_rank = $this->sql->loadAssoc( "SELECT * FROM {$config['table2']} WHERE {$config['rate_id']} = $id AND company_id = $cid AND plan = $plan" );

            if ( empty( $aff_rank ) ) {

              $this->sql->insertArray( $config[ 'table2' ], array( 'company_id' => $cid, $config[ 'rate_id' ] => $id, 'plan' => $plan ) );
              $new_company_rate_ids[] = $this->sql->insertBigId();
            }
            else
              $new_company_rate_ids[] = $aff_rank[ 'id' ];
          }
        }
      }
    }

    $company_rates = $this->sql->loadArray( "SELECT id, {$config['rate_id']} FROM {$config['table2']} WHERE company_id = $cid" );
    $company_rate_ids = phive()->arrCol( $company_rates, 'id' );

    $company_rates = array_combine( $company_rate_ids, $company_rates );

    foreach ( array_diff( $company_rate_ids, $new_company_rate_ids ) as $company_rate ) {

      $this->sql->delete( $config[ 'table2' ], "id = $company_rate" );

      if ( $this->sql->getValue( "SELECT base FROM {$config['table1']} WHERE id = {$company_rates[ $company_rate ][ $config[ 'rate_id' ] ]}" ) == 0 ) {

        if ( empty( $this->sql->loadArray( "SELECT * FROM {$config['table2']} WHERE {$config['rate_id']} = {$company_rates[ $company_rate ][ $config[ 'rate_id' ] ]}" ) ) )
          $this->sql->delete( $config[ 'table1' ], "id = {$company_rates[ $company_rate ][ $config[ 'rate_id' ] ]}" );
      }
    }

    foreach ( $old_rate_ids as $old_rate_id ) {

      if ( empty( $this->sql->loadArray( "SELECT * FROM {$config[ 'table2']} WHERE {$config['rate_id']} = $old_rate_id" ) ) )
        $this->sql->delete( $config[ 'table1' ], "id = $old_rate_id" );
    }
  }

  function getRatesCommon ( $type, $cid = null, $uid = null, $plan = null, $plan_group = false ) {

    $plural = $type == 'revshare' ? '' : 's';

    $config = [
      'table1'  => "affiliate_{$type}_rates",
      'table2'  => "companies_affiliate_{$type}_rates",
      'rate_id' => "affiliate_{$type}_rate{$plural}_id"
    ];

    return $this->getAffiliateRates( $config, $cid, $uid, $plan, $plan_group );
  }

  public function getAffiliateCPARates ( $cid = null, $uid = null, $plan = null, $plan_group = false ) {
    return $this->getRatesCommon( 'cpa', $cid, $uid, $plan, $plan_group );
  }

  public function getAffiliateHybridRates ( $cid = null, $uid = null, $plan = null, $plan_group = false ) {

    return $this->getRatesCommon( 'hybrid', $cid, $uid, $plan, $plan_group );
  }

  public function getAffiliateRevShareRates ( $cid = null, $uid = null, $plan = null, $plan_group = false ) {

    return $this->getRatesCommon( 'revshare', $cid, $uid, $plan, $plan_group );
  }

  public function getAffiliateNdcRates ( $cid = null, $uid = null, $plan = null, $plan_group = false ) {

    return $this->getRatesCommon( 'ndc', $cid, $uid, $plan, $plan_group );
  }

  public function getSubAffiliateRevShareRates ( $cid = null, $uid = null, $plan = null, $plan_group = false ) {

    $config = [
      'table1'  => 'sub_affiliate_revshare_rates',
      'table2'  => 'companies_sub_affiliate_revshare_rates',
      'rate_id' => 'sub_affiliate_revshare_rate_id'
    ];

    return $this->getAffiliateRates( $config, $cid, $uid, $plan, $plan_group );
  }

  public function updateSubAffiliateRevShareRates ( $rows, $cid = null, $default = false ) {

    $config = [
      'table1'    => 'sub_affiliate_revshare_rates',
      'table2'    => 'companies_sub_affiliate_revshare_rates',
      'rate_id'   => 'sub_affiliate_revshare_rate_id',
      'rows'      => $rows,
      'reward_id' => 4
    ];

    if ( $default )
      return $this->updateAffiliateRatesDefaults( $config );
    else
      return $this->updateAffiliateRates( $config, $cid );
  }

  function getRewardSortCol ( $type ) {

    if ( $type == 'hyb' )
      return [ 'start_amount', 'gift' ];
    if ( $type == 'cpa' )
      return 'gift';

    return 'start_amount';
  }

  function getRewardCol ( $type ) {

    return $type == 'cpa' ? 'gift' : 'start_amount';
  }

  function getRewardIdMap () {

    return [ 'rev' => 1, 'hyb' => 2, 'cpa' => 3, 'sub' => 4, 'ndc' => 5 ];
  }

  function getRewardTypeMap () {

    return array_flip( $this->getRewardIdMap() );
  }

  function getRewardGetMap () {

    return [ 'rev' => 'getAffiliateRevShareRates',
             'hyb' => 'getAffiliateHybridRates',
             'cpa' => 'getAffiliateCPARates',
             'sub' => 'getSubAffiliateRevShareRates',
             'ndc' => 'getAffiliateNdcRates'
    ];
  }

  function getRewardUpdateMap () {

    return [ 'rev' => 'updateAffiliateRevShareRates',
             'hyb' => 'updateAffiliateHybridRates',
             'cpa' => 'updateAffiliateCPARates',
             'sub' => 'updateSubAffiliateRevShareRates',
             'ndc' => 'updateAffiliateNdcRates'
    ];
  }

  function getUpdateRewardFunc ( $type ) {

    return $this->getRewardUpdateMap()[ $type ];
  }

  function getGetRewardFunc ( $type ) {

    return $this->getRewardGetMap()[ $type ];
  }

  function getRewardIdType ( $type ) {

    $map = $this->getRewardIdMap();

    return $map[ $type ];
  }

  public function updateAffiliateRatesCommon ( $type, $reward_id, $rows, $cid = null, $default = false ) {

    $plural = $type == 'revshare' ? '' : 's';

    $config = [
      'table1'    => "affiliate_{$type}_rates",
      'table2'    => "companies_affiliate_{$type}_rates",
      'rate_id'   => "affiliate_{$type}_rate{$plural}_id",
      'rows'      => $rows,
      'reward_id' => $reward_id
    ];

    if ( $default )
      return $this->updateAffiliateRatesDefaults( $config );
    else
      return $this->updateAffiliateRates( $config, $cid );
  }

  public function updateAffiliateCPARates ( $rows, $cid = null, $default = false ) {

    return $this->updateAffiliateRatesCommon( 'cpa', 3, $rows, $cid, $default );
  }

  public function updateAffiliateHybridRates ( $rows, $cid = null, $default = false ) {

    return $this->updateAffiliateRatesCommon( 'hybrid', 2, $rows, $cid, $default );
  }

  public function updateAffiliateRevShareRates ( $rows, $cid = null, $default = false ) {

    return $this->updateAffiliateRatesCommon( 'revshare', 1, $rows, $cid, $default );
  }

  public function updateAffiliateNdcRates ( $rows, $cid = null, $default = false ) {

    return $this->updateAffiliateRatesCommon( 'ndc', 5, $rows, $cid, $default );
  }

  public function getEmails ( $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM emails WHERE company_id = {$this->getCompanyID( $uid )}" );
  }

  public function getAllEmails ( $wildcard = null ) {

    $where_wildcard = '';

    if ( !empty( $wildcard ) )
      $where_wildcard = "WHERE email LIKE '%$wildcard%'";

    return $this->sql->loadArray( "SELECT * FROM emails $where_wildcard" );
  }

  public function getEmailByID ( $eid ) {

    return $this->sql->loadArray( "SELECT * FROM emails WHERE id = '$eid'" );
  }

  public function getCompanyAdminEmail ( $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM emails WHERE admin = 1 AND company_id = {$this->getCompanyID( $uid )}" );
  }

  public function checkCompanyEmailDuplicate ( $email, $update = false, $eid = null ) {

    return $this->sql->loadArray( "SELECT * FROM emails WHERE email = '$email'" . ( $update ? " AND id != {$this->getEmailByID( $eid )[0]['id']}" : '' ) );
  }

  public function checkUsernameDuplicate ( $username, $update = false, $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM users WHERE username = '$username'" . ( $update ? " AND id != $uid" : '' ) );
  }

  public function checkBonusCodeDuplicate ( $bonus_code ) {

    return $this->sql->loadArray( "SELECT * FROM market_source WHERE bonus_code = '$bonus_code'" );
  }

  public function checkCampaignDuplicate ( $campaign ) {

    return $this->sql->loadArray( "SELECT * FROM campaigns WHERE name = '$campaign'" );
  }

  public function getRestrictedIPs ( $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM restricted_ips WHERE company_id = {$this->getCompanyID( $uid ) }" );
  }

  public function getRestrictedIPsByID ( $rid, $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM restricted_ips WHERE restricted_ips_id = $rid AND company_id = {$this->getCompanyID( $uid )}" );
  }

  public function checkRestrictedIP ( $ip, $username, $current = false ) {

    $uid = $this->getUserDetailByKey( 'id', 'username', $username );

    $ips = phive()->arrCol( $this->getRestrictedIPs( $uid ), 'ip' );

    if ( !empty( $ips ) && in_array( $ip, $ips ) )
      return true;
    else if ( empty( $ips ) && !$current )
      return true;
    else
      return false;
  }

  public function getPaymentMethods ( $col = '*' ) {

    return $this->sql->loadArray( "SELECT $col FROM payment_methods" );
  }

  public function getPaymentMethodByID ( $pid ) {

    return $this->sql->loadAssoc( "SELECT * FROM payment_methods WHERE pm_id = " . $pid );
  }

  public function getPaymentMethodBySignature ( $psig ) {

    return $this->sql->loadAssoc( "SELECT * FROM payment_methods WHERE signature = '$psig'" );
  }

  public function getUserBankWire ( $uid = null ) {

    return $this->sql->loadAssoc( "SELECT * FROM bank_wires WHERE company_id = {$this->getCompanyID( $uid )}" );
  }

  public function getUserBankVS ( $uid = null ) {

    return $this->sql->loadAssoc( "SELECT * FROM bank_product WHERE company_id = {$this->getCompanyID( $uid )} AND product = 'videoslots'" );
  }

  public function getProducts () {

    return $this->sql->loadArray( "SELECT * FROM products" );
  }

  public function getUserProducts ( $uid = null ) {

    $company_rewardplans = $this->getCompanyRewardPlans( $uid );
    $company_rewardplans = phive()->arrCol( $company_rewardplans, 'reward_plans_id' );

    $products = $this->getProducts();
    $user_products = null;

    foreach ( $products as $product ) {

      $rps = explode( ',', $product[ 'deals' ] );

      foreach ( $rps as $rp ) {

        if ( in_array( $rp, $company_rewardplans) ) {

          $user_products[] = $product;
          break 1;
        }
      }
    }

    return $user_products;
  }

  public function getProductsByID ( $pid ) {

    return $this->sql->loadAssoc( "SELECT * FROM products WHERE p_id = $pid" );
  }

  public function getProductsBySignature ( $signature ) {

    return $this->sql->loadAssoc( "SELECT * FROM products WHERE signature = '$signature'" );
  }

  public function getCompanyCampaignsByMarketAndReward ( $uid, $mrksrc = null, $rwdplan = null, $campaign = null ) {

    $where_ms = '';
    $where_rp = '';

    if ( !empty( $mrksrc ) )
      $where_ms = " AND ms_id = $mrksrc";

    if ( !empty( $rwdplan ) )
      $where_rp = " AND ms_id IN ( SELECT mrk_src_id FROM market_source
                                    WHERE reward_id = $rwdplan
                                    AND mrk_src_grp_id IN (
                                    SELECT mrk_src_grp_id FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )} ) )";

    if ( !empty( $campaign ) )
      $where_c = " AND id = $campaign";

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE ms_id IN (
                                    SELECT mrk_src_id FROM market_source
                                    WHERE mrk_src_grp_id IN (
                                    SELECT mrk_src_grp_id FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )} ) )
                                    $where_ms
                                    $where_rp
                                    $where_c" );
  }

  public function getCompanyCampaigns ( $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE ms_id IN (
                                    SELECT mrk_src_id FROM market_source
                                    WHERE mrk_src_grp_id IN (
                                    SELECT mrk_src_grp_id FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )} ) )" );
  }

  public function getCompanyCampaignsDate ( $uid = null, $sdate, $edate ) {

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE ms_id IN (
                                    SELECT mrk_src_id FROM market_source
                                    WHERE mrk_src_grp_id IN (
                                    SELECT mrk_src_grp_id FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )} ) )
                                    AND date BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59'" );
  }

    public function getCompanyCampaignsProductWhere ( $uid, $product_where = "!= 'partnerroom'" ) {
        /*
        $sql = "SELECT * FROM campaigns
                                   WHERE ms_id IN (
                                   SELECT mrk_src_id FROM market_source
                                   WHERE mrk_src_grp_id IN (
                                   SELECT mrk_src_grp_id FROM market_source_group
                                   WHERE company_id = {$this->getCompanyID( $uid )} ) )
                                   AND product $product_where";
        */
        
        
        $sql = "SELECT 
                    campaigns.id as id, 
                    campaigns.ms_id as ms_id, 
                    campaigns.name as name, 
                    campaigns.description as description, 
                    campaigns.label as label, 
                    campaigns.product as product, 
                    campaigns.`date` as date,
                    market_source.reward_id as ms_reward_id,
                    market_source.plan as ms_plan
                FROM campaigns
                INNER JOIN market_source ON market_source.mrk_src_id = campaigns.ms_id
                INNER JOIN market_source_group ON market_source.mrk_src_grp_id = market_source_group.mrk_src_grp_id
                WHERE market_source_group.company_id = {$this->getCompanyID( $uid )}
                AND campaigns.product $product_where";  
        
        
        return $this->sql->loadArray($sql);                                   
    }

  public function getCompanyCompaignNameBySearch ( $search, $type, $uid = null ) {

    $where = '';
    //TODO assoc array dispatch again please
    if ( $type == 'banner' )
      $where = " b_id != 0";
    else if ( $type == 'url' )
      $where = " b_id = 0 AND ext_id != ''";
    else if ( $type == 'codes' )
      $where = " b_id = 0 AND ext_id = ''";

    return phive( 'SQL' )->loadArray( "SELECT name FROM campaigns WHERE ms_id IN (
                                    SELECT mrk_src_id FROM market_source
                                    WHERE mrk_src_grp_id IN (
                                    SELECT mrk_src_grp_id FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )} ) )
                                    AND name LIKE '%$search%'
                                    AND $where" );
  }

  public function getCompanyCampaignsByRewardPlans ( $rid, $uid = null ) {

    return $this->sql->loadArray( "SELECT c.* FROM campaigns c
                                    INNER JOIN market_source ms
                                    ON ms.mrk_src_id = c.ms_id
                                    WHERE ms.reward_id = $rid
                                    AND ms.company_id = {$this->getCompanyID( $uid )}" );
  }

  public function getCampaignsAll ( $name = null, $mrksrc = null, $mrksrcgrp = null ) {

    $where = '';

    if ( !empty( $mrksrcgrp ) )
      $where = "ms_id IN ( SELECT mrk_src_id FROM market_source WHERE mrk_src_grp_id IN ( SELECT mrk_src_grp_id FROM market_source_group WHERE name = '$mrksrcgrp' ) )";

    if ( !empty( $mrksrc ) ) {

      if ( !empty( $mrksrcgrp ) )
        $where = "AND $where";

      $where = "ms_id IN ( SELECT mrk_src_id FROM market_source WHERE name = '$mrksrc' ) $where";
    }

    if ( !empty( $name ) ) {

      if ( !empty( $where ) )
        $where = " AND $where";

      $where = "WHERE name LIKE '%$name%' $where";
    }
    else
      $where = "WHERE $where";

    return $this->sql->loadArray( "SELECT * FROM campaigns $where" );
  }

  public function getCampaignByBonusCodes ( $bonus_codes ) {

    return $this->sql->loadAssoc( "SELECT * FROM campaigns WHERE name = '$bonus_codes'" );
  }

  public function getCampaignsByMarketSources ( $mark_sources, $product = null ) {

    $product_where = '';

    if ( !empty( $product ) )
      $product_where = " AND product = '$product'";

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                  WHERE ms_id IN ($mark_sources)
                                  $product_where" );
  }

  public function getCompanyCampaignsBanners ( $mid = null, $uid = null ) {

    $where = '';

    if ( $mid != null )
      $where = 'ms_id = ' . $mid;
    else {

      $where = "ms_id IN (
                  SELECT mrk_src_id FROM market_source
                  WHERE mrk_src_grp_id IN (
                  SELECT mrk_src_grp_id FROM market_source_group
                  WHERE company_id = {$this->getCompanyID( $uid )} ) )";
    }

    return $this->sql->loadArray( "SELECT c.*, eb.name as file FROM campaigns AS c
                                    INNER JOIN  ext_banners AS eb
                                    ON c.b_id = eb.id
                                    WHERE $where" );
  }

  public function getCompanyLatestCampaigns ( $limit = 5, $uid = null ) {

    $campaigns = phive( 'ExtAffiliater' )->getAffiliateCampaigns();

    if ( count( $campaigns ) > $limit )
      $start = count( $campaigns ) - $limit;
    else
      $start = 0;

    $campaign_list = [ ];

    $counter = 1;

    for ( $i = $start; $i < count( $campaigns ); $i++ ) {

      $campaign_list[ $limit - $counter ] = $campaigns[ $i ];
      $counter++;
    }

    ksort( $campaign_list, SORT_NUMERIC );

    return $campaign_list;
  }

  public function getCompanyCampaignsByExtID ( $eid, $uid = null ) {

    return $this->sql->loadAssoc( "SELECT *
                                    FROM campaigns
                                    WHERE ext_id =  '$eid'
                                    AND ms_id
                                    IN (

                                    SELECT mrk_src_id
                                    FROM market_source
                                    WHERE mrk_src_grp_id
                                    IN (

                                    SELECT mrk_src_grp_id
                                    FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )}
                                    )
                                    )" );
  }

  public function getCompanyCampaignsByLabel ( $label, $uid = null ) {

    if ( !empty( $uid ) ) {

      $where = "AND ms_id IN (
                SELECT mrk_src_id FROM market_source
                WHERE mrk_src_grp_id IN (
                SELECT mrk_src_grp_id FROM market_source_group
                WHERE company_id = {$this->getCompanyID( $uid )} ) )";
    }

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE label LIKE '%$label%'
                                    $where" );
  }

  public function getCompanyCampaignByName ( $name, $uid = null ) {

    if ( !empty( $uid ) ) {

      $where = "AND ms_id IN (
                SELECT mrk_src_id FROM market_source
                WHERE mrk_src_grp_id IN (
                SELECT mrk_src_grp_id FROM market_source_group
                WHERE company_id = {$this->getCompanyID( $uid )} ) )";
    }

    return $this->sql->loadAssoc( "SELECT * FROM campaigns
                                    WHERE name = '$name'
                                    $where" );
  }

  public function getCompanyBanners ( $uid = null ) {

    return $this->sql->loadArray( "SELECT * FROM ext_banners
                                    WHERE id IN (
                                    SELECT b_id FROM campaigns
                                    WHERE ms_id IN (
                                    SELECT mrk_src_id FROM market_source
                                    WHERE mrk_src_grp_id IN (
                                    SELECT mrk_src_grp_id FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )} ) ) )" );
  }

  public function getBannerByID ( $bid ) {

    return $this->sql->loadAssoc( "SELECT * FROM ext_banners WHERE id = $bid" );
  }

  public function getCompanyExtBanners ( $uid = null ) {

    $ea = phive( 'ExtAffiliater' );

    $extbanners = $ea->getMerchantBanners();
    $banners = $this->getCompanyBanners( $uid );

    $banner_list = array();

    foreach ( $extbanners as $eb ) {

      foreach ( $banners as $b ) {

        if ( $eb[ '_id' ] == $b[ 'name' ] ) {

          $banner_list[] = $eb;
          break;
        }
      }
    }

    return $banner_list;
  }

  public function getCompanyCampaignsExtBanners ( $name = null, $mid = null, $uid = null ) {

    $campaigns = $this->getCompanyCampaignsBanners( $mid, $uid );
    $extbanners = $this->getCompanyExtBanners( $uid );

    $banners = array();

    foreach ( $campaigns as $campaign ) {

      if ( $name == null ) {

        foreach ( $extbanners as $extbanner ) {

          if ( $campaign[ 'file' ] == $extbanner[ '_id' ] ) {

            $banners[] = array_merge( $campaign, $extbanner );
            break;
          }
        }
      }
      else if ( $campaign[ 'name' ] == $name ) {

        foreach ( $extbanners as $extbanner ) {

          if ( $campaign[ 'file' ] == $extbanner[ '_id' ] ) {

            $banners[] = array_merge( $campaign, $extbanner );
            break;
          }
        }
      }
    }

    return $banners;
  }

  public function getCompanyCampaignsWithExtBanners ( $mid = null, $uid = null ) {

    $campaigns = $this->getCompanyCampaignsBanners( $mid, $uid );
    $extbanners = $this->getCompanyExtBanners( $uid );

    $banners = array();

    foreach ( $campaigns as $campaign ) {

      foreach ( $extbanners as $extbanner ) {

        if ( $campaign[ 'file' ] == $extbanner[ '_id' ] ) {

          $banners[] = array_merge( $campaign, $extbanner );
          break;
        }
      }
    }

    return $banners;
  }

  public function getCompanyFilteredTagByType ( $type, $filters, $uid = null ) {

    $sub_query = '';

    foreach ( $filters as $filter ) {

      if ( $filter != '' ) {

        if ( $sub_query == '' ) {

          $sub_query .= "SELECT b_id
                          FROM ext_banners_tags
                          WHERE tag = '$filter'
                          AND b_id IN (
                            SELECT b_id FROM campaigns
                            WHERE ms_id IN (
                              SELECT mrk_src_id FROM market_source
                              WHERE company_id = {$this->getCompanyID( $uid )}
                            )  AND b_id != 0 GROUP BY b_id
	                        ) ";
        }
        else {

          $sub_query .= " AND b_id IN (
                        SELECT b_id
                        FROM ext_banners_tags
                        WHERE tag = '$filter'
                      )";
        }
      }
    }

    return phive( 'SQL' )->loadArray( "SELECT tag
                                        FROM ext_banners_tags
                                        WHERE type = '$type'
                                        AND b_id IN (
                                          $sub_query
                                        )
                                        GROUP BY tag" );
  }

  public function getCompanyBannersByTags ( $filters, $uid = null ) {

    $sub_query = '';

    foreach ( $filters as $filter ) {

      if ( $filter != '' ) {

        if ( $sub_query == '' ) {

          $sub_query .= "eb.id IN ( SELECT b_id
                        FROM ext_banners_tags
                        WHERE tag = '$filter'
                          AND b_id IN (
                            SELECT b_id FROM campaigns
                            WHERE ms_id IN (
                              SELECT mrk_src_id FROM market_source
                              WHERE company_id = {$this->getCompanyID( $uid )}
                            )  AND b_id != 0 GROUP BY b_id
	                        ) )";
        }
        else {

          $sub_query .= " AND eb.id IN (
                        SELECT b_id
                        FROM ext_banners_tags
                        WHERE tag = '$filter'
                      )";
        }
      }
    }

    return phive( 'SQL' )->loadArray( "SELECT eb.name AS file, c.name, c.id, c.url, c.ext_id FROM ext_banners AS eb
                                        INNER JOIN campaigns AS c
                                        ON eb.id = c.b_id
                                        WHERE $sub_query" );
  }

  public function getCompanyExtBannersbyTags ( $filters, $uid = null ) {

    $campaigns = phive( 'ExtAffiliater' )->getAffiliateCampaigns();
    $tagged = $this->getCompanyBannersByTags( $filters, $uid );

    $rarr = array();

    foreach ( $campaigns as $c ) {

      foreach ( $tagged as $tag ) {

        if ( $c[ '_id' ] == $tag[ 'ext_id' ] ) {

          $c[ 'name' ] = $tag[ 'name' ];
          $c[ 'id' ] = $tag[ 'id' ];
          $c[ 'url' ] = $tag[ 'url' ];
          $rarr[] = $c;
        }
      }
    }

    return $rarr;
  }

  public function getCompanyURLs ( $uid = null, $mid = null ) {

    $where = '';

    if ( $mid != null ) {

      $where .= "= $mid";
    }
    else {

      $where .= "IN (
                  SELECT mrk_src_id FROM market_source
                  WHERE mrk_src_grp_id IN (
                  SELECT mrk_src_grp_id FROM market_source_group
                  WHERE company_id = {$this->getCompanyID( $uid )} ) )";
    }

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE b_id = 0 AND link_id != 0 AND  ms_id $where" );
  }

  public function getCompanyURLByExtID ( $eid, $uid = null ) {

    return $this->sql->loadAssoc( "SELECT *
                                    FROM campaigns
                                    WHERE b_id = 0
                                    AND ext_id =  '$eid'
                                    AND ms_id
                                    IN (

                                    SELECT mrk_src_id
                                    FROM market_source
                                    WHERE mrk_src_grp_id
                                    IN (

                                    SELECT mrk_src_grp_id
                                    FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )}
                                    )
                                    )" );
  }

  public function getCompanyURLByName ( $name, $uid = null ) {

    return $this->sql->loadArray( "SELECT *
                                    FROM campaigns
                                    WHERE b_id = 0
                                    AND name =  '$name'
                                    AND ms_id
                                    IN (

                                    SELECT mrk_src_id
                                    FROM market_source
                                    WHERE mrk_src_grp_id
                                    IN (

                                    SELECT mrk_src_grp_id
                                    FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )}
                                    )
                                    )" );
  }

  public function getCompanyURLByID ( $cid, $uid = null ) {

    return $this->sql->loadArray( "SELECT *
                                    FROM campaigns
                                    WHERE b_id =0
                                    AND id =  '$cid'
                                    AND ms_id
                                    IN (

                                    SELECT mrk_src_id
                                    FROM market_source
                                    WHERE mrk_src_grp_id
                                    IN (

                                    SELECT mrk_src_grp_id
                                    FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )}
                                    )
                                    )" );
  }

  public function getCompanyCodes ( $uid = null, $mid = null ) {

    $where = '';

    if ( $mid != null ) {

      $where .= "= $mid";
    }
    else {

      $where .= "IN (
                  SELECT mrk_src_id FROM market_source
                  WHERE mrk_src_grp_id IN (
                  SELECT mrk_src_grp_id FROM market_source_group
                  WHERE company_id = {$this->getCompanyID( $uid )} ) )";
    }

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE b_id = 0
                                    AND ext_id = ''
                                    AND ms_id $where" );
  }

  public function getCompanyCodesByLabel ( $label, $uid = null, $mid = null ) {

    $where = '';

    if ( $mid != null ) {

      $where .= "= $mid";
    }
    else {

      $where .= "IN (
                  SELECT mrk_src_id FROM market_source
                  WHERE mrk_src_grp_id IN (
                  SELECT mrk_src_grp_id FROM market_source_group
                  WHERE company_id = {$this->getCompanyID( $uid )} ) )";
    }

    return $this->sql->loadArray( "SELECT * FROM campaigns
                                    WHERE b_id = 0
                                    AND ext_id = ''
                                    AND label = '$label'
                                    AND ms_id $where" );
  }

  public function getCampaignByID ( $id, $uid = null ) {

    return $this->sql->loadAssoc( "SELECT *
                                    FROM campaigns c
                                    WHERE c.id =  '$id'
                                    AND ms_id
                                    IN (

                                    SELECT mrk_src_id
                                    FROM market_source
                                    WHERE mrk_src_grp_id
                                    IN (

                                    SELECT mrk_src_grp_id
                                    FROM market_source_group
                                    WHERE company_id = {$this->getCompanyID( $uid )}
                                    )
                                    )" );
  }

  public function checkDuplicateNews ( $field, $value, $update = false, $nid = null ) {

    return $this->sql->loadArray( "SELECT * FROM news WHERE $field = '$value'" . ( $update ? " AND id != $nid" : '' ) );
  }

  public function checkDuplicateNewsTranslate ( $field, $value, $update = false, $nid = null ) {

    return $this->sql->loadArray( "SELECT * FROM news_translate WHERE $field = '$value'" . ( $update ? " AND id != $nid" : '' ) );
  }

  public function getPlayers () {

    return $this->sql->loadArray( "SELECT * FROM players" );
  }

  public function getPlayerByUser ( $uid ) {
    $uid = intval($uid);
    return $this->sql->loadAssoc( "SELECT * FROM players WHERE user_id = $uid" );
  }

  public function updatePlayerCPA ( $uid, $date, $cpa = 1 ) {

    $to_update = array(
      'cpa_flag' => $cpa,
      'cpa_date' => $date
    );

    $this->sql->updateArray( 'players', $to_update, "user_id = $uid" );
  }

  public function insertPlayer ( $uid, $product, $date = null ) {

    if ( empty( $date ) )
      $date = date( 'Y-m-d' );

    $to_insert = array(
      'user_id'  => $uid,
      'product'  => $product,
      'cpa_date' => $date
    );

    $cols = $this->getColumns( 'players', true );
    $to_insert = $this->sql->escapeArray( array_intersect_key( $to_insert, $cols ), false );

    return $this->sql->insertArray( 'players', $to_insert );
  }

  public function insertUniquePlayers ( $users, $product, $col = 'user_id', $date = null ) {

    foreach ( $users as $user ) {
      if ( empty( $this->getPlayerByUser( $user[ $col ] ) ) )
        $this->insertPlayer( $user[ $col ], $product, $date );
    }
  }

    function importUds($date){
        $daff = phive( 'PRDistAffiliater' );

        $in_process = true;
        $sleep = 60;
        $try = 20;
        $products_fail = [ ];
        $products = [ ];

        while ( $in_process && $try > 0 ) {

            foreach ( $this->getProducts() as $product ) {

                if ( !in_array( $product[ 'signature' ], $products ) ) {

                    if ( $product[ 'stats' ] == 1 ) {

                        $result = $daff->getUsersDailyStats( $product[ 'signature' ], $date );

                        if ( $result ) {

                            $products[ $product[ 'key' ] ] = $product[ 'signature' ];
                            unset( $products_fail[ $product[ 'key' ] ] );
                        }
                        else {

                            $products_fail[ $product[ 'key' ] ] = $product[ 'signature' ];
                            sleep( $sleep );
                        }

                        $try--;
                    }
                }
            }

            if ( empty( $products_fail ) )
                $in_process = false;
        }

        if ( $in_process ) {

            $error_msg = 'The following products failed to send User Daily Stats: \n';

            foreach ( $products_fail as $fail )
                $error_msg .= '\n-' . $fail;

            $replacers[ '__ERROR__' ] = $error_msg;

            phive( 'UserHandler' )->sendMailPR( "error.pr", phive( 'UserHandler' )->getUser( $this->getUserDetailsByUsername( 'admin' )[ 'id' ] ), $replacers );
        }
        $in_process = true;
        $sleep = 60;
        $try = 20;
        $products_fail = [ ];
        $products = [ ];

        while ( $in_process && $try > 0 ) {

            foreach ( $this->getProducts() as $product ) {

                if ( !in_array( $product[ 'signature' ], $products ) ) {

                    if ( $product[ 'stats' ] == 1 ) {

                        $result = $daff->getUsersDailyStats( $product[ 'signature' ], $date );

                        if ( $result ) {

                            $products[ $product[ 'key' ] ] = $product[ 'signature' ];
                            unset( $products_fail[ $product[ 'key' ] ] );
                        }
                        else {

                            $products_fail[ $product[ 'key' ] ] = $product[ 'signature' ];
                            sleep( $sleep );
                        }

                        $try--;
                    }
                }
            }

            if ( empty( $products_fail ) )
                $in_process = false;
        }

        if ( $in_process ) {

            $error_msg = 'The following products failed to send User Daily Stats: \n';

            foreach ( $products_fail as $fail )
                $error_msg .= '\n-' . $fail;

            $replacers[ '__ERROR__' ] = $error_msg;

            $this->sendMailPR( "error.pr", $this->getUser( $uh->getUserDetailsByUsername( 'admin' )[ 'id' ] ), $replacers );
        }
    }
    
  public function checkPlayersCPA ( $users, $product = null, $cpa = null, $sdate = null, $edate = null ) {

    $where_product = '';
    $where_cpa = '';
    $where_sdate = '';
    $where_edate = '';

    if ( !empty( $product ) )
      $where_product = " AND product = '$product'";

    if ( $cpa !== null )
      $where_cpa = " AND cpa_flag = $cpa";

    if ( !empty( $sdate ) )
      $where_sdate = " AND cpa_date >= '$sdate'";

    if ( !empty( $edate ) )
      $where_edate = " AND cpa_date <= '$edate'";

      $str = "SELECT user_id FROM players
              WHERE user_id IN ( {$this->helperArrayToInStr( $users )} )
                  $where_product
                  $where_cpa
                  $where_sdate
                  $where_edate";
      
    return $this->sql->loadArray( $str );
  }

  public function getPlayersCPA ( $users, $product = null, $cpa = null, $sdate = null, $edate = null ) {

    $where_product = '';
    $where_cpa = '';
    $where_date = '';

    if ( !empty( $product ) )
      $where_product = " AND product = '$product'";

    if ( $cpa !== null )
      $where_cpa = " AND cpa_flag = $cpa";

    if ( !empty( $sdate ) && !empty( $edate ) )
      $where_date = " AND cpa_date >= '$sdate' AND cpa_date <= '$edate'";

    return $this->sql->loadArray( "SELECT * FROM players
                                      WHERE user_id IN ( {$this->helperArrayToInStr( $users )} )
                                      $where_product
                                      $where_cpa
                                      $where_date" );
  }

  public function getLanguages () {

    return $this->sql->loadArray( "SELECT * FROM languages" );
  }

  public function getLanguage ( $lid ) {

    return $this->sql->loadAssoc( "SELECT * FROM languages WHERE language = '$lid'" );
  }

  public function getSumBonusStatesByCampaign ( $campaigns, $sdate = null, $edate = null ) {

    $dateWhere = '';

    if ( !empty( $sdate ) )
      $dateWhere = " AND day_date >= '$sdate'";

    if ( !empty( $edate ) )
        $dateWhere .= " AND day_date <= '$edate'";
      $str = "SELECT COALESCE( SUM(bets), 0 ) AS bet,
                  COALESCE( SUM(deposits), 0 ) AS deposit,
                  COALESCE( SUM( real_prof + cpa_prof ), 0 ) AS profit,
                  SUM( real_prof ) AS real_prof,
                  SUM( cpa_prof ) AS cpa_prof
              FROM affiliate_daily_bcodestats
              WHERE bonus_code IN ( {$this->helperArrayToInStr( $campaigns )} )
                  $dateWhere";
    return $this->sql->loadAssoc($str);
  }

  public function getSumBonusSubStatesByCampaign ( $campaigns, $sdate = null, $edate = null ) {

    $dateWhere = '';

    if ( !empty( $sdate ) )
      $dateWhere = " AND day_date >= '$sdate'";

    if ( !empty( $edate ) )
      $dateWhere .= " AND day_date <= '$edate'";

    return $this->sql->loadAssoc( "SELECT COALESCE( SUM( real_prof ), 0 ) as profit
                                    FROM sub_affiliate_daily_stats
                                    WHERE bonus_code IN ( {$this->helperArrayToInStr( $campaigns )} )
                                    $dateWhere" );
  }

  public function getCashTransactionsAmount ( $uid = null, $checked = true, $debit = true, $sdate = null, $edate = null, $type = null ) {

    $dateWhere = '';
    $userWhere = '';

    if ( !empty( $sdate ) )
      $dateWhere = " AND timestamp >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $dateWhere .= " AND timestamp <= '$edate 23:59:59'";

    if ( !empty( $uid ) )
      $userWhere = " AND user_id = $uid";

    $types = [ ];

    if ( empty( $type ) ) {

      $transactiontypes = phive( 'PRAffiliater' )->getTransactionTypes();

      foreach ( $transactiontypes as $type => $transactiontype ) {

        if ( $debit && $transactiontype[ 'debit' ] != 1 )
          continue;
        else if ( !$debit && $transactiontype[ 'debit' ] != 0 )
          continue;

        array_push( $types, $type );
      }
    }
    else
      array_push( $types, $type );

    if ( $checked )
      $checked = "1";
    else
      $checked = "0";

    $types_str = $this->helperArrayToInStr( $types );

    return $this->sql->getValue( "SELECT SUM(amount) FROM cash_transactions
                                  WHERE checked IN ( $checked )
                                  AND transactiontype IN ( $types_str )
                                  $dateWhere
                                  $userWhere" );
  }

  public function getCashTransactions ( $sdate = null, $edate = null, $uid = null, $debit = true, $type = null ) {

    $dateWhere = '';
    $userWhere = '';

    if ( !empty( $sdate ) )
      $dateWhere = " AND timestamp >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $dateWhere .= " AND timestamp <= '$edate 23:59:59'";

    if ( !empty( $uid ) )
      $userWhere = " AND user_id = $uid";
    else {

      $mid = $this->getCompanyManager( $this->getCompanyID( $uid ) )[ 'id' ];
      $userWhere = " AND user_id = $mid";
    }

    $types = [ ];

    if ( empty( $type ) ) {

      $transactiontypes = phive( 'PRAffiliater' )->getTransactionTypes();

      foreach ( $transactiontypes as $type => $transactiontype ) {

        foreach ( $transactiontypes as $type => $transactiontype ) {

          if ( $debit && $transactiontype[ 'debit' ] != 1 )
            continue;
          else if ( !$debit && $transactiontype[ 'debit' ] != 0 )
            continue;

          array_push( $types, $type );
        }

        array_push( $types, $type );
      }
    }
    else
      array_push( $types, $type );

    $types_str = $this->helperArrayToInStr( $types );

    return $this->sql->loadArray( "SELECT * FROM cash_transactions
                                  WHERE transactiontype = 8 
                                  $dateWhere
                                  $userWhere" );
  }

  public function getCashTransactionLast ( $type = null, $uid = null ) {

    $typeWhere = '';

    if ( !empty( $type ) )
      $typeWhere = " AND transactiontype = $type";

    $mid = $this->getCompanyManager( $this->getCompanyID( $uid ) )[ 'id' ];

    return $this->sql->loadAssoc( "SELECT * FROM cash_transactions
                                  WHERE user_id = $mid
                                  $typeWhere
                                  ORDER BY timestamp DESC
                                  LIMIT 1" );
  }

  public function getQueuedTransactionsAmount ( $approved = null, $type = null, $uid = null ) {

    $typeWhere = '';
    $approvedWhere = '';

    if ( !empty( $type ) )
      $typeWhere = " AND transactiontype = $type";

    if ( !empty( $approved ) )
      $approvedWhere = " AND approved = $approved";

    $mid = $this->getCompanyManager( $this->getCompanyID( $uid ) )[ 'id' ];

    return $this->sql->getValue( "SELECT SUM(amount) FROM queued_transactions
                                  WHERE user_id = $mid
                                  $typeWhere
                                  $approvedWhere" );
  }

  public function deleteQueuedTransactionByID ( $tid ) {

    $this->sql->delete( 'queued_transactions', "transaction_id = $tid" );
  }

  public function getMessagesByReceiver ( $receiver ) {

    return $this->sql->loadArray( "SELECT * FROM messages m1
                                    WHERE m1.receiver_id = $receiver
                                    AND m1.deleted_receiver = 0
                                    AND m1.trash_receiver = 0
                                    AND time IN (
                                        SELECT MAX(m2.time) FROM messages m2
                                        INNER JOIN messages m3
                                        ON m2.id = m3.id
                                        WHERE m2.receiver_id = $receiver
                                        AND m2.deleted_receiver = 0
                                        AND m2.trash_receiver = 0
                                      GROUP BY m2.reply_to_id
                                    )
                                    ORDER BY m1.time DESC" );
  }

  public function getMessagesByTrashReceiver ( $receiver ) {

    return $this->sql->loadArray( "SELECT * FROM messages
                                    WHERE trash_receiver = 1
                                    AND deleted_receiver = 0
                                    AND receiver_id = $receiver
                                    GROUP BY reply_to_id
                                    ORDER BY time DESC" );
  }

  public function getMessagesBySender ( $sender ) {

    return $this->sql->loadArray( "SELECT * FROM (
                                      SELECT * FROM messages m2
                                      WHERE time = (
                                          SELECT MAX(time) FROM messages m3
                                          WHERE sender_id = $sender
                                          AND deleted_sender = 0
                                          AND m2.id = m3.id
                                      )
                                      GROUP BY reply_to_id
                                    ) m1
                                    GROUP BY m1.group_by_id
                                    ORDER BY time DESC" );
  }

  public function getMessagesByUnread ( $receiver ) {

    return $this->sql->loadArray( "SELECT * FROM messages m1
                                    INNER JOIN (
                                      SELECT id, reply_to_id, MAX( TIME ) AS TIME
                                      FROM messages
                                      GROUP BY reply_to_id
                                    )m2 ON m2.reply_to_id = m1.reply_to_id
                                    AND m2.time = m1.time
                                        WHERE m1.receiver_id = $receiver
                                        AND m1.deleted_receiver = 0
                                        AND m1.trash_receiver = 0
                                      AND m1.seen = 0
                                    GROUP BY m2.reply_to_id
                                    ORDER BY m1.time DESC" );
  }

  public function getMessagesByGroup ( $mid, $current = false ) {

    if ( !$current ) {

      $reply_to_id = $this->sql->getValue( "SELECT reply_to_id FROM messages
                                          WHERE id = $mid" );

      $user_id = $this->getAttr( 'id' );

      $messages = $this->sql->loadArray( "SELECT * FROM messages
                                        WHERE reply_to_id = $reply_to_id
                                        AND receiver_id = $user_id
                                        OR reply_to_id = $reply_to_id
                                        AND sender_id = $user_id
                                        ORDER BY time DESC" );

      $mid = $messages[ 0 ][ 'id' ];
    }

    $group_by_id = $this->sql->getValue( "SELECT group_by_id FROM messages
                                            WHERE id = $mid" );

    return $this->sql->loadArray( "SELECT * FROM messages
                                    WHERE group_by_id = $group_by_id" );
  }

  public function getMessages ( $mid, $receiver = true, $sender = true, $group = true ) {

    $get_receiver = '';
    $get_sender = '';
    $group_by = '';

    $reply_to_id = $this->sql->getValue( "SELECT reply_to_id FROM messages
                                          WHERE id = $mid" );

    $user_id = $this->getAttr( 'id' );

    if ( $receiver )
      $get_receiver = " reply_to_id = $reply_to_id AND receiver_id = $user_id";

    if ( $sender ) {

      if ( $get_receiver != '' )
        $get_sender = " OR";

      $get_sender .= " reply_to_id = $reply_to_id AND sender_id = $user_id";
    }

    if ( $group )
      $group_by = " GROUP BY group_by_id";

    return $this->sql->loadArray( "SELECT * FROM messages
                                    WHERE $get_receiver
                                    $get_sender
                                    $group_by
                                    ORDER BY time DESC" );
  }

  public function updateMessagesColumn ( $col, $val, $where ) {

    $to_update = array(
      $col => $val
    );

    $this->sql->updateArray( 'messages', $to_update, $where );
  }

  public function getMessageByID ( $mid ) {

    return $this->sql->loadAssoc( "SELECT * FROM messages WHERE id = $mid" );
  }

  public function getNews ( $cid = null, $sdate = null, $edate = null ) {

    $category = '';
    $date = '';

    if ( !empty( $cid ) )
      $category = " AND category_id = $cid ";

    if ( !empty( $sdate ) && !empty( $edate ) )
      $date = " AND date BETWEEN '$sdate' AND '$edate' ";

    return $this->sql->loadArray( "SELECT * FROM news
                                    WHERE published = 1
                                    $category
                                    $date
                                    ORDER BY date DESC" );
  }

  //COMMENT why couldn't the existing limited news handler be used here instead?
  public function deleteNews ( $n_title ) {

    $news_id = $this->getNewsTranslateByTitle( $n_title )[ 'news_id' ];

    $this->sql->delete( 'news_translate', "title = '$n_title'" );

    $news = $this->sql->loadArray( "SELECT * FROM news_translate WHERE news_id = $news_id" );

    if ( count( $news ) <= 0 )
      $this->sql->delete( 'news', "id = $news_id" );
  }

  public function getNewsBySlug ( $slug ) {

    return $this->sql->loadAssoc( "SELECT * FROM news WHERE slug = '$slug'" );
  }

  public function getNewsCategories () {

    return $this->sql->loadArray( "SELECT * FROM news_categories" );
  }

  public function addNewsCategory ( $insert ) {

    $this->sql->insertArray( 'news_categories', $insert );
  }

  public function getNewsCategoriesByID ( $id ) {
    $id = intval($id);
    return $this->sql->loadAssoc( "SELECT * FROM news_categories WHERE id = $id" );
  }

  public function getNewsTranslateByNewsID ( $nid, $lang ) {

    $news = $this->sql->loadAssoc( "SELECT * FROM news n
                                    INNER JOIN news_translate nt
                                    ON n.id = nt.news_id
                                    WHERE n.id = $nid
                                    AND nt.lang = '$lang'" );

    if ( empty( $news ) )
      $news = $this->sql->loadAssoc( "SELECT * FROM news n
                                    INNER JOIN news_translate nt
                                    ON n.id = nt.news_id
                                    WHERE n.id = $nid
                                    AND nt.lang = 'en'" );

    return $news;
  }

  public function getNewsTranslate ( $lang = null, $search = null ) {

    $where_lang = '';
    $where_search = '';

    if ( !empty( $lang ) )
      $where_lang = "WHERE lang = '$lang'";

    if ( !empty( $search ) )
      $where_search = ( $where_lang == '' ? "WHERE" : "AND" ) . " title LIKE '%$search%'";

    return $this->sql->loadArray( "SELECT * FROM news_translate $where_lang $where_search" );
  }

  public function getNewsTranslateByID ( $id ) {

    return $this->sql->loadAssoc( "SELECT * FROM news_translate nt
                                    INNER JOIN news n
                                    ON nt.news_id = n.id
                                    WHERE nt.id = $id" );
  }

  public function getNewsTranslateByTitle ( $title ) {

    return $this->sql->loadAssoc( "SELECT * FROM news_translate nt
                                    INNER JOIN news n
                                    ON nt.news_id = n.id
                                    WHERE nt.title = '$title'" );
  }

  public function getNewsByLimit ( $limit = null, $max_limit = null, $lang = 'en', $cid = null, $sdate = null, $edate = null ) {

    $category = '';
    $date = '';
    $sql_limit = '';

    if ( !empty( $cid ) )
      $category = " AND n.category_id = $cid ";

    if ( !empty( $sdate ) && !empty( $edate ) )
      $date = " AND date BETWEEN '$sdate' AND '$edate' ";

    if ( $limit !== null && $max_limit !== null )
      $sql_limit = "LIMIT $limit, $max_limit";

    $current_lang[] = $this->sql->loadArray( "SELECT * FROM news n
                                    INNER JOIN news_translate nt
                                    ON n.id = nt.news_id
                                    WHERE nt.lang = '$lang'
                                    AND n.published = 1
                                    $category
                                    $date
                                    ORDER BY date DESC
                                    $sql_limit" );

    $news = array();

    if ( $lang != 'en' ) {

      $default_lang[] = $this->sql->loadArray( "SELECT * FROM news n
                                    INNER JOIN news_translate nt
                                    ON n.id = nt.news_id
                                    WHERE nt.lang = 'en'
                                    AND n.published = 1
                                    $category
                                    $date
                                    ORDER BY date DESC
                                    $sql_limit" );

      foreach ( $default_lang[ 0 ] as $default ) {

        $current_check = false;

        foreach ( $current_lang[ 0 ] as $current ) {

          if ( $default[ 'news_id' ] == $current[ 'news_id' ] ) {
            $current_check = true;
            $news[] = $current;
          }
        }

        if ( !$current_check )
          $news[] = $default;
      }
    }
    else {
      $news = $current_lang[ 0 ];
    }

    return $news;
  }

  public function getCommentsByNews ( $nid ) {

    return $this->sql->loadArray( "SELECT * FROM news_comments
                                    WHERE news_id = $nid
                                    AND approved = 1
                                    ORDER BY time DESC" );
  }

  public function getNewsByDate ( $sdate, $edate ) {

    return $this->sql->loadArray( "SELECT * FROM news n
                                    INNER JOIN news_translate nt
                                    ON n.id = nt.news_id
                                    WHERE date >= '$sdate'
                                    AND date <= '$edate'" );
  }

  //COMMENT some kind of faq functionality already exists on VS, why couldn't it be used?
  public function getFAQArray ( $lang ) {

    $faqs = array();

    $faq_list = $this->sql->loadArray( "SELECT * FROM faqs f
                                    INNER JOIN faqs_translate ft
                                    ON f.id = ft.faq_id
                                    INNER JOIN faqs_header fh
                                    ON f.id = fh.faq_id
                                    WHERE ft.lang = '$lang'
                                    AND fh.lang = '$lang'" );

    foreach ( $faq_list as $faq ) {

      $faqs[ $faq[ 'header_id' ] ][ 'title' ] = $faq[ 'header' ];
      $faqs[ $faq[ 'header_id' ] ][ 'faqs' ][ $faq[ 'faq_id' ] ] = array( 'title' => $faq[ 'title' ], 'desc' => $faq[ 'desc' ] );
    }

    return $faqs;
  }

  public function getFAQBySearch ( $search, $lang ) {

    return $this->sql->loadArray( "SELECT * FROM faqs_translate
                                    WHERE lang = '$lang'
                                      AND title LIKE '%$search%'" );
  }

  public function getFAQByID ( $fid, $lang ) {

    return $this->sql->loadArray( "SELECT * FROM faqs f
                                    INNER JOIN faqs_translate ft
                                    ON f.id = ft.faq_id
                                    INNER JOIN faqs_header fh
                                    ON f.id = fh.faq_id
                                    WHERE ft.lang = '$lang'
                                    AND fh.lang = '$lang'
                                    AND ft.faq_id = $fid
                                    AND fh.faq_id = $fid" );
  }

  public function getADB ( $sdate, $edate, $campaigns = '' ) {

    $where = '';

    if ( !empty( $campaigns ) )
      $where = " AND bonus_code IN ( $campaigns )";

      $sql_str = "SELECT * FROM affiliate_daily_bcodestats
                  WHERE day_date >= '$sdate'
                      AND day_date <= '$edate'
                      $where
                      ORDER BY day_date";
      
    return $this->sql->loadArray($sql_str);
  }

  public function getADBByManagerID ( $sdate, $edate, $uid ) {

    return $this->sql->loadArray( "SELECT * FROM affiliate_daily_bcodestats
                                    WHERE day_date >= '$sdate'
                                    AND day_date <= '$edate'
                                    AND affe_id = $uid" );
  }

  public function getADBByCampaigns ( $sdate, $edate, $bonuscodes ) {

    $bonuscodes_str = $this->helperArrayToInStr( $bonuscodes );

    return $this->sql->loadArray( "SELECT * FROM affiliate_daily_bcodestats
                                    WHERE day_date >= '$sdate'
                                    AND day_date <= '$edate'
                                    AND bonus_code IN ( $bonuscodes_str )" );
  }

  public function getPendingWithdrawals ( $status = '', $payment_method = '', $uid = null, $sdate = null, $edate = null ) {

    $where_pm = '';
    $where_u = '';
    $where_date = '';

    if ( !empty( $payment_method ) )
      $where_pm .= " AND payment_method = '$payment_method'";

    if ( !empty( $uid ) )
      $where_u .= " AND user_id = $uid";

    if ( !empty( $sdate ) )
      $where_date .= " AND timestamp >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $where_date .= " AND timestamp <= '$edate 23:59:59'";

    return $this->sql->loadArray( "SELECT * FROM pending_withdrawals
                                  WHERE status = '$status'
                                  $where_pm
                                  $where_u
                                  $where_date" );
  }

  public function getPendingWithdrawalByID ( $pid ) {

    return $this->sql->loadAssoc( "SELECT * FROM pending_withdrawals WHERE id = $pid" );
  }

  public function getPendingWithdrawalsAmount ( $uid = null, $sdate = null, $edate = null ) {

    $where_date = '';

    if ( !empty( $sdate ) )
      $where_date .= " AND timestamp >= '$sdate 00:00:00";

    if ( !empty( $edate ) )
      $where_date .= " AND timestamp <= '$edate 23:59:59";

    return $this->sql->getValue( "SELECT SUM( amount ) FROM pending_withdrawals
                                  WHERE user_id = {$this->getCompanyManager( $this->getCompanyID( $uid ) )['id']}
                                  AND status = 'approved'
                                  $where_date" );
  }

  public function getSubAffiliates ( $bonus_codes ) {

    $bonus_codes_str = $this->helperArrayToInStr( $bonus_codes );

    return $this->sql->loadArray( "SELECT * FROM companies WHERE bonus_code IN ( $bonus_codes_str )" );
  }

  public function getSubAffiliatesProf ( $uid = null, $sdate = null, $edate = null ) {

    $where = '';

    if ( !empty( $sdate ) )
      $where .= " AND day_date >= '$sdate'";

    if ( !empty( $edate ) )
      $where .= " AND day_date <= '$edate'";

    return $this->sql->getValue( "SELECT SUM( real_prof ) FROM sub_affiliate_daily_stats WHERE affe_id = {$this->getCompanyManager( $this->getCompanyID( $uid ) )['id']} $where" );
  }

  public function getSubAffiliatesStats ( $uid, $sdate = null, $edate = null ) {

    $where = '';

    if ( !empty( $sdate ) )
      $where .= " AND day_date >= '$sdate'";

    if ( !empty( $edate ) )
      $where .= " AND day_date <= '$edate'";

    return $this->sql->loadArray( "SELECT * FROM sub_affiliate_daily_stats WHERE affe_id = $uid $where ORDER BY day_date" );
  }

  public function getUsersByCampaigns ( $campaigns, $sdate = null, $edate = null, $countries = [] ) {
    $where_date = '';

      if ( !empty( $sdate ) )
          $where_date .= " AND date >= '$sdate 00:00:00'";
      
      if ( !empty( $countries ) )
          $in_countries = " AND country IN({$this->sql->makeIn($countries)}) ";
          
      if ( !empty( $edate ) )
          $where_date .= " AND date <= '$edate 23:59:59'";

      $str = "SELECT * FROM pixel_registrar
              WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
              $in_countries
              $where_date";     

    return $this->sql->loadArray( $str );
  }

  public function getSubUsersByCampaigns ( $campaigns ) {

    return $this->sql->loadArray( "SELECT * FROM companies WHERE bonus_code in ({$this->helperArrayToInStr( $campaigns )})" );
  }

  public function getCompanyUsersRegistrar ( $campaigns, $sdate = null, $edate = null ) {

    $date_where = '';

    if ( !empty( $sdate ) && !empty( $edate ) )
      $date_where = "AND date BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59'";

    return $this->sql->loadArray( "SELECT * FROM pixel_registrar
                                    WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
                                    $date_where" );
  }

  public function getCompanySubUsersRegistrar ( $campaigns, $sdate = null, $edate = null ) {

    return $this->sql->loadArray( "SELECT * FROM companies
                                  WHERE bonus_code IN ({$this->helperArrayToInStr($campaigns)})
                                  AND register_date BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59'" );
  }

  public function getAllUsersIDRegistrar ( $product = null, $date = null ) {

    $product_where = '';
    $date_where = '';

    if ( !empty( $product ) )
      $product_where = "WHERE product = '$product'";

    if ( !empty( $date ) )
      $date_where = "AND date BETWEEN '$date 00:00:00' AND '$date 23:59:59'";

    return $this->sql->load2DArr( "SELECT * FROM pixel_registrar $product_where $date_where", 'product' );
  }

  //TODO the below 4 functions could be turned into one
  public function getCompanyUsersDeposits ( $campaigns = null, $sdate = null, $edate = null, $product = null ) {

    $where = '';

    if ( !empty( $campaigns ) )
      $where = "WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})";

    if ( !empty( $sdate ) )
      $where .= ( empty( $where ) ? "WHERE" : " AND" ) . " date >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $where .= ( empty( $where ) ? "WHERE" : " AND" ) . " date <= '$edate 23:59:59'";

    if ( !empty( $product ) )
      $where .= ( empty( $where ) ? "WHERE" : " AND" ) . " product = '$product";

    return $this->sql->loadArray( "SELECT * FROM pixel_deposit
                                    $where
                                    ORDER BY date ASC" );
  }

  public function getCompanyUsersDepositsUnique ( $campaigns, $sdate = null, $edate = null ) {

    $date_range = '';

    if ( !empty( $sdate ) )
      $date_range .= " AND date >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $date_range .= " AND date <= '$edate 23:59:59'";

    return $this->sql->loadArray( "SELECT DISTINCT uid, * FROM pixel_deposit
                                    WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
                                    $date_range
                                    ORDER BY date ASC" );
  }

  public function getCompanyUsersFirstDeposit ( $campaigns, $sdate = null, $edate = null ) {

    $date_range = '';

    if ( !empty( $sdate ) )
      $date_range .= " AND date >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $date_range .= " AND date <= '$edate 23:59:59'";

    return $this->sql->loadArray( "SELECT * FROM pixel_first_deposit
                                    WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
                                    $date_range
                                    ORDER BY date ASC" );
  }

  public function getCompanyUsersFirstDepositCheck( $uid, $sdate = null, $edate = null ){

    $cid = $this->getCompanyIDByUserID( $uid );

    return $this->sql->loadArray( "SELECT adb.* FROM affiliate_daily_bcodestats adb
                                    LEFT JOIN campaigns c ON c.name = adb.bonus_code 
                                    LEFT JOIN market_source ms ON c.ms_id = mrk_src_id
                                    LEFT JOIN reward_plans rp ON ms.reward_id = rp.reward_plans_id
                                    WHERE adb.day_date BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59' 
                                    AND ms.company_id = $cid AND rp.signature = 'ndc'");
  }

  public function getFirstDeposits ( $sdate = null, $edate = null, $product ) {

    $date_where = null;
    $product_where = null;

    if ( !empty( $sdate ) && !empty( $edate ) )
      $date_where = "WHERE date BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59'";

    if ( !empty( $product ) ) {
      if ( empty( $date_where ) )
        $product_where = "WHERE product = '$product'";
      else
        $product_where = "AND product = '$product'";
    }

    return $this->sql->loadArray( "SELECT * FROM pixel_first_deposit $date_where $product_where" );
  }

  public function getCompanyUsersActivity ( $campaigns = null, $sdate = null, $edate = null, $group_by = null, $product = null ) {

    $where = '';
    $groupby = '';

    if ( !empty( $campaigns ) )
      $where = "WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})";

    if ( !empty( $sdate ) )
      $where .= ( empty( $where ) ? "WHERE" : " AND" ) . " date >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $where .= ( empty( $where ) ? "WHERE" : " AND" ) . " date <= '$edate 23:59:59'";

    if ( !empty( $product ) )
      $where .= ( empty( $where ) ? "WHERE" : " AND" ) . " product = '$product";

    if ( !empty( $group_by ) )
      $groupby = "GROUP BY $group_by";

      $str = "SELECT * FROM pixel_user_login
                  $where
                  $groupby
                  ORDER BY date ASC";
      
    return $this->sql->loadArray($str);
  }

  public function getCompanyUsersActivityCheck ( $campaigns = null, $sdate = null, $edate = null, $group_by = null, $product = null ) {

  } 

  public function getCompanyUsersFirstActivity ( $campaigns, $sdate = null, $edate = null ) {

    $date_range = '';

    if ( !empty( $sdate ) )
      $date_range .= " AND date >= '$sdate 00:00:00'";

    if ( !empty( $edate ) )
      $date_range .= " AND date <= '$edate 23:59:59'";

    return $this->sql->loadArray( "SELECT * FROM pixel_registrar
                                    WHERE bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
                                    $date_range
                                    ORDER BY date ASC" );
  }

  public function getUserDepositsAndActivity ( $campaigns, $sdate = null, $edate = null, $range = null, $range_count = null, $range_keys = null, $uid = null ) {
    $details   = array();
    $deposits  = $this->getCompanyUsersDeposits( $campaigns, $sdate, $edate );
    $d_counter = 0;
    $td        = 0;
    $udc       = array();
    $udc_id    = array();
    $udc_range = array();

    foreach ( $deposits as $deposit ) {
      if ( !in_array( $deposit[ 'uid' ], $udc_id ) ) {
        $udc_id[] = $deposit[ 'uid' ];
        $udc_range[] = $deposit;
      }

      if ( $deposit[ 'count' ] == 0 ) {
        $d_counter += 1;
        $udc[ $deposit[ 'uid' ] ] += 1;
      } else {
        $d_counter += $deposit[ 'count' ];
        $udc[ $deposit[ 'uid' ] ] += $deposit[ 'count' ];
      }

      $td += chg( $deposit[ 'currency' ], $this->companyAttr( 'currency', $uid ), $deposit[ 'amount' ], 1 );
    }

    $details[ 'home.newcust.dc' ]  = 0;
    $details[ 'home.newcust.dc' ]  = $d_counter;
    $details[ 'home.newcust.udc' ] = 0;
    $details[ 'home.newcust.udc' ] = count( $udc );
    $details[ 'home.newcust.td' ]  = $td / 100;

    if ( !empty( $range ) ) {
      if ( $range == 'year' )
        $active_range = $this->getCompanyUsersActivity( $campaigns, $sdate, $edate, 'YEAR(date), uid' );
      else if ( $range == 'month' )
        $active_range = $this->getCompanyUsersActivity( $campaigns, $sdate, $edate, 'Month(date), uid' );
      else if ( $range == 'day' )
        $active_range = $this->getCompanyUsersActivity( $campaigns, $sdate, $edate, 'DAY(date), uid' );
    }

    $uac                           = $this->getCompanyUsersActivity( $campaigns, $sdate, $edate, 'uid' );
    $details[ 'home.newcust.ac' ]  = 0;
    $details[ 'home.newcust.ac' ]  = count( $uac );
    $first_deposits                = $this->getCompanyUsersFirstDeposit( $campaigns, $sdate, $edate );
    $first_deposits_check          = $this->getCompanyUsersFirstDepositCheck( $uid, $sdate, $edate );
    $ftd = count( $first_deposits ) >= count( $first_deposits_check ) ? count( $first_deposits ) : count( $first_deposits_check );

    $details[ 'home.newcust.fdc' ] = 0;
    $details[ 'home.newcust.fdc' ] = $ftd;

    //    $first_activities = $this->getCompanyUsersFirstActivity( $campaigns, $sdate, $edate );
    //
    //    $details[ 'home.newcust.fac' ] = 0;
    //    $details[ 'home.newcust.fac' ] = count( $first_activities );

    if ( !empty( $range ) ) {

      $details[ 'dc-range' ] = $this->getStatsByRange( $deposits, $range, $range_count, $range_keys, 'date', 'count' );
      $details[ 'udc-range' ] = $this->getStatsByRange( $udc_range, $range, $range_count, $range_keys, 'date' );
      $details[ 'td-range' ] = $this->getStatsByRange( $deposits, $range, $range_count, $range_keys, 'date', 'amount', 0.01, $uid, true );
      $details[ 'ac-range' ] = $this->getStatsByRange( $active_range, $range, $range_count, $range_keys, 'date' );
      if(count( $first_deposits_check ) >= count( $first_deposits )){
        $details[ 'fdc-range' ] = $this->getStatsByRange( $first_deposits_check, $range, $range_count, $range_keys, 'day_date' );
      }else{
        $details[ 'fdc-range' ] = $this->getStatsByRange( $first_deposits, $range, $range_count, $range_keys, 'date' );  
      }
      //      $details[ 'fac-range' ] = $this->getStatsByRange( $first_activities, $range, $range_count, $range_keys, 'date' );
    }

    return $details;
  }

  public function getStatsByRange ( $stats, $range, $range_count, $range_keys, $date_field, $count_field = null, $count_multi = null, $uid = null, $currency = false ) {

    $date_stat = array();

    for ( $i = 1; $i <= $range_count; $i++ ) {

      if ( $range == 'year' )
        $date_stat[ $range_keys[ $i - 1 ] ] = 0;
      else
        $date_stat[ $i ] = 0;
    }

    foreach ( $stats as $stat ) {

      $counter = 1;

      if ( !empty( $count_field ) )
        $counter = intval( $stat[ $count_field ] );

      if ( $currency )
        $counter = chg( $stat[ 'currency' ], $this->companyAttr( 'currency', $uid ), $counter, 1 );

      if ( $range == 'year' )
        $date_stat[ intval( explode( '-', $stat[ $date_field ] )[ 0 ] ) ] += $counter;
      else if ( $range == 'month' )
        $date_stat[ intval( explode( '-', $stat[ $date_field ] )[ 1 ] ) ] += $counter;
      else
        $date_stat[ intval( explode( '-', $stat[ $date_field ] )[ 2 ] ) ] += $counter;
    }

    if ( !empty( $count_multi ) ) {

      foreach ( $date_stat as $key => $num ) {

        $date_stat[ $key ] = $num * $count_multi;
      }
    }

    return $date_stat;
  }

  public function getCompanyUsers ( $campaigns, $ssignup = null, $esignup = null, $sactivity = null, $eactivity = null, $country = null, $product = null ) {

    $where = '';
    $join = '';
    $select = '';

     if ( !empty( $ssignup ) )
       $where .= " AND pr.date >= '$ssignup 00:00:00'";
 
     if ( !empty( $esignup ) )
       $where .= " AND pr.date <= '$esignup 23:59:59'";
 
     if ( !empty( $country ) )
       $where .= " AND pr.country = '$country'";
 
     if ( !empty( $product ) )
       $where .= " AND pr.product = '$product'";
 
     if ( !empty( $eactivity ) && !empty( $sactivity ) ) {
 
        $join = "INNER JOIN pixel_user_login pul
               ON pr.uid = pul.uid";
 
        $select = ", pul.* ";
 
        $where .= " AND pul.date <= '$eactivity 23:59:59'";
     }
 
     return phive( 'SQL' )->loadArray( "SELECT pr.*$select FROM pixel_registrar pr
                                           $join
                                           WHERE pr.bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
                                           $where
                                           GROUP BY pr.uid
                                           ORDER BY pr.uid" );
  }

  public function getCompanyUsersDaily ( $campaigns, $ssignup = null, $esignup = null, $sactivity = null, $eactivity = null, $country = null, $product = null ) {

    $where = '';
    $join = '';
    $select = '';

     if ( !empty( $ssignup ) )
       $where .= " AND uds.date >= '$ssignup 00:00:00'";
 
     if ( !empty( $esignup ) )
       $where .= " AND uds.date <= '$esignup 23:59:59'";
 
     if ( !empty( $country ) )
       $where .= " AND uds.country = '$country'";
 
     if ( !empty( $product ) )
       $where .= " AND uds.product = '$product'";
 
     if ( !empty( $eactivity ) && !empty( $sactivity ) ) {
 
        $join = "INNER JOIN pixel_user_login pul
               ON pr.uid = pul.uid";
 
        $select = ", pul.* ";
 
        $where .= " AND pul.date <= '$eactivity 23:59:59'";
     }
 
     return phive( 'SQL' )->loadArray( "SELECT uds.*$select FROM users_daily_stats uds
                                           $join
                                           WHERE uds.bonus_code IN ({$this->helperArrayToInStr( $campaigns )})
                                           $where
                                           GROUP BY uds.uid
                                           ORDER BY uds.uid" );
  }

  public function getUserProfit ( $uid ) {

    return phive( 'SQL' )->getValue( "SELECT SUM( before_deal ) FROM users_daily_stats WHERE user_id = $uid" );
  }

  public function getCompanyUserByID ( $campaigns, $uid ) {

    return phive( 'SQL' )->loadArray( "SELECT * FROM users_daily_stats
                                       WHERE user_id = $uid" );
  }

  public function getUserDailyStatByUserID ( $user_id, $uid, $product ) {

    return phive( 'SQL' )->loadArray( "SELECT * FROM users_daily_stats
                                        WHERE user_id = $user_id
                                        AND affe_id = {$this->getUserManager($uid)}
                                        AND product = '$product'" );
  }

  public function getUserDailyStatsByFilters ( $filters ) {

    $where = '';
    $join = '';

    if ( !empty( $filters[ 'year' ] ) )
      $where = "YEAR( us.date ) = {$filters['year']}";

    if ( !empty( $filters[ 'month' ] ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $where .= "MONTH( us.date ) = {$filters['month']}";
    }

    if ( !empty( $filters[ 'sdate' ] ) )
      $where = "us.date >= '{$filters['sdate']}'";

    if ( !empty( $filters[ 'edate' ] ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $where .= "us.date <= '{$filters['edate']}";
    }

    if ( !empty( $filters[ 'date' ] ) )
      $where = "us.date = '{$filters['date']}'";

    if ( !empty( $filters[ 'affiliate' ] ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $aff_id = $this->getUserDetailByKey( 'id', 'username', $filters[ 'affiliate' ] );
      $where .= "us.affe_id = '$aff_id'";
    }

    if ( !empty( $filters[ 'username' ] ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $where .= "us.username = '{$filters['username']}'";
    }

    if ( !empty( $filters[ 'product' ] ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $where .= "us.product = '{$filters['product']}'";
    }

    if ( !empty( $filters[ 'group' ] ) || !empty( $filters[ 'source' ] ) || !empty( $filters[ 'campaign' ] ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $campaigns = $this->getCampaignsAll( $filters[ 'campaign' ], $filters[ 'source' ], $filters[ 'group' ] );
      $campaigns = phive()->arrCol( $campaigns, 'name' );
      $campaigns = $this->helperArrayToInStr( $campaigns );

      $where .= "us.bonus_code IN ( $campaigns )";
    }

    if ( !empty( $filters[ 'other' ] ) ) {

      $join = "INNER JOIN users u ON u.id = us.affe_id
                INNER JOIN companies c ON c.company_id = u.company_id";

      foreach ( $filters[ 'other' ] as $filter ) {

        $field = '';
        $value = $filter[ 2 ];

        switch ( $filter[ 0 ] ) {

          case 'aff_name' :
            $field = 'c.name';
            break;
          case 'aff_country' :
            $field = 'c.country';
            break;
          case 'aff_reg_date' :
            $field = 'c.register_date';
            break;
          case 'aff_min_payout' :
            $field = 'c.min_payout';
            break;
          case 'aff_currency' :
            $field = 'c.currency';
            break;
          case 'aff_pm_id' :
            $field = 'c.pm_id';
            break;
          case 'aff_ca' :
            $field = 'c.ca_id';
            break;
        }

        if ( !empty( $where ) )
          $where .= " AND ";

        $where .= "{$field} {$filter[1]} '{$value}'";
      }
    }

    $num_cols = $this->casinoStatsCols();
    $sums = phive( 'SQL' )->makeSums( $num_cols );

    return phive( 'SQL' )->loadArray( "SELECT us.username, $sums FROM users_daily_stats us
                                         $join
                                         WHERE $where
                                         GROUP BY username" );
  }

  function casinoStatsCols () {

    return array( 'us.ndeposits', 'us.nwithdrawals', 'us.nbusts', 'us.deposits', 'us.withdrawals', 'us.bets', 'us.tax', 'us.wins', 'us.jp_contrib', 'us.frb_ded', 'us.rewards', 'us.fails', 'us.bank_fee',
                  'us.op_fee', 'us.aff_fee', 'us.site_rev', 'us.bank_deductions', 'us.jp_fee', 'us.real_aff_fee', 'us.site_prof', 'us.gen_loyalty', 'us.paid_loyalty',
                  'us.frb_wins', 'us.frb_cost'
    );
  }

  public function getUserSettings ( $uid ) {

    return $this->sql->loadArray( "SELECT * FROM users_settings WHERE user_id = $uid" );
  }

  public function updateUserSetting ( $sid, $update ) {

    $this->sql->updateArray( 'users_settings', $update, "id = $sid" );
  }

  public function getCampaignLinkByID ( $id ) {
    $id = intval($id);
    return $this->sql->loadAssoc( "SELECT * FROM campaign_links WHERE id = $id" );
  }

  public function getCampaignLInkByProduct ( $product ) {

    return $this->sql->loadArray( "SELECT * FROM campaign_links WHERE product = '$product'" );
  }

  public function getCampaignLinks ( $keyword = null, $product = null ) {

    $where = '';

    if ( !empty( $keyword ) )
      $where .= "link_desc LIKE '%$keyword%'";

    if ( !empty( $product ) ) {

      if ( !empty( $where ) )
        $where .= " AND ";

      $where .= "product = '$product'";
    }

    if ( !empty( $where ) )
      $where = "WHERE $where";

    return $this->sql->loadArray( "SELECT * FROM campaign_links $where" );
  }

  public function addCampaignLink ( $insert_link, $insert_lang ) {

    $this->sql->save( 'campaign_links', $insert_link );
    $camp_link_id = $this->sql->insertBigId();

    if ( !empty( $insert_lang ) ) {

      foreach ( $insert_lang as $lang ) {

        $lang[ 'campaign_link_id' ] = $camp_link_id;
        $this->sql->save( 'campaign_links_langs', $lang );
      }
    }
  }

  public function addCampaignLinkLang ( $insert_lang ) {

    $this->sql->save( 'campaign_links_langs', $insert_lang );

    return $this->sql->insertBigId();
  }

  public function deleteCampaignLink ( $link_id = null, $lang_id = null ) {

    if ( !empty( $link_id ) ) {

      $this->sql->delete( 'campaign_links_langs', "campaing_link_id = $link_id" );
      $this->sql->delete( 'campaign_links', "id = $link_id" );
    }
    else if ( !empty( $lang_id ) ) {

      $this->sql->delete( 'campaign_links_langs', "id = $lang_id" );
    }
  }

  public function updateCampaignLink ( $id, $update ) {

    $this->sql->updateArray( 'campaign_links', $update, "id = $id" );
  }

  public function updateCampaignLinkLang ( $id, $update ) {

    $this->sql->updateArray( 'campaign_links_langs', $update, "id = $id" );
  }

  public function updateCompanyAffiliateManager ( $cid, $manager ) {

    $this->sql->updateArray( 'companies', [ 'aff_manager' => $manager ], "company_id = $cid" );
  }

  public function getCompaniesByAffiliateManager ( $manager ) {

    $this->sql->loadArray( "SELECT * FROM companies WHERE aff_manager = '$manager'" );
  }

  public function getCampaignLangs ( $link ) {

    return $this->sql->loadArray( "SELECT * FROM campaign_links_langs WHERE campaign_link_id = $link" );
  }

  public function getCampaignLangsByID ( $id ) {

    return $this->sql->loadAssoc( "SELECT * FROM campaign_links_langs WHERE id = $id" );
  }

  public function getMarketingDomainsByCampaignID ( $id ) {

    return $this->sql->loadArray( "SELECT * FROM marketing_domains WHERE campaign_id = $id" );
  }

  public function getPermissionTags () {

    return $this->sql->loadArray( "SELECT * FROM permission_tags" );
  }

  public function getPermissionUsers () {

    return $this->sql->loadArray( "SELECT * FROM permission_users" );
  }

  public function addPermissionTag ( $insert ) {

    $this->sql->save( 'permission_tags', $insert );
  }

  public function addPermissionTagUser ( $insert ) {

    $this->sql->save( 'permission_users', $insert );
  }

  public function deletePermissionTagUser ( $uid, $tag ) {

    $this->sql->delete( 'permission_users', "user_id = $uid AND tag = '$tag'" );
  }

  public function deletePermissionTag ( $tag ) {

    $this->sql->delete( 'permission_tags', "tag = '$tag'" );
  }

  public function updatePermissionTag ( $id, $update ) {

    $this->sql->updateArray( 'permission_tags', $update, "tag = '$id'" );
    $this->sql->updateArray( 'permission_users', [ 'tag' => $update[ 'tag' ] ], "tag = '$id'" );
    $this->sql->updateArray( 'permission_groups', [ 'tag' => $update[ 'tag' ] ], "tag = '$id'" );
  }

  public function addPermissionGroup ( $insert ) {

    $this->sql->save( 'groups', $insert );
  }

  public function addPermissionGroupUser ( $insert ) {

    $this->sql->save( 'groups_members', $insert );
  }

  public function addPermissionGroupTag ( $insert ) {

    $this->sql->save( 'permission_groups', $insert );
  }

  public function getPermissionGroups () {

    return $this->sql->loadArray( "SELECT * FROM groups" );
  }

  public function getPermissionGroupsByID ( $gid ) {
    $gid = intval($gid);
    return $this->sql->loadAssoc( "SELECT * FROM groups WHERE group_id = $gid" );
  }

  public function getPermissionGroupMembersCount () {

    return $this->sql->load2DArr( "SELECT group_id, COUNT( 'user_id' ) AS count FROM groups_members GROUP BY group_id", 'group_id' );
  }

  public function getPermissionGroupTagsCount () {

    return $this->sql->load2DArr( "SELECT group_id, COUNT( 'tag' ) AS count FROM permission_groups GROUP BY group_id", 'group_id' );
  }

  public function getPermissionGroupMembers () {

    return $this->sql->load2DArr( "SELECT user_id FROM groups_members", 'group_id' );
  }

  public function getPermissionGroupMembersDetails () {

    return $this->sql->load2DArr( "SELECT gm.group_id, u.id, u.username, u.firstname, u.lastname FROM groups_members gm
                                    JOIN users u
                                    ON u.id = gm.user_id", 'group_id' );
  }

  public function getPermissionGroupTags () {

    return $this->sql->load2DArr( "SELECT * FROM permission_groups", 'group_id' );
  }

  public function getPermissionGroupDetails () {

    $details = [ ];

    $groups = $this->getPermissionGroups();
    $members = $this->getPermissionGroupMembersCount();
    $tags = $this->getPermissionGroupTagsCount();

    foreach ( $groups as $group ) {

      $details[] = [
        'id'      => $group[ 'group_id' ],
        'name'    => $group[ 'name' ],
        'members' => $members[ $group[ 'group_id' ] ][ 0 ][ 'count' ],
        'tags'    => $tags[ $group[ 'group_id' ] ][ 0 ][ 'count' ]
      ];
    }

    return $details;
  }

  public function deletePermissionGroup ( $gid ) {

    $this->sql->delete( 'groups', "group_id = '$gid'" );
  }

  public function updatePermissionGroup ( $id, $update ) {

    $this->sql->updateArray( 'groups', $update, "group_id = '$id'" );
  }

  public function deleteGroupMember ( $gid, $uid ) {

    $this->sql->delete( 'groups_members', "group_id = $gid AND user_id = $uid" );
  }

  public function deleteGroupTag ( $gid, $tag ) {

    $this->sql->delete( 'permission_groups', "group_id = $gid AND tag = '$tag'" );
  }

  public function getAffiliateManagerByID ( $afid ) {
    $afid = intval($afid);
    return $this->sql->loadAssoc( "SELECT * FROM affiliate_managers WHERE id = $afid" );
  }

  public function getAffiliateManagerByCompany ( $cid ) {
    $cid = intval($cid);
    return $this->sql->loadAssoc( "SELECT * FROM affiliate_managers WHERE company_id = $cid" );
  }

  public function getAffiliateRateNote ( $cid ) {
    $cid = intval($cid);
    return $this->sql->loadAssoc( "SELECT * FROM affiliate_rate_notes WHERE company_id = $cid" );
  }

  public function updateAffiliateRateNote ( $note, $cid, $id = null ) {
    $cid = intval($cid);
    $id = intval($id);
    if ( empty( $note ) ) {

      $this->sql->delete( 'affiliate_rate_notes', "company_id = $cid" );
    }
    else {

      if ( empty( $id ) ) {

        $this->sql->save( 'affiliate_rate_notes', [ 'company_id' => $cid, 'note' => $note ] );
      }
      else {

        $this->sql->updateArray( 'affiliate_rate_notes', [ 'note' => $note ], "id = $id AND company_id = $cid" );
      }
    }
  }

  public function getMarketingDomainsByUser ( $uid = null, $name = '' ) {

    $campaigns = $this->getCompanyCampaigns( $uid );
    $campaign_ids = phive()->arrCol( $campaigns, 'id' );
    $campaign_ids_str = $this->helperArrayToInStr( $campaign_ids );

    return $this->sql->loadArray( "SELECT * FROM marketing_domains WHERE campaign_id IN ( $campaign_ids_str ) AND name LIKE '%$name%'" );
  }

  //TODO when doing a project search for usage I only see it used with the defaults which should pretty much make this one replaceable by SQL->makeIn or?
  public function helperArrayToInStr ( $array, $wrapper = "'", $seperator = ',', $spacer = ' ' ) {

      return phive('SQL')->makeIn($array);

      /*
      $in_str = '';

      if ( !is_array( $array ) )
          return ( $wrapper . $array . $wrapper );

      $new_array = array();

      //just loop and reassign with array_unique afterwards
      foreach ( $array as $item ) {

          if ( !in_array( $item, $new_array ) )
              $new_array[] = $item;
      }

      foreach ( $new_array as $item ) {
          $in_str .= ( $wrapper . $item . $wrapper . $seperator . $spacer );
      }

      return substr( $in_str, 0, -2 );
      */
  }

  //TODO can't phive()->getDateInterval() be used here instead?
  public function helperDateRange ( $strDateFrom, $strDateTo ) {

    $aryRange = array();

    //why doesn't strtotime work here?
    $iDateFrom = mktime( 1, 0, 0, substr( $strDateFrom, 5, 2 ), substr( $strDateFrom, 8, 2 ), substr( $strDateFrom, 0, 4 ) );
    $iDateTo = mktime( 1, 0, 0, substr( $strDateTo, 5, 2 ), substr( $strDateTo, 8, 2 ), substr( $strDateTo, 0, 4 ) );

    if ( $iDateTo >= $iDateFrom ) {
      array_push( $aryRange, date( 'Y-m-d', $iDateFrom ) ); // first entry
      while ( $iDateFrom < $iDateTo ) {
        $iDateFrom += 86400; // add 24 hours
        array_push( $aryRange, date( 'Y-m-d', $iDateFrom ) );
      }
    }

    return $aryRange;
  }

  //TODO former with getFullMonths should work, simply use Localizer->setLanguage before and after if you suddenly need to swithch language in the middle of things, if so why do you need to do that?
  public function helperFullMonths () {

    $uh = phive( 'UserHandler' );

      $lang = cuAttr('preferred_lang');

    $rarr = array();

    foreach ( range( 1, 12 ) as $num ) {
      $rarr[ intval( phive( 'Former' )->fc()->pad( $num ) ) ] = t( "month.$num", $lang );
    }

    return $rarr;
  }

  //TODO, I've added a third argument to phive()->getDateInterval, use it instead
  public function helperMonthsRange ( $date1, $date2 ) {

    //return phive()->getDateInterval($date1, $date2, '+1 month');

    $output = [ ];
    $time = strtotime( $date1 );
    $last = date( 'm-Y', strtotime( $date2 ) );

    do {
      $month_year = date( 'm-Y', $time );
      $month = date( 'm', $time );
      $year = date( 'Y', $time );

      $output[] = "$year-$month-01";

      $time = strtotime( '+1 month', $time );
    } while ( $month_year != $last );

    return $output;
  }

  public function helperArraySearchI ( $needle, $haystack ) {

    return array_search( strtolower( $needle ), array_map( 'strtolower', $haystack ) );
  }

  public function helperInArrayI ( $needle, $haystack ) {

    return in_array( strtolower( $needle ), array_map( 'strtolower', $haystack ) );
  }

  public function helperArrayKeyExistsI ( $needle, $haystack ) {

    $allKeys = array_keys( $haystack );
    $allKeysLower = array_map( 'strtolower', $allKeys );

    $idx = array_search( strtolower( $needle ), $allKeysLower );
    if ( $idx === false ) {
      return false;
    }
    else {
      return $allKeys[ $idx ];
    }
  }

  public function extractToCSV ( $data, $headers, $filename = 'report', $delimiter = ',' ) {

    header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );

    $output = fopen( 'php://output', 'w' );

    //cool, didn't know it existed
    fputcsv( $output, $headers, $delimiter );

    foreach ( $data as $row )
      fputcsv( $output, $row, $delimiter );
  }

  public function sendMailPR ( $mail_trigger, $user, $replacers = null, $lang = null, $from = null, $reply_to = null, $bcc = null, $cc = null, $priority = 1 ) {

    if ( !is_object( $user ) ) {

      $email = $user;

      $lang = phive( 'Localizer' )->getDefaultLanguage();
    }
    else {

      $email = $this->getCompanyAdminEmail( $user->getAttribute( 'id' ) )[ 0 ][ 'email' ];

      if ( $lang === null ) {
        $lang = $user->getAttribute( "preferred_lang" );

        if ( empty( $lang ) )
          $lang = phive( 'Localizer' )->getDefaultLanguage();
      }

      if ( $replacers === null )
        $replacers = $this->getDefaultReplacers( $user );
    }

    if ( empty( $email ) )
      return false;

    return phive( 'MailHandler' )->sendMailToEmail( $mail_trigger, $email, $replacers, $lang, $from, $reply_to, $bcc, $cc, $priority );
  }

  public function logAction ( $target, $descr, $tag, $add_uname = false, $actor = null ) {

    if ( is_numeric( $target ) )
      $target_id = $target;
    else
      $target_id = is_object( $target ) ? $target->getId() : $target;

    if ( empty( $actor ) )
      $actor = $this->getUser();
    else if ( is_numeric( $actor ) )
      $actor = $this->getUser( $actor );

    if ( empty( $actor ) ) {
      $actor_id = ud( 'system' )['id'];
      $actor_uname = 'system';
    }
    else {
      $actor_id = $actor->getId();
      $actor_uname = $actor->getUsername();
    }

    $insert = array(
      'actor'  => $actor_id,
      'target' => $target_id,
      'descr'  => $add_uname ? $actor_uname . " $descr" : $descr,
      'tag'    => $tag
    );

    return phive( 'SQL' )->insertArray( 'actions', $insert );
  }

  public function logIp ( $aid, $tid, $tag, $descr, $tr_id = 0 ) {

    if ( is_object( $tid ) )
      $tid = $tid->getId();
    if ( is_object( $aid ) )
      $aid = $aid->getId();
    phive( 'SQL' )->insertArray( 'ip_log', array(
      'ip_num' => remIp(),
      'actor'  => $aid,
      'target' => $tid,
      'descr'  => $descr,
      'tag'    => $tag,
      'tr_id'  => $tr_id
    ) );

  }

  public function getDefaultReplacers ( $user ) {

    $ret = array();

    $ret[ "__USERNAME__" ] = $user->data[ 'username' ];
    $ret[ '__FULLNAME__' ] = $user->data[ 'firstname' ] . ' ' . $user->data[ 'lastname' ];
    $ret[ '__USERID__' ] = $user->data[ 'id' ];
    $ret[ '__CURRENCY__' ] = phive( "Currencer" )->getCurSym( $this->getUserCompany( $user->data[ 'id' ] )[ 'currency' ] );
    $ret[ '__EMAIL__' ] = $this->getCompanyAdminEmail( $user->data[ 'id' ] )[ 0 ][ 'email' ];
    $ret[ '__COUNTRY__' ] = $this->getUserCompany( $user->data[ 'id' ] )[ 'country' ];
    $ret[ '__FIRSTNAME__' ] = $user->data[ 'firstname' ];

    return $ret;
  }

    /**
     * Return affordability checks for a user
     *
     * @param int $user_id
     * @return mixed
     */
    public function getAffordabilityChecks(int $user_id)
    {
        $where = "user_id = $user_id AND `type` = 'affordability'";
        return phive('SQL')->sh($user_id)->arrayWhere('responsibility_check', "$where");
    }

    /**
     * Return vulnerability checks for a user
     *
     * @param int $user_id
     * @return mixed
     */
    public function getVulnerabilityChecks(int $user_id)
    {
        $where = "user_id = $user_id AND `type` = 'vulnerability'";
        return phive('SQL')->sh($user_id)->arrayWhere('responsibility_check', "$where");
    }
}
