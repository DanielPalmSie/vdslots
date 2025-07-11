<?php

require_once __DIR__ . '/CasinoAffiliater.php';
require_once __DIR__ . '/../UserHandler/PRUserHandler.php';
require_once __DIR__ . '/../DBUserHandler/DBUser.php';
require_once __DIR__ . '/../../../diamondbet/html/display.php';

class PRCasinoAffiliater extends CasinoAffiliater {

    function __construct() {

        $this->db = phive('SQL');
    }

    function phAliases() {
        return array('PRAffiliater');
    }

    function sumReportCols(&$period, &$date_stats, $camp_stats, $signups, $user_details, $adb_stats, $sub_period = 'daily'){
        foreach ($period as $dt) {
            $date_key  = $dt->format("Y-m-d");
            $month_key = $dt->format("Y-m");
            $day_key   = $dt->format("j");
            $use_key   = $sub_period == 'daily' ? $date_key : $month_key;
            $date_stats[$use_key]['clicks']       += $camp_stats['clicks'][$date_key];
            $date_stats[$use_key]['loads']        += $camp_stats['loads'][$date_key];
            $date_stats[$use_key]['signups']      += $signups[$date_key];
            $date_stats[$use_key]['act_cust']     += $user_details[$month_key]['ac-range'][$day_key];
            $date_stats[$use_key]['new_dep_cust'] += $user_details[$month_key]['udc-range'][$day_key];
            $date_stats[$use_key]['st_dep_cust']  += $user_details[$month_key]['fdc-range'][$day_key];
            //$date_stats[$use_key]['total_dep']    += $user_details[$month_key]['td-range'][$day_key];
            $date_stats[$use_key]['deposits']           = $adb_stats[$use_key]['deposits'];
            $date_stats[$use_key]['gross']              = $adb_stats[$use_key]['gross'];
            $date_stats[$use_key]['before_deal']        = $adb_stats[$use_key]['before_deal'];
            $date_stats[$use_key]['real_prof']          = $adb_stats[$use_key]['real_prof'];
            $date_stats[$use_key]['op_fees']            = $adb_stats[$use_key]['op_fees'];
            $date_stats[$use_key]['op_fee']             = $adb_stats[$use_key]['op_fee'];
            $date_stats[$use_key]['jp_fees']            = $adb_stats[$use_key]['jp_fees'];
            $date_stats[$use_key]['jp_fee']             = $adb_stats[$use_key]['jp_fee'];
            $date_stats[$use_key]['jp_contrib']         = $adb_stats[$use_key]['jp_contrib'];
            $date_stats[$use_key]['bank_fees']          = $adb_stats[$use_key]['bank_fees'];
            $date_stats[$use_key]['bank_fee']           = $adb_stats[$use_key]['bank_fee'];
            $date_stats[$use_key]['bank_deductions']    = $adb_stats[$use_key]['bank_deductions'];
            $date_stats[$use_key]['fails']              = $adb_stats[$use_key]['fails'];
            $date_stats[$use_key]['prof']               = $adb_stats[$use_key]['prof'];
            $date_stats[$use_key]['cpa_prof']           = $adb_stats[$use_key]['cpa_prof'];
            $date_stats[$use_key]['wins']               = $adb_stats[$use_key]['wins'];
            $date_stats[$use_key]['real_aff_fee']       = $adb_stats[$use_key]['real_aff_fee'];
            $date_stats[$use_key]['rewards']            = $adb_stats[$use_key]['rewards'];
            $date_stats[$use_key]['chargebacks']        = $adb_stats[$use_key]['chargebacks'];
            $date_stats[$use_key]['rate']               = $adb_stats[$use_key]['rate'];
            $date_stats[$use_key]['aff_rate']           = $adb_stats[$use_key]['aff_rate'];
            $date_stats[$use_key]['paid_loyalty']       = $adb_stats[$use_key]['paid_loyalty'];
            $date_stats[$use_key]['site_prof']          = $adb_stats[$use_key]['site_prof'];
            $date_stats[$use_key]['tax']                = $adb_stats[$use_key]['tax'];
            $date_stats[$use_key]['site_rev']           = $adb_stats[$use_key]['site_rev'];
            $date_stats[$use_key]['bets']               = $adb_stats[$use_key]['bets'];
            $date_stats[$use_key]['wins']               = $adb_stats[$use_key]['wins'];
            $date_stats[$use_key]['admin_fee']          = $adb_stats[$use_key]['admin_fee'];
        }
    }

    function calcOverview($day = '') {
        $uh = phive('UserHandler');
        $sql = phive('SQL');
        if (empty($day))
            $day = phive()->yesterday();

        $sql->delete('overview', ['date' => $day]);

        foreach ($sql->loadArray("SELECT * FROM affiliate_info") as $info) {

            if (is_object(cu($info['affe_id']))) {

                $insert = [];
                $uid = $info['affe_id'];
                $adbs = $sql->load2DArr("SELECT before_deal, real_prof, cpa_prof, deposits, bonus_code
                                         FROM affiliate_daily_bcodestats
                                         WHERE day_date = '$day'
                                         AND affe_id = $uid", 'bonus_code');

                if (!empty($adbs)) {

                    $details = [];
                    for ($i = 1; $i <= 5; $i++) {

                        $details['profit'][$i] = 0;
                        $details['deposits'][$i] = 0;
                        $rp_campaigns = phive()->arrCol($uh->getCompanyCampaignsByRewardPlans($i, $uid), 'name');

                        foreach ($rp_campaigns as $campaign) {
                            if (array_key_exists($campaign, $adbs)) {
                                //add the new getTotalProf() function to the overview also
                                $details['profit'][$i] += $this->getTotalProf($adbs[$campaign][0]['prof'], $adbs[$campaign][0]['cpa_prof'], 0, true);
                                //$details['profit'][$i] += intval($adbs[$campaign][0]['real_prof']) + intval($adbs[$campaign][0]['cpa_prof']);
                                $details['deposit_amount'][$i] += intval($adbs[$campaign][0]['deposits']);
                                $details['before_deal'] += intval($adbs[$campaign][0]['before_deal']);
                            }
                        }
                        $details['ndc'][$i] = count($uh->getCompanyUsersFirstDeposit($rp_campaigns, $day, $day));
                        $details['deposits'][$i] = array_sum(phive()->arrCol($uh->getCompanyUsersDeposits($rp_campaigns, $day, $day), 'count'));
                        $details['signups'][$i] = count($uh->getCompanyUsersFirstActivity($rp_campaigns, $day, $day));
                    }

                    $insert['date'] = $day;
                    $insert['user_id'] = $uid;
                    $insert['before_deal'] = $details['before_deal'];
                    $insert['total_profit'] = array_sum($details['profit']);
                    $insert['rev_profit'] = $details['profit'][1];
                    $insert['hyb_profit'] = $details['profit'][2];
                    $insert['cpa_profit'] = $details['profit'][3];
                    $insert['sub_profit'] = $details['profit'][4];
                    $insert['ndc_profit'] = $details['profit'][5];
                    $insert['signups'] = array_sum($details['signups']);
                    $insert['rev_signups'] = $details['signups'][1];
                    $insert['hyb_signups'] = $details['signups'][2];
                    $insert['cpa_signups'] = $details['signups'][3];
                    $insert['sub_signups'] = $details['signups'][4];
                    $insert['ndc_signups'] = $details['signups'][5];
                    $insert['ndc'] = array_sum($details['ndc']);
                    $insert['rev_ndc'] = $details['ndc'][1];
                    $insert['hyb_ndc'] = $details['ndc'][2];
                    $insert['cpa_ndc'] = $details['ndc'][3];
                    $insert['sub_ndc'] = $details['ndc'][4];
                    $insert['ndc_ndc'] = $details['ndc'][5];
                    $insert['total_deposits'] = array_sum($details['deposits']);
                    $insert['rev_deposits'] = $details['deposits'][1];
                    $insert['hyb_deposits'] = $details['deposits'][2];
                    $insert['cpa_deposits'] = $details['deposits'][3];
                    $insert['sub_deposits'] = $details['deposits'][4];
                    $insert['ndc_deposits'] = $details['deposits'][5];
                    $insert['total_deposit_amount'] = array_sum($details['deposit_amount']);
                    $insert['rev_deposit_amount'] = $details['deposit_amount'][1] ? $details['deposit_amount'][1] : 0;
                    $insert['hyb_deposit_amount'] = $details['deposit_amount'][2] ? $details['deposit_amount'][2] : 0;
                    $insert['cpa_deposit_amount'] = $details['deposit_amount'][3] ? $details['deposit_amount'][3] : 0;
                    $insert['sub_deposit_amount'] = $details['deposit_amount'][4] ? $details['deposit_amount'][4] : 0;;
                    $insert['ndc_deposit_amount'] = $details['deposit_amount'][5] ? $details['deposit_amount'][5] : 0;;
                    if ($insert['before_deal'] != 0 || $insert['total_profit'] != 0 || $insert['signups'] != 0 || $insert['ndc'] != 0 || $insert['total_deposit'] != 0 || $insert['total_deposit_amount'] != 0) {
                        $sql->insertArray('overview', $insert);
                    }
                }
            }
        }
    }

    function getNdcCount($bcode, $sdate = null, $edate = null) {

        $date_where = '';
        $in = $this->db->makeIn($bcode);

        if (!empty($sdate) || !empty($edate))
            $date_where = "AND date BETWEEN '$sdate 00:00:00' AND '$edate 23:23:59'";

        $str = "SELECT COUNT(*) FROM pixel_first_deposit WHERE bonus_code IN($in) $date_where";

        return $this->db->getValue($str);
    }

    /*
      Calculates the actual profit numbers, updates affiliate daily bcode stats
     */
    function calcRewards($date, $uid = null, $test_reward_id = null, $test = false) {
        $uh = phive('UserHandler');
        $campaigns = [];
        if (empty($uid)) {
            $companies = $uh->getCompanies();
            foreach ($companies as $company) {
                // We get the company manager id.
                $mid = $uh->getCompanyManager($company['company_id'])['id'];
                if (empty($mid)) {
                    continue;
                }
                // This is all the bonus codes
                $company_campaigns = $uh->getCompanyCampaignsProductWhere($mid, "!= 'partnerroom'");
                if (!empty($company_campaigns)) {
                    foreach ($company_campaigns as $company_campaign) {
                        $ms_reward_id = $company_campaign['ms_reward_id'];
                        $ms_plan = $company_campaign['ms_plan'];
                        $campaigns['companies'][$company['company_id']][$ms_reward_id][$company_campaign['product']][$ms_plan][] = $company_campaign['name'];
                    }
                }
            }
        } else {
            $company_campaigns = $uh->getCompanyCampaignsProductWhere($uid, "!= 'partnerroom'");
            if (!empty($company_campaigns)) {
                $cid = phive('UserHandler')->getCompanyID($uid);
                foreach ($company_campaigns as $company_campaign) {
                    $ms_reward_id = $company_campaign['ms_reward_id'];
                    $ms_plan = $company_campaign['ms_plan'];
                    $campaigns['companies'][$cid][$ms_reward_id][$company_campaign['product']][$ms_plan][] = $company_campaign['name'];
                }
            }
        }

        $reward_plans = $uh->getRewardPlans();

        foreach ($campaigns['companies'] as $company_k => $company_v) {
            foreach ($reward_plans as $reward_plan) {
                if (empty($company_v[$reward_plan['reward_plans_id']]))
                    continue;

                /*
                   Array
                   (
                   [1] => rev
                   [2] => hyb
                   [3] => cpa
                   [4] => sub
                   [5] => ndc
                   )
                 */
                if (in_array($reward_plan['reward_plans_id'], [1, 2, 3, 5])) {
                    $type = $uh->getRewardTypeMap()[$reward_plan['reward_plans_id']];
                    if (empty($type))
                        continue;
                }

                if (!empty($test_reward_id) && $reward_plan['reward_plans_id'] != $test_reward_id)
                    continue;

                if (in_array($type, ['hyb', 'cpa'])) {
                    $this->calcCpa($company_k, $date, $company_v[$reward_plan['reward_plans_id']], $type, $test);
                }

                if (in_array($type, ['hyb', 'rev', 'ndc'])) {
                    $rev_share = $this->calcRevShare($company_k, $date, $company_v[$reward_plan['reward_plans_id']], $type, $test);
                    if($rev_share) {
                        $uds_update[] = $rev_share;
                    }
                }
            }
        }
        //TODO: check, now the product is only videoslots but maybe in the future....
        phive('PRDistAffiliater')->updateUserDailyStats('videoslots', $uds_update);

    }

    function calcSubRewards($date, $uid = null, $test = false) {

        $uh = phive('UserHandler');

        $campaigns = array();

        if (empty($uid)) {

            $companies = $uh->getCompanies();

            foreach ($companies as $company) {

                $company_campaigns = $uh->getCompanyCampaignsProductWhere($uh->getCompanyManager($company['company_id'])['id'], "= 'partnerroom'");

                if (!empty($company_campaigns)) {

                    foreach ($company_campaigns as $company_campaign) {
                        $ms_plan = $company_campaign['ms_plan'];
                        $campaigns['companies'][$company['company_id']][$ms_plan][] = $company_campaign['name'];
                    }
                }
            }
        } else {

            $company_campaigns = $uh->getCompanyCampaignsProductWhere($uid, "= 'partnerroom'");

            if (!empty($company_campaigns)) {

                $cid = phive('UserHandler')->getCompanyID($uid);

                foreach ($company_campaigns as $company_campaign) {
                    $ms_plan = $company_campaign['ms_plan'];
                    $campaigns['companies'][$cid][$ms_plan][] = $company_campaign['name'];
                }
            }
        }

        if ($test)
            d('Company and campaigns: ', $campaigns);
        if($campaigns['companies']) {
            foreach ($campaigns['companies'] as $company_k => $bonus_codes_plans) {
                $this->calcSubRevShare($company_k, $date, $bonus_codes_plans, $test);
            }
        }
    }

    function calcSubRevShare($cid, $date, $bonus_codes_plans, $test = false) {

        $uh = phive('UserHandler');

        $mid = $uh->getCompanyManager($cid)['id'];
        $affe = cu($mid);

        $sdate = date('Y-m', strtotime($date)) . '-01';
        $edate = $date;

        if (is_object($affe)) {

            foreach ($bonus_codes_plans as $plan => $bonus_codes) {

                $rates = $uh->getSubAffiliateRevShareRates($cid, null, $plan);
                $rates = phive()->sort2d($rates, 'start_amount', 'desc');

                $sub_bcode_stats = $this->getSubAffBCodeStats($mid, $sdate, $edate, $bonus_codes);
                $total_month = phive()->sum2d($sub_bcode_stats, 'before_deal');
                $total_month_eur = chg($uh->companyAttr('currency', $mid), 'EUR', $total_month, 1, $date);
                $total_month_prof_eur = $this->calcRevProfit($total_month_eur, $rates)['total'];
                $total_month_prof = chg('EUR', $uh->companyAttr('currency', $mid), intval($total_month_prof_eur), 1, $date);

                if (date('d', strtotime($edate)) == '01') {
                    $difference_prof = $total_month_prof;
                } else {
                    $prev_date = date('Y-m-d', strtotime($date . '-1 day'));
                    $prev_bcode_stats = $this->getSubAffBCodeStats($mid, $sdate, $prev_date, $bonus_codes);
                    $prev_total_prof = phive()->sum2d($prev_bcode_stats, 'real_prof');
                    $difference_prof = abs($total_month_prof - $prev_total_prof);
                }

                $today_bcode_stats = $this->getSubAffBCodeStats($mid, $edate, $edate, $bonus_codes);
                $today_total = abs(phive()->sum2d($today_bcode_stats, 'before_deal'));

                $prof_multiplier = 0;
                if ($today_total != 0) {
                    $prof_multiplier = $difference_prof / $today_total;
                }

                foreach ($today_bcode_stats as $bcode_stat) {
                    if (!empty($bcode_stat['affe_id'])) {
                        $prof = $bcode_stat['before_deal'] * $prof_multiplier;
                        phive('SQL')->insertOrUpdate('sub_affiliate_daily_stats', $bcode_stat, array('prof' => $prof, 'real_prof' => $prof));
                    }
                }
            }
        }
    }

    function calcRevShare($cid, $date, $product_campaigns, $type, $test = false) {
        $uh = phive('UserHandler');
        $mid = $uh->getCompanyManager($cid)['id'];
        if (empty($mid)){
            return;
        }
        $affe = cu($mid);
        if (!is_object($affe)) {
            return;
        }
        $sdate = date('Y-m', strtotime($date)) . '-01';
        $edate = $date;

        $map = $uh->getRewardGetMap();

        $col = 'start_amount';

        foreach ($product_campaigns as $product => $plan_campaigns) {
            foreach ($plan_campaigns as $plan => $bonus_codes) {
                if (empty($bonus_codes)) {
                    continue;
                }
                $rates = call_user_func_array([$uh, $map[$type]], [$cid, null, $plan]);
                //$rates = $uh->$map[ $type ]( $cid, null, $plan );

                $bcode_stats = $this->getAffBCodeStats($mid, $sdate, $edate, $bonus_codes);

                $total_month = phive()->sum2d($bcode_stats, 'before_deal');
                $total = chg($uh->companyAttr('currency', $mid), 'EUR', $total_month, 1, $date);
                if ($type == 'ndc') {
                    $ndc_count = $this->getNdcCount($bonus_codes, $sdate, $edate);
                    $calc_result = $this->calcNDCProfit($total, $rates, $ndc_count, $col);
                } else if ($type == 'hyb') {
                    $users = $uh->getUsersByCampaigns($bonus_codes);
                    $user_ids = phive()->arrCol($users, 'uid');
                    $hyb_count = count($uh->checkPlayersCPA($user_ids, $product, 1, $sdate, $edate));
                    $col = 'min_users';
                    $calc_result = $this->calcNDCProfit($total, $rates, $hyb_count, $col);
                } else {
                    $calc_result = $this->calcRevProfit($total, $rates);
                }
                $total_month_prof_eur = $calc_result['total'];
                $rate = $calc_result['rate'];

                $total_month_prof = chg('EUR', $uh->companyAttr('currency', $mid), intval($total_month_prof_eur), 1, $date);

                $difference_prof = abs($total_month_prof);
                if (date('d', strtotime($edate)) != '01') {
                    $prev_date = date('Y-m-d', strtotime($date . '-1 day'));
                    $prev_bcode_stats = $this->getAffBCodeStats($mid, $sdate, $prev_date, $bonus_codes);
                    $prev_total_prof = phive()->sum2d($prev_bcode_stats, 'real_prof');
                    $difference_prof = abs($total_month_prof - $prev_total_prof);
                }

                $today_bcode_stats = $this->getAffBCodeStats($mid, $edate, $edate, $bonus_codes);
                if(empty($today_bcode_stats)){
                    continue;
                }
                $today_total = abs(phive()->sum2d($today_bcode_stats, 'before_deal'));

                $prof_multiplier = 0;
                if ($today_total != 0) {
                    $prof_multiplier = $difference_prof / $today_total;
                }

                foreach ($today_bcode_stats as $bcode_stat) {

                    if (!empty($bcode_stat['affe_id'])) {

                        $prof = $bcode_stat['before_deal'] * $prof_multiplier;

                        phive('SQL')->insertOrUpdate('affiliate_daily_bcodestats', $bcode_stat, array('prof' => $prof, 'real_prof' => $prof));

                        $bcode_user_stats = $this->getUDS($edate, $edate, $bcode_stat['bonus_code']);

                        $total_bcode_users_eur = 0;

                        $bcode_prof_eur = chg($uh->companyAttr('currency', $mid), 'EUR', abs($prof), 1, $date);
                        foreach ($bcode_user_stats as $user_stat) {

                            $total_bcode_users_eur += chg($user_stat['currency'], 'EUR', $user_stat['before_deal'], 1, $date);
                            $fee_multiplier = 0;
                            if ($total_bcode_users_eur != 0) {
                                $fee_multiplier = $bcode_prof_eur / abs($total_bcode_users_eur);
                            }
                            $fee_eur = chg($user_stat['currency'], 'EUR', $user_stat['before_deal'], 1, $date) * $fee_multiplier;
                            $fee = chg('EUR', $user_stat['currency'], $fee_eur, 1, $date);

                            phive('SQL')->insertOrUpdate('users_daily_stats', $user_stat, array('aff_fee' => $fee, 'real_aff_fee' => $fee, 'aff_rate' => $rate));

                            $uds_update = [
                                'id' => $user_stat['uds_id'],
                                'fee' => $fee,
                                'rate' => $rate
                            ];
                        }
                    }
                }
            }
        }

        unset($product_campaigns);
        if (!empty($uds_update)) {
            return $uds_update;
        }
        return false;
    }

    function calcNDCProfit($total, $rates, $ndcs, $col) {

        $rates = phive()->sort2d($rates, $col, 'asc');
        $rl = $rates;

        if (empty($rl)){
            return ['total' => $total * 0.25, 'rate' => 0.25];
        }
        if (count($rl) == 1) {
            return ['total' => $total * $rl[0]['rate'], 'rate' => $rl[0]['rate']];
        }

        if ($ndcs == 0 && $rl[0][$col] > 0) {
            return ['total' => 0, 'rate' => 0];
        }

        for ($i = 0; $i < count($rl); $i++) {

            $rate = $rl[$i]['rate'];

            if (empty($rl[$i + 1])) {
                return ['total' => $rate * $total, 'rate' => $rate];
            }
            if ($ndcs < $rl[$i + 1][$col]) {
                return ['total' => $rate * $total, 'rate' => $rate];
            }
        }
    }

    function calcRevProfit($total, $rates) {

        $sign = 1;

        if ($total < 0)
            $sign = -1;

        $total = abs($total);
        $rates = phive()->sort2d($rates, 'start_amount', 'asc');
        $rl = $rates;

        if (empty($rl))
            return ['total' => $total * 0 * $sign, 'rate' => 0];
        if (count($rl) == 1)
            return ['total' => $total * $rl[0]['rate'] * $sign, 'rate' => $rl[0]['rate']];

        for ($i = 0; $i < count($rl); $i++) {

            $rate = $rl[$i]['rate'];

            if (empty($rl[$i + 1]))
                return ['total' => $rate * $total * $sign, 'rate' => $rate];

            if ($total < $rl[$i + 1]['start_amount'])
                break;
        }

        return ['total' => $total * $rate * $sign, 'rate' => $rate];
    }

    function getAffBCodeStats($aid, $sdate, $edate, $bonus_codes = null) {

        $bcodes = '';

        if (!empty($bonus_codes)) {

            $bcodes = phive('UserHandler')->helperArrayToInStr($bonus_codes);
            $bcodes = "AND bonus_code IN ($bcodes)";
        }

        $str = "SELECT * FROM affiliate_daily_bcodestats
              WHERE affe_id = $aid
                  AND day_date >= '$sdate'
                  AND day_date <= '$edate'
                  $bcodes";

        return phive('SQL')->loadArray($str);
    }

    function getSubAffBCodeStats($aid, $sdate, $edate, $bonus_codes = null) {

        $bcodes = '';

        if (!empty($bonus_codes)) {
            $bcodes = phive('UserHandler')->helperArrayToInStr($bonus_codes);
            $bcodes = "AND bonus_code IN ($bcodes)";
        }

        return phive('SQL')->loadArray(
                        "SELECT * FROM sub_affiliate_daily_stats
        WHERE affe_id = $aid
        AND day_date >= '$sdate'
        AND day_date <= '$edate'
        $bcodes"
        );
    }

    function updateUDSCPAFees($aff_fee, $id, $day) {

        $currency = phive('SQL')->getValue('SELECT currency FROM users_daily_stats WHERE id = $id');
        $aff_fee = chg('EUR', $currency, $aff_fee, 1, $day);
        phive('SQL')->updateArray('users_daily_stats', array('cpa_fee' => $aff_fee), array('id' => $id));
    }

    function calcCpa($cid, $date, $product_campaigns, $type, $test = false) {
        $uh = phive('UserHandler');
        $mid = $uh->getCompanyManager($cid)['id'];
        if (empty($mid)) {
            error_log("company $cid does not have a manager with id: $mid");
            return;
        }
        $mid_obj = cu($mid);
        if (empty($mid_obj)) {
            error_log("company $cid does not have a manager with id: $mid");
            return;
        }

        //$extra_countries    = $mid_obj->getSetting('extra_countries');
        //$standard_countries = phive('Config')->getValue('cpa', 'standard_countries');
        //$allowed_countries  = array_merge($standard_countries, $extra_countries);

        $sdate = $edate = $date;
        $allowed_countries = ['SE','FI','NO','NL','GB'];

        foreach ($product_campaigns as $product => $plan_campaigns) {
            foreach ($plan_campaigns as $plan => $bonus_codes) {

                if ($type == 'cpa')
                    $rates = $uh->getAffiliateCPARates($cid, null, $plan);
                else if ($type == 'hyb')
                    $rates = $uh->getAffiliateHybridRates($cid, null, $plan);

                $rates = phive()->sort2d($rates, 'min_users', 'asc');

                $bcode_stats = $this->getAffBCodeStats($mid, $sdate, $edate, $bonus_codes);
                if (empty($bonus_codes))
                    continue;

                $day_cpa_players       = $bcode_day_cpa_players = [];
                $cur_month_cpa_players = 0;
                $rate_deposit          = intval($rates[0]['deposit_amount']);
                $rate_wager            = intval($rates[0]['wager_amount']);
                $rate_range            = $rates[0]['days_range'] - 1;

                //From this until current day is the time period they have in order to reach the wager / deposit requirements
                $cpa_sdate             = phive()->modDate($edate, "-$rate_range days");
                //From this until current date is the time period we count valid players towards the cpa ladder
                $month_start           = date('Y-m-01', strtotime($edate));

                foreach ($bonus_codes as $bonus_code) {
                    $users                 = $uh->getUsersByCampaigns($bonus_code, $cpa_sdate, $edate, $allowed_countries);
                    $uh->insertUniquePlayers($users, $product, 'uid', $edate);
                    $user_ids              = phive()->arrCol($users, 'uid');
                    //Ids of users that have registered within the required period and that have not reached the goal yet
                    $cpa_user_ids          = phive()->arrCol($uh->checkPlayersCPA($user_ids, $product, 0, $cpa_sdate, $edate), 'user_id');
                    $udstats               = $this->getStatByUsersAndDate($cpa_user_ids, $cpa_sdate, $edate, $product);
                    $users                 = $uh->getUsersByCampaigns($bonus_code);
                    $user_ids              = phive()->arrCol($users, 'uid');
                    //We get all players who have reached the goals
                    $cur_month_cpa_players += count($uh->checkPlayersCPA($user_ids, $product, 1, $month_start, $edate));
                    foreach ($udstats as $key => $udstat) {

                        if (!empty($udstat['deposits']) || !empty($udstat['bets'])) {

                            $cur_deposits   = chg($udstat['currency'], 'EUR', intval($udstat['deposits']), 1, $date);
                            $cur_dep_thold  = $rate_deposit;
                            $cur_bets       = chg($udstat['currency'], 'EUR', intval($udstat['bets']), 1, $date);
                            $cur_bets_thold = $rate_wager;

                            if ($cur_deposits >= $cur_dep_thold && $cur_bets >= $cur_bets_thold) {
                                $uh->updatePlayerCPA($udstat['user_id'], $edate);
                                $day_cpa_players[] = ['id' => $udstat['id'], 'uid' => $udstat['user_id']];
                                $bcode_day_cpa_players[$bonus_code][] = ['id' => $udstat['id'], 'uid' => $udstat['user_id']];
                            }

                            if ($udstat['deposits'] > 0) {
                                //we mark the first deposit date
                                if (empty(phive('SQL')->getValue("SELECT deposit_date FROM players WHERE user_id = {$udstat['user_id']} AND product = '$product'")))
                                    phive('SQL')->updateArray('players', ['deposit_date' => $edate], "user_id = {$udstat['user_id']} AND product = '$product'");
                            }

                            if ($cur_bets >= intval($rate_wager)) {
                                //we mark the first day we reach the minimum wager req
                                if (empty(phive('SQL')->getValue("SELECT wager_date FROM players WHERE user_id = {$udstat['user_id']} AND product = '$product'")))
                                    phive('SQL')->updateArray('players', ['wager_date' => $edate], "user_id = {$udstat['user_id']} AND product = '$product'");
                            }
                        }
                    }
                }

                $cpa_prof = 0;
                $day_cpa_players_count = count($day_cpa_players);

                if (!empty($day_cpa_players)) {
                    $cur_month_cpa_players++;
                    foreach ($rates as $rate) {
                        while ($cur_month_cpa_players <= $rate['max_users'] || $rate['max_users'] == '-1') {
                            $cur_player = array_shift($day_cpa_players);
                            if (empty($cur_player))
                                break 2;
                            $cpa_prof += $rate['gift'];
                            $this->updateUDSCPAFees($rate['gift'], $cur_player, $date);
                            $uh->updatePlayerCPA($cur_player, $edate);
                            $cur_month_cpa_players++;
                        }
                    }
                }

                if ($cpa_prof > 0) {
                    $per_user_profit = ( $cpa_prof / $day_cpa_players_count );
                    foreach ($bcode_stats as $bcode_stat) {
                        $per_bcode_profit = ( $per_user_profit * count($bcode_day_cpa_players[$bcode_stat['bonus_code']]) );
                        $cpa = chg('EUR', $uh->companyAttr('currency', $mid), intval($per_bcode_profit), phive('Affiliater')->getSetting('conversion_fee'), $date);
                        phive('SQL')->updateArray('affiliate_daily_bcodestats', array('cpa_prof' => $cpa), array('id' => $bcode_stat['id']));
                    }
                }
            }
        }
    }

    function insertExtAffiliate($cid, $uid, $ext = true) {

        if ($this->getSetting('skip_ext') == true)
            $ext = false;

        $info = $this->getInfo($uid);

        if (empty($info)) {

            if ($ext) {

                $ext_aff = phive('ExtAffiliater')->insertAffiliate(phive('UserHandler')->getCompanyManager($cid)['username']);

                if (!empty($ext_aff))
                    return phive('SQL')->save('affiliate_info', array('affe_id' => $uid, 'ext_pwd' => $ext_aff['pw']));
            } else
                return phive('SQL')->save('affiliate_info', array('affe_id' => $uid, 'ext_pwd' => uniqid()));
        }
    }

    function getUDS($sdate = null, $edate = null, $campaigns = null) {

        $where_date = $where_campaigns = '';

        if (!empty($sdate) && !empty($edate)) {
            $where_date = "date >= '$sdate' AND date <= '$edate'";
        }

        if (!empty($campaigns)) {
            $campaigns_str = phive('UserHandler')->helperArrayToInStr($campaigns);
            $where_campaigns = "bonus_code IN ($campaigns_str)";

            if (!empty($where_date)) {
                $where_campaigns = "AND " . $where_campaigns;
            }
        }
        return phive('SQL')->loadArray("SELECT * FROM users_daily_stats WHERE $where_date $where_campaigns");
    }

    function getUDSGroupByUser($campaigns) {

        $campaigns_str = phive('UserHandler')->helperArrayToInStr($campaigns);
        return phive('SQL')->loadArray("SELECT * FROM users_daily_stats
                                        WHERE bonus_code");
    }

    function updateUDS($stats, $admin_pc) {

        foreach ($stats as $stats_bcs) {
            foreach ($stats_bcs as $stats_users) {
                foreach ($stats_users as $users) {
                    $update = [
                        'before_deal' => $users['before_deal'] - ( $users['gross'] * $admin_pc )
                    ];
                    phive('SQL')->updateArray('users_daily_stats', $update, array('id' => $users['id']));
                }
            }
        }
    }

    /*
      Prepares daily bcode stats with base data such as gross and before deal, no profits are inserted at this point
     */

    function insertBCodeStat($day, $aid = '', $test = false, $update_uds = true) {
        $count = 0;
        $where = '';
        if (!empty($aid)) {
            $where = " WHERE affe_id = $aid ";
        }

        $sql = phive('SQL');
        $curs = phive("Currencer")->getAllCurrencies();
        $conversion_fee = phive('Affiliater')->getSetting('conversion_fee');
        //bets - wins - jp_contrib = gross
        //gross - op_fees - bank_fees - rewards - chargebacks - paid_loyalty - tax = before_deal
        //before_deal -> func = prof / aff_fee
        //
        //More reasonable:
        //gross
        //gross - op_fees - bank_fees (includes chargebacks etc) - rewards (includes loyalty etc) - tax = before_deal
        //
        //what do we need?:
        //deposit amount
        //withdraw amounts?
        //gross amount?
        //before deal amount?
        /* difference between bank and transfer fees:bank fee: chargebacks + transfer fees + sms feestransfer fee: deposit fees + withdrawal feesPlayer deductions are ignored
         * CasinoCashier->calcUserCache
         */
        $num_cols = array(
            'gross' => 'gross',
            'bets' => 'bets', //*
            'wins' => 'wins', //*
            'deposits' => 'deposits', //*
            'op_fees' => 'op_fee',
            'bank_fees' => 'bank_fee',
            'before_deal' => 'before_deal',
            'fails' => 'fails',
            'rewards' => 'rewards',
            'prof' => 'aff_fee',
            'real_prof' => 'real_aff_fee',
            'chargebacks' => 'chargebacks',
            'transfer_fees' => 'transfer_fees',
            'paid_loyalty' => 'paid_loyalty',
            'tax' => 'tax',
            'jp_fee' => 'jp_fee',
            'cpa_prof' => 'cpa_fee'
        );
        $infos = phive('SQL')->loadArray("SELECT affe_id FROM affiliate_info $where");
        foreach ($infos as $info) {
            $mid = $info['affe_id'];
            $affe = phive("UserHandler")->getUser($mid);
            if (!is_object($affe))
                continue;
            $DBUser = new DBUser(null, $mid);
            $admin_pc = $DBUser->getSettingOrGlobal('affiliate_admin_fee', 'affiliate', 'admin-fee');
            $bcodes = phive()->arrCol(phive('UserHandler')->getCompanyCampaignsProductWhere($mid, "!= 'partnerroom'"), 'name');
            if (empty($bcodes))
                continue;
            $stats = $this->getUDS($day, $day, $bcodes);
            $affe_cur = phive('UserHandler')->companyAttr('currency', $mid);
            $bc_insert = $bc_uds_stats = [];
            foreach ($stats as $r) {
                $bonus_code = strtolower($r['bonus_code']);
                $cur = array();
                foreach ($num_cols as $affkey => $usrkey) {
                    $val = $r['currency'] == $affe_cur ? $r[$usrkey] : chg($r['currency'], $affe_cur, $r[$usrkey], $conversion_fee, $day);
                    $cur[$affkey] = floatval($val);
                    if (phive('UserHandler')->helperInArrayI($r['bonus_code'], $bcodes)) {
                        $bc_insert[$bonus_code][$affkey] += floatval($val);
                    }
                }
                $admin_fee = $cur['gross'] * $admin_pc;
                $gross_deduction = $admin_fee + $cur['jp_fee']; //old betsoft stuff that's irrelevant these days
                if (phive('UserHandler')->helperInArrayI($r['bonus_code'], $bcodes)) {
                    $bc_insert[$bonus_code]['before_deal'] -= $admin_fee;
                    $bc_insert[$bonus_code]['gross'] -= $gross_deduction;
                    $bc_insert[$bonus_code]['admin_fee'] += $admin_fee;
                    $bc_insert[$bonus_code]['product'] = $r['product'];
                    $bc_uds_stats[$bonus_code]['user_daily_stats'][] = array('id' => $r['id'],
                        'uds_id' => $r['uds_id'],
                        'before_deal' => $r['before_deal'],
                        'gross' => $r['gross']
                    );
                }
            }
            $bcodesI = [];

            foreach ($bcodes as $bcode) {
                $bcodesI[strtolower($bcode)] = $bcode;
            }
            foreach ($bc_insert as $bc => $bc_arr) {
                $bc_arr['day_date'] = $day;
                $bc_arr['affe_id'] = $mid;
                $bc_arr['currency'] = $affe_cur;
                $bc_arr['bonus_code'] = $bcodesI[$bc];
                if ($sql->insertArray('affiliate_daily_bcodestats', $bc_arr) && $update_uds) {
                    $this->updateUDS($bc_uds_stats, $admin_pc);
                }
            }
            unset($bc_insert);
            unset($r);
        }
    }

    function insertSubStat($day, $aid = '') {

        $where = '';
        if (!empty($aid)) {
            $where = " WHERE affe_id = $aid ";
        }

        $uh = phive('UserHandler');
        $curs = phive("Currencer")->getAllCurrencies();

        $num_cols = array(
            'gross' => 'gross',
            'bets' => 'bets',
            'wins' => 'wins',
            'op_fees' => 'op_fees',
            'bank_fees' => 'bank_fees',
            'before_deal' => 'before_deal',
            'fails' => 'fails',
            'rewards' => 'rewards',
        );

        if (!empty($aid)) {
            phive('SQL')->delete('sub_affiliate_daily_stats', "day_date = '$day' AND affe_id = $aid");
        } else {
            phive('SQL')->delete('sub_affiliate_daily_stats', "day_date = '$day'");
        }

        foreach (phive('SQL')->loadArray("SELECT * FROM affiliate_info $where") as $info) {

            $mid = $info['affe_id'];
            $affe = cu($mid);
            $sdate = $edate = $day;

            if (!is_object($affe))
                continue;

            $affe_cur = $uh->companyAttr('currency', $mid);

            $bcodes = phive()->arrCol(phive('UserHandler')->getCompanyCampaignsProductWhere($mid, "= 'partnerroom'"), 'name');
            $subs = $uh->getSubAffiliates($bcodes);

            $sub_insert = array();

            foreach ($subs as $sub) {

                $sub_mid = $uh->getCompanyManager($sub['company_id'])['id'];
                $adb_stats = $uh->getADBByManagerID($sdate, $edate, $sub_mid);

                if (!empty($adb_stats)) {
                    foreach ($adb_stats as $r) {
                        foreach ($num_cols as $subkey => $adbkey) {
                            $sub_insert[$sub['bonus_code']][$subkey] += floatval($r[$adbkey]);
                        }
                    }
                }
            }

            foreach ($sub_insert as $bc => $insert) {

                $insert['affe_id'] = $mid;
                $insert['day_date'] = $day;
                $insert['currency'] = $affe_cur;
                $insert['bonus_code'] = $bc;
                phive('SQL')->insertArray('sub_affiliate_daily_stats', $insert);
            }
        }
    }

    function getStatByUsersAndDate($users, $sdate, $edate, $product = null) {

        $pr = new PRUserHandler();
        $users_str = $pr->helperArrayToInStr($users);

        if (!empty($product)) {
            $product = " AND product = '$product'";
        }

        $str = "SELECT id, uds_id, SUM( bets ) as bets, SUM( deposits ) as deposits, user_id, date, currency FROM users_daily_stats
                WHERE user_id IN ($users_str)
                AND `date` >= '$sdate' AND `date` <= '$edate'
                $product
                GROUP BY user_id";
        return phive('SQL')->loadArray($str);
    }

    function calcRealProfits($sdate, $edate, $aid = '') {

        $where = '';
        $aid = intval($aid);

        if (!empty($aid)) {
            $where = " WHERE affe_id = $aid ";
        }

        foreach (phive('SQL')->loadArray("SELECT * FROM affiliate_info $where") as $info) {

            $aid = $info['affe_id'];
            $sum_affiliate_profit_list = $this->sumAffiliateProfit($sdate, $edate, $aid, 'date');
            $sum_sub_affiliate_profit = $this->sumSubAffiliateProfit($sdate, $edate, $aid);
            $main_prof = intval($sum_affiliate_profit_list['prof']);
            $sub_prof = intval($sum_sub_affiliate_profit);

            if ($main_prof <= 0) {
                $str = "UPDATE affiliate_daily_bcodestats SET real_prof = 0 WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' AND affe_id = $aid";
                phive('SQL')->query($str);
                $str = "UPDATE users_daily_stats SET real_aff_fee = 0 WHERE `date` >= '$sdate' AND `date` <= '$edate' AND affe_id = $aid";
                phive('SQL')->query($str);
            }
            if ($sub_prof <= 0) {
                $str = "UPDATE sub_affiliate_daily_stats SET real_prof = 0 WHERE `day_date` >= '$sdate' AND `day_date` <= '$edate' AND affe_id = $aid";
                phive('SQL')->query($str);
            }
        }
    }

    function getTotalProf($total_rev = 0, $total_cpa = 0, $total_sub_rev = 0, $in_cents = false, $format = false, $decimals = 2){
        $amount = max($total_rev + $total_sub_rev, 0) + $total_cpa;
        if(!$in_cents)
            $amount /= 100;
        if(!$format)
            return $amount;
        return number_format($amount, $decimals);
    }

    function queueTransactions($sdate, $edate, $aid = '', $test = false, $only_sub_affiliates = false) {

        $uh = phive('UserHandler');

        $where = '';

        if (!empty($aid)) {
            $where = " WHERE affe_id in( $aid )";
        }

        foreach (phive('SQL')->loadArray("SELECT * FROM affiliate_info $where") as $info) {

            $aid = $info['affe_id'];
            $user = $uh->getUser($aid);
            $cid = $user->data['company_id'];
            //if (!empty($this->getAffBCodeStats($aid, $sdate, $edate))) {

                if (empty($only_sub_affiliates)) {
                    $sum_affiliate_profit_list = $this->sumAffiliateProfitByReward($sdate, $edate, $cid);

                    $rewardprofit = array();
                    foreach($sum_affiliate_profit_list as $ksumout => $vsumout){
                        if($vsumout['prof'] > 0){
                            $rewardprofit['prof'] += $vsumout['prof'];
                        }
                        $rewardprofit['cpa_prof'] += $vsumout['cpa_prof'];
                    }

                    // Currently a negative rev share profit should not impact a positive CPA profit.
                    //$main_prof = max((int)$sum_affiliate_profit_list['real_prof'], 0) + (int)$sum_affiliate_profit_list['cpa_prof'];
                    $main_prof = $this->getTotalProf($rewardprofit['prof'], $rewardprofit['cpa_prof'], 0, true);

                }

                $sum_sub_affiliate_profit = $this->sumSubAffiliateProfit($sdate, $edate, $aid);
                $sub_prof = intval($sum_sub_affiliate_profit);

                $map = array();

                if ($test) {
                    d('Affiliate Profit: ', $sum_affiliate_profit_list);
                    d('Sub Affiliate Profit: ', $sum_sub_affiliate_profit);
                    d('Main Profit: ', $main_prof);
                    d('Sub Profit: ', $sub_prof);
                }

                if ($main_prof < 0 || $sub_prof < 0) {
                    $tot_prof = $main_prof + $sub_prof;
                    if ($tot_prof > 0) {
                        $type = $main_prof < 0 ? 20 : 5;
                        $map[] = array($tot_prof, $type);
                    }
                } else {
                    if ($main_prof > 0) {
                        $map[] = array($main_prof, 5);
                    }
                    if ($sub_prof > 0) {
                        $map[] = array($sub_prof, 20);
                    }
                }
                foreach ($map as $m) {
                    $this->transactQueue($info['affe_id'], $m[0], $m[1], 1);
                }
            //}
        }
    }

    // $group_by needs to be either daily or monthly
    function sumAffiliateProfit($sdate, $edate, $aid, $group_by = ''){
        if(!empty($group_by)){
            // DATE_FORMAT(date,format)
            //$group_col     = $group_by == 'daily' ? 'day_date' : 'month';
            $date_format   = $group_by == 'daily' ? '%Y-%m-%d' : '%Y-%m';
            $group_sql     = " GROUP BY `date`";
        }

        $num_cols = array('deposits', 'gross', 'bets', 'wins', 'op_fees', 'bank_fees', 'before_deal', 'fails', 'rewards', 'prof', 'real_prof', 'paid_loyalty', 'cpa_prof', 'admin_fee');
        $sums = phive('SQL')->makeSums($num_cols);
        $str = "SELECT $sums, DATE_FORMAT(day_date, '$date_format') AS `date` FROM affiliate_daily_bcodestats
                WHERE day_date BETWEEN '$sdate' AND '$edate'
                    AND affe_id = $aid $group_sql";

        if(empty($group_by))
            return phive('SQL')->loadAssoc($str);

        return phive('SQL')->loadArray($str, 'ASSOC', 'date');
    }

    // $group_by needs to be either daily or monthly
    function sumAffiliateBreakdown($sdate, $edate, $aid, $group_by = '', $camp_campaign = '', $reward = ''){
        if(!empty($group_by)){
            $date_format   = $group_by == 'daily' ? '%Y-%m-%d' : '%Y-%m';
            $group_sql     = " GROUP BY adb.day_date";
        }

        if(!empty($camp_campaign)){
            $camp = "AND c.id = $camp_campaign ";
        }

        if(!empty($reward)){
            $rew = "AND rp.reward_plans_id = $reward ";
        }

        $num_cols = array('gross', 'bets', 'wins', 'tax', 'op_fees', 'bank_fees', 'jp_fee', 'before_deal', 'paid_loyalty', 'rewards', 'admin_fee', 'cpa_prof');
        $sums = phive('SQL')->makeSums($num_cols);
        $str = "SELECT $sums, DATE_FORMAT(adb.day_date, '$date_format') AS `date` FROM affiliate_daily_bcodestats adb
                JOIN campaigns c ON c.name = adb.bonus_code
                JOIN market_source ms ON c.ms_id = mrk_src_id
                JOIN reward_plans rp ON ms.reward_id = rp.reward_plans_id
                WHERE adb.day_date BETWEEN '$sdate' AND '$edate' $camp $rew
                AND adb.affe_id = $aid $group_sql";

        if(empty($group_by))
            return phive('SQL')->loadAssoc($str);

        return phive('SQL')->loadArray($str, 'ASSOC', 'date');
    }

    // $group_by needs to be either daily or monthly
    function sumAffiliateBreakdownJackpot($sdate, $edate, $aid, $group_by = '', $camp_campaign = '', $reward = ''){
        if(!empty($group_by)){
            $date_format   = $group_by == 'daily' ? '%Y-%m-%d' : '%Y-%m';
            $group_sql     = " GROUP BY uds.date";
        }

        if(!empty($camp_campaign)){
            $camp = "AND c.id = $camp_campaign ";
        }

        if(!empty($reward)){
            $rew = "AND rp.reward_plans_id = $reward ";
        }

        $num_cols = array('jp_contrib');
        $sums = phive('SQL')->makeSums($num_cols);
        $str = "SELECT $sums, DATE_FORMAT(uds.date, '$date_format') AS `date` FROM users_daily_stats uds
                JOIN campaigns c ON c.name = uds.bonus_code
                JOIN market_source ms ON c.ms_id = mrk_src_id
                JOIN reward_plans rp ON ms.reward_id = rp.reward_plans_id
                WHERE uds.date BETWEEN '$sdate' AND '$edate' $camp $rew
                AND uds.affe_id = $aid $group_sql";

        if(empty($group_by))
            return phive('SQL')->loadAssoc($str);

        return phive('SQL')->loadArray($str, 'ASSOC', 'date');
    }

    function sumAffiliateProfitBreakdown($sdate, $edate, $aid, $group_by = '', $camp_campaign, $reward){

        $sumtot = $this->sumAffiliateBreakdown($sdate, $edate, $aid, $group_by, $camp_campaign, $reward);

        $sumjack = $this->sumAffiliateBreakdownJackpot($sdate, $edate, $aid, $group_by, $camp_campaign, $reward);

        $uh = phive('UserHandler');
        $user = $uh->getUser($aid);
        $cid = $user->data['company_id'];

        if(!empty($group_by)){
            // DATE_FORMAT(date,format)
            //$group_col     = $group_by == 'daily' ? 'day_date' : 'month';
            $date_format   = $group_by == 'daily' ? '%Y-%m-%d' : '%Y-%m';
            $group_sql     = " GROUP BY `date`";
        }
        $str = "SELECT adb.*, rp.*
            FROM affiliate_daily_bcodestats adb
            JOIN campaigns c ON c.name = adb.bonus_code
            JOIN market_source ms ON c.ms_id = ms.mrk_src_id
            JOIN reward_plans rp ON ms.reward_id = rp.reward_plans_id
            WHERE adb.day_date BETWEEN '$sdate' AND '$edate'
            AND ms.company_id = $cid";

        $result = phive('SQL')->loadArray($str);
        $avg = array();

        $start = ( new DateTime($sdate) );
        $end = ( new DateTime($edate) );
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($start, $interval, $end);

        foreach($period as $dt){
            $holdrate = 0;
            $holdcount = 0;
            foreach($result as $value){
                $date_key  = $dt->format("Y-m-d");
                if($date_key == $value['day_date']){
                    switch($value['signature']){
                        case 'rev':
                            $strrev = "SELECT * FROM companies_affiliate_revshare_rates carr
                            JOIN affiliate_revshare_rates arr ON carr.affiliate_revshare_rate_id = arr.id
                            AND carr.company_id = $cid AND carr.plan = ".$value['reward_plans_id'];
                            $resrev = phive('SQL')->loadArray($strrev);
                            $holdrate  += $resrev[0]['rate'];
                            $holdcount++;
                            break;
                        case 'cpa':
                            $strcpa = "SELECT * FROM companies_affiliate_cpa_rates cacr
                            JOIN affiliate_cpa_rates acr ON cacr.affiliate_cpa_rates_id = acr.id
                            AND cacr.company_id = $cid"; // AND cacr.plan = ".$value['reward_plans_id'];
                            $rescpa = phive('SQL')->loadArray($strcpa);
                            break;
                        case 'hyb':
                            $strhyb = "SELECT * FROM companies_affiliate_hybrid_rates cahr
                            JOIN affiliate_hybrid_rates ahr ON cahr.affiliate_hybrid_rates_id = ahr.id
                            AND cahr.company_id = $cid AND cahr.plan = ".$value['reward_plans_id'];
                            $reshyb = phive('SQL')->loadArray($strhyb);
                            $holdrate = $reshyb[0]['rate'];
                            $holdcount++;
                            break;
                        case 'sub':
                             $strsub = "SELECT * FROM companies_sub_affiliate_revshare_rates csarr
                            JOIN affiliate_revshare_rates arr ON csarr.sub_affiliate_revshare_rate_id = arr.id
                            AND csarr.company_id = $cid AND csarr.plan = ".$value['reward_plans_id'];
                            $ressub = phive('SQL')->loadArray($strsub);
                            break;
                        case 'ndc':
                            $strndc = "SELECT * FROM companies_affiliate_ndc_rates canr
                            JOIN affiliate_ndc_rates anr ON canr.affiliate_ndc_rates_id = anr.id
                            AND canr.company_id = $cid AND canr.plan = ".$value['reward_plans_id'];
                            $resndc = phive('SQL')->loadArray($strndc);
                            $holdrate  += $resndc[0]['rate'];
                            break;
                    }
                }
            }
            $avgrate = $holdrate/$holdcount;
            $sumtot[$date_key]['rate'] = $avgrate;
            $sumtot[$date_key]['jp_contrib'] = $sumjack[$date_key]['jp_contrib'];
        }

        return $sumtot;
    }

    function sumAffiliateProfitByReward($sdate, $edate, $cid){

        $sums = phive('SQL')->makeSums(['prof', 'real_prof', 'cpa_prof']);
        $str = "SELECT $sums FROM affiliate_daily_bcodestats adb
            JOIN campaigns c ON c.name = adb.bonus_code
            JOIN market_source ms ON c.ms_id = mrk_src_id
            JOIN reward_plans rp ON ms.reward_id = rp.reward_plans_id
            WHERE adb.day_date BETWEEN '$sdate' AND '$edate'
            and ms.company_id = $cid GROUP BY ms.reward_id";

        return phive('SQL')->loadArray($str);
    }

    function sumSubAffiliateProfit($sdate, $edate, $aid) {

        $aff = cu($aid);
        if (empty($aff))
            return 0;

        $sql = "SELECT SUM(real_prof) FROM sub_affiliate_daily_stats WHERE affe_id = $aid AND day_date BETWEEN '$sdate' AND '$edate'";

        if ($aff->getSetting('sub_aff_no_neg_carry') == 0 || phive()->isEmpty($aff->getSetting('sub_aff_no_neg_carry'))) {
            return phive('SQL')->getValue($sql);
        } else {

            $sql .= " GROUP BY bonus_code";
            $res = 0;
            foreach (phive('SQL')->loadArray($sql) as $r) {
                if ((int) $r['real_prof'] > 0) {
                    $res += $r['real_prof'];
                }
            }

            return $res;
        }
    }

    function checkHistorical($aid = '', $test = false) {

        if (!empty($aid)) {
            $where = " WHERE affe_id in( $aid )";
        }

        foreach (phive('SQL')->loadArray("SELECT * FROM affiliate_info $where") as $info) {

            $aid = $info['affe_id'];
            $history = phive('SQL')->loadArray("SELECT * FROM cash_transactions WHERE user_id = $aid ORDER BY transaction_id DESC, timestamp DESC");

            $count = 0;
            $total_payout = 0;
            $total_withdrawal = 0;
            $affiliate = false;
            $update = 0;

            foreach($history as $khist => $vhist){
                $update = $vhist['transaction_id'];
                if($vhist['description'] == 'Withdrawal'){
                    if($affiliate ==  true){
                        if(($total_withdrawal*-1) == $total_payout){
                            echo 'Match on withdrawal';
                        }
                        else{
                            if(($total_payout > 10000)&&($update > 0)){
                                $sqlcash = "UPDATE cash_transactions SET amount = -$total_payout WHERE transaction_id = $update";
                                phive('SQL')->query($sqlcash);
                                $aid = intval($aid);
                                $sqlpending = "UPDATE pending_withdrawals SET amount = $total_payout WHERE user_id = $aid";
                                phive('SQL')->query($sqlpending);
                            }
                        }
                        $affiliate = false;
                        $total_payout = 0;
                        $total_withdrawal = 0;
                    }
                    $total_withdrawal += $vhist['amount'];
                }
                if(($vhist['description'] == 'Affiliate payout')or($vhist['description'] == 'Sub affiliate payout')){
                    $affiliate = true;
                    //echo 'Payout is:'.$vhist['amount'].'<br/>';
                    $total_payout += $vhist['amount'];
                }
                $count++;
            }
            if(($total_withdrawal*-1) != $total_payout){
                if(($total_payout > 10000)&&($update > 0)){
                    //echo 'Affiliate'.$aid.'<br/>';
                    $sqlcash = "UPDATE cash_transactions SET amount = -$total_payout WHERE transaction_id = $update";
                    phive('SQL')->query($sqlcash);
                    $aid = intval($aid);
                    $sqlpending = "UPDATE pending_withdrawals SET amount = $total_payout WHERE user_id = $aid";
                    phive('SQL')->query($sqlpending);
                }
            }
        }
    }

    function payTransactions($aid = '', $test = false) {

        $uh = phive('UserHandler');
        $cc = phive('CasinoCashier');

        $where = '';

        if (!empty($aid))
            $where = " WHERE affe_id = $aid ";

        foreach (phive('SQL')->loadArray("SELECT * FROM affiliate_info $where") as $info) {

            $aid = $info['affe_id'];

            $queued = $this->getAffiliateQueuedTransactions($aid);
            $company = $uh->getUserCompany($aid);
            $min_pay = $company['min_payout'];

            foreach ($queued as $type => $transactiontype) {
                foreach ($transactiontype as $transaction) {
                    $amount = $transaction['amount'];
                    $type = $transaction['transactiontype'];

                    if (!$test) {

                        phive('SQL')->delete('queued_transactions', "user_id = {$transaction['user_id']} AND transactiontype = {$transaction['transactiontype']}");
                        if ($amount > 0) {
                            $linecomp = $this->transactUser($aid, $amount, $type, 1);
                        }
                    }
                }
            }

            if ($test) {

                d('Transactions to be cashed: ', $queued);
                d('Min Payout for user: ', $min_pay);
                d('Total Transaction amount: ', $amount);
                d('Prev cash balance: ', $company['cash_balance']);
            }

            $company = $uh->getUserCompany($aid);
            $cash_balance = $company['cash_balance'];

            if ($test)
                d('New Balance: ', $cash_balance);

            if ($cash_balance >= $min_pay) {

                if ($test) {

                    $company = $uh->getUserCompany($aid);
                    $cash_balance = $company['cash_balance'];
                    d('Cash Balance over');
                    d('New cash balance: ', $cash_balance);
                }

                if ($company['pm_id'] == 1) {

                    $user = cu($aid);
                    $bank = $uh->getUserBankWire($aid);

                    if ($this->checkBankDetails($bank)) {

                        $details = array(
                            'bank_receiver' => $bank['payee'],
                            'bank_city' => $bank['city'],
                            'bank_country' => $bank['country'],
                            'iban' => $bank['iban'],
                            'bank_account_number' => $bank['account_num'],
                            'bank_id' => $bank['bank_id'],
                            'swift_bic' => $bank['swift'],
                            'currency' => $company['currency']
                        );

                        $new_id = $cc->insertPendingBank($user, $cash_balance, $details, 'bank', false);

                        $this->transactUser($aid, ( 0 - $cash_balance), 8, 0, $new_id);
                    }
                    else {

                        $user = cu($aid);
                        $replacers = phive('UserHandler')->getDefaultReplacers($user);
                        $replacers["__METHOD__"] = ucfirst('Bank Wire');
                        $replacers["__AMOUNT__"] = nfCents($cash_balance, true);
                        //phive( 'UserHandler' )->sendMailPR( "bank.wire.invalid.data", $user, $replacers );
                    }
                } else if ($company['pm_id'] == 2) {

                    $username = phive('UserHandler')->getUserBankVS($aid)['username'];

                    if ($username == phive('PRDistAffiliater')->checkUsername('videoslots', $username)['videoslots']['username']) {

                        $unique_id = $this->transactUser($aid, ( 0 - $cash_balance), 6);
                        phive('PRDistAffiliater')->transferCashBalanceToProduct('videoslots', $username, $cash_balance, $company['currency'], $unique_id);
                    } else {
                        $user = cu($aid);
                        $replacers = phive('UserHandler')->getDefaultReplacers($user);
                        phive('UserHandler')->sendMailPR("videoslots.payout.username.invalid", $user, $replacers);
                    }
                }
            }
        }
    }

    function checkBankDetails($bank) {

        if (empty($bank['payee']) || empty($bank['city']) || empty($bank['country']) || empty($bank['swift']))
            return false;

        $iban_countries = phive('Config')->getValue('countries', 'use_iban');

        if (in_array($bank['country'], explode(' ', $iban_countries)) && empty($bank['iban']))
            return false;

        $bank_id_countries = phive('Config')->getValue('countries', 'use_bank_id');

        if (in_array($bank['country'], explode(' ', $bank_id_countries)) && ( empty($bank['bank_id']) || empty($bank['account_num']) ))
            return false;

        if (!in_array($bank['country'], explode(' ', $iban_countries)) && !in_array($bank['country'], explode(' ', $bank_id_countries)) && empty($bank['account_num']))
            return false;

        return true;
    }

    function getQueuedTransactions() {

        return phive('SQL')->loadArray("SELECT * FROM queued_transactions ORDER BY timestamp");
    }

    function getQueuedTransactionsByID($id) {

        return phive('SQL')->loadAssoc("SELECT * FROM queued_transactions WHERE transaction_id = $id");
    }

    function getAffiliateQueuedTransactions($aid) {

        return phive('SQL')->load2DArr("SELECT SUM( amount ) as amount, user_id, transactiontype FROM queued_transactions
                                      WHERE user_id = $aid
                                      GROUP BY transactiontype");
    }

    function getAffiliateCashedTransactions($aid) {

        return phive('SQL')->loadArray("SELECT * FROM cash_transactions
                                      WHERE user_id = $aid
                                      AND payed = 0
                                      ORDER BY timestamp");
    }

    function getQueuedTransactionsFiltered($uid = null, $sdate = null, $edate = null) {

        $where_user = '';
        $where_date = '';

        if (!empty($uid))
            $where_user = " AND user_id = $uid";

        if (!empty($sdate) && !empty($edate))
            $where_date = " AND timestamp BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59'";

        return phive('SQL')->loadArray("SELECT * FROM queued_transactions
                                        WHERE amount > 0
                                        $where_user
                                        $where_date
                                        ORDER BY timestamp");
    }

    function getAffiliateManagers() {
        return phive('SQL')->loadArray("SELECT * FROM affiliate_managers");
    }

    function getAffiliateOverview($sdate, $edate, $page, $limit, $sort = 'fullname', $sort_direction = 1, $filters) {

        $where_user = '';
        $where_manager = '';
        $where_country = '';
        $where_register = '';
        $where_active = '';

        if ($sort_direction == 1)
            $sql_sort = "$sort DESC";
        else
            $sql_sort = "$sort ASC";

        if (!empty($filters['affiliate']))
            $where_user = " AND u.username = '{$filters['affiliate']}'";

        if (!empty($filters['manager']))
            $where_manager = " AND c.aff_manager = {$filters['manager']}";

        if (!empty($filters['country']))
            $where_country = " AND c.country = '{$filters['country']}'";

        if (!empty($filters['register_start']) && !empty($filters['register_end']))
            $where_register = " AND c.register_date BETWEEN '{$filters['register_start']}' AND '{$filters['register_end']}'";
        else if (!empty($filters['register_start']))
            $where_register = " AND c.register_date > '{$filters['register_start']}'";
        else if (!empty($filters['register_end']))
            $where_register = " AND c.register_date < '{$filters['register_end']}'";

        if (!empty($filters['active_start']) && !empty($filters['active_end']))
            $where_active = " AND u.last_login BETWEEN '{$filters['active_start']}' AND '{$filters['active_end']}'";
        else if (!empty($filters['active_start']))
            $where_active = " AND u.last_login > '{$filters['active_start']}'";
        else if (!empty($filters['active_end']))
            $where_active = " AND u.last_login < '{$filters['active_end']}'";

        $sum_cols = ['o.before_deal', 'o.total_profit', 'o.rev_profit', 'o.hyb_profit', 'o.cpa_profit', 'o.sub_profit', 'o.signups', 'o.rev_signups', 'o.hyb_signups', 'o.cpa_signups', 'o.sub_signups', 'o.ndc', 'o.ndc_ndc', 'o.rev_ndc', 'o.hyb_ndc', 'o.cpa_ndc', 'o.sub_ndc', 'o.total_deposits', 'o.rev_deposits', 'o.hyb_deposits', 'o.cpa_deposits', 'o.sub_deposits', 'o.total_deposit_amount', 'o.rev_deposit_amount', 'o.hyb_deposit_amount', 'o.cpa_deposit_amount', 'o.sub_deposit_amount'];

        $sums = phive('SQL')->makeSums($sum_cols);

        $sql_str = "SELECT SQL_CALC_FOUND_ROWS $sums, o.user_id, u.username, CONCAT( u.firstname, ' ', u.lastname ) as fullname, u.last_login, c.mobile, c.currency, c.country, c.register_date, m.name, e.email
                    FROM overview o
                        INNER JOIN users u
                        ON o.user_id = u.id
                        INNER JOIN companies c
                        ON u.company_id = c.company_id
                        INNER JOIN emails e
                        ON c.company_id = e.company_id
                    LEFT JOIN affiliate_managers m
                        ON c.aff_manager = m.id
                    WHERE o.date BETWEEN '$sdate' AND '$edate'
                        AND e.admin = 1
                        $where_user
                        $where_manager
                        $where_country
                        $where_register
                        $where_active
                    GROUP BY o.user_id
                        ORDER BY $sql_sort";

        return phive('SQL')->loadArray($sql_str);
    }

    function getCacheForAff($affe_id, $sdate, $edate, $by_month = false, $tbl = 'affiliate_daily_stats', $extra = array(), $cur = '', $only_real = false, $bonus_code = '') {

        if (!empty($bonus_code) || $by_month === 'bonus_code')
            $tbl = 'affiliate_daily_bcodestats';

        $where = empty($affe_id) ? " AND $tbl.affe_id != 0 " : " AND $tbl.affe_id = $affe_id ";

        if (!empty($only_real))
            $where = " AND $tbl.affe_id IN (SELECT affe_id FROM affiliate_rates WHERE rate > 0) ";

        if (!empty($bonus_code))
            $where .= " AND bonus_code = '$bonus_code' ";

        if ($tbl == 'affiliate_daily_stats' || $tbl == 'affiliate_daily_bcodestats') {
            $num_cols = array('gross', 'admin_fee', 'bets', 'wins', 'op_fees', 'bank_fees', 'before_deal', 'fails', 'rewards', 'real_prof',
                        'real_prof', 'paid_loyalty', 'deposits', 'tax', 'paid_loyalty', 'jp_fee'
            );
            $date_col = 'day_date';
        }

        if ($tbl == 'sub_casino_affiliate_earnings') {
            $num_cols = array('before_deal', 'real_prof');
            $date_col = 'day_date';
        }

        if ($tbl == 'users_daily_stats') {
            $num_cols = $this->getUDSNumCols();
            $date_col = 'date';
        }

        $sums = phive('SQL')->makeSums(array_merge($num_cols, $extra));
        $where_cur = empty($cur) ? '' : "AND $tbl.currency = '$cur'";

        if ($by_month === true || $by_month == 'month_num') {
            $group1 = "`$date_col` AS day_date, DATE_FORMAT($date_col, '%Y-%m') AS month_num, $sums";
            $group2 = ' GROUP BY month_num ';
            $group_by = 'month_num';
        } else if ($by_month == 'day_date') {
            $group1 = "`$date_col` AS day_date, $sums";
            $group2 = ' GROUP BY day_date ';
            $group_by = 'day_date';
        } else if ($by_month == 'bonus_code') {
            $group1 = "bonus_code, $sums";
            $group2 = ' GROUP BY bonus_code ';
            $group_by = 'bonus_code';
        } else if ($by_month == 'day') {
            $group_by = 'day_num';
            $group1 = "*, DAYOFMONTH($date_col) AS day_num, $sums";
            $group2 = " GROUP BY day_num ";
        } else if ($by_month == 'affiliate') {
            $group_by = 'affe_id';
            $group1 = "$tbl.affe_id, u.firstname, u.username, u.username as affiliate, $sums";
            $group2 = ' GROUP BY affe_id ';
            $join = "LEFT JOIN users AS u ON u.id = $tbl.affe_id";
        } else {
            $group1 = '*';
            $group_by = false;
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

        return phive('SQL')->loadArray($str, 'ASSOC', $group_by);
    }

    public function getTransactionTypes() {

        return [
            5 => ['desc' => 'Affiliate payout', 'debit' => 0],
            8 => ['desc' => 'Withdrawal', 'debit' => 1, 'payment' => 'Bank Wire'],
            13 => ['desc' => 'Normal Refund', 'debit' => 1, 'payment' => 'Bank Wire'],
            20 => ['desc' => 'Sub affiliate payout', 'debit' => 0],
                //6  => [ 'desc' => 'Affiliate cash balance debited through Videoslots.com', 'debit' => 1, 'payment' => 'Videoslots.com Account' ],
        ];
    }

    public function transactUser($user_id, $amount, $type, $checked = 0, $entry = 0) {

        $company = phive('UserHandler')->getUserCompany($user_id);
        $types = $this->getTransactionTypes();

        $insert = [
            'user_id' => $user_id,
            'amount' => $amount,
            'description' => $types[$type]['desc'],
            'transactiontype' => $type,
            'currency' => $company['currency'],
            'entry_id' => $entry,
            'checked' => $checked
        ];

        phive('SQL')->incrValue('companies', 'cash_balance', "company_id = {$company['company_id']}", $amount);
        $new_id = phive('SQL')->insertArray('cash_transactions', $insert);

        return $new_id;
    }

    public function transactQueue($uid, $amount, $type, $approved = 0) {

        $types = $this->getTransactionTypes();

        $insert = [
            'user_id' => $uid,
            'amount' => $amount,
            'transactiontype' => $type,
            'description' => $types[$type]['desc'],
            'approved' => $approved
        ];

        $new_id = phive("SQL")->insertArray('queued_transactions', $insert);

        return $new_id;
    }

    public function transactFailed($cid, $tid, $amount, $type) {

        $company = phive('UserHandler')->getCompanyByID($cid);
        $types = $this->getTransactionTypes();

        $insert = [
            'company_id' => $cid,
            'transaction_id' => $tid,
            'transactiontype' => $type,
            'desc' => $types[$type]['desc'],
            'amount' => $amount,
            'currency' => $company['currency']
        ];

        phive('SQL')->insertArray('failed_transactions', $insert);
    }

    public function checkTransactions($aid = '', $test = false) {

        $where = '';

        if (!empty($aid))
            $where = " WHERE affe_id = $aid ";

        foreach (phive('SQL')->loadArray("SELECT * FROM affiliate_info $where") as $info) {

            $company = phive('UserHandler')->getUserCompany($info['affe_id']);
            $product = phive('UserHandler')->getPaymentMethodByID($company['pm_id'])['signature'];

            if (!empty($product)) {

                $transactions = phive('SQL')->loadArray("SELECT * FROM cash_transactions WHERE user_id = {$info['affe_id']} AND checked = '0' AND transactiontype IN ( 6 )");

                if ($test)
                    d('List of transactions: ', $transactions);

                if (!empty($transactions)) {

                    foreach ($transactions as $transaction) {

                        $result = phive('PRDistAffiliater')->checkTransaction($product, $transaction['transaction_id']);

                        if ($test) {

                            d('Check transaction: ', $transaction);
                            d('Result: ', $result);
                        } else {

                            if ($result[$product]) {

                                phive('SQL')->updateArray('cash_transactions', ['checked' => 1], "transaction_id = {$transaction['transaction_id']}");
                                $username = phive('UserHandler')->getUserBankVS($transaction['user_id'])['username'];

                                $withdrawal = [
                                    'user_id' => $info['affe_id'],
                                    'payment_method' => $product,
                                    'amount' => ( $transaction['amount'] * -1 ),
                                    'currency' => $company['currency'],
                                    'status' => 'approved',
                                    'product_username' => $username
                                ];

                                phive('SQL')->save('pending_withdrawals', $withdrawal);

                                $user = cu($info['affe_id']);
                                $replacers = phive('UserHandler')->getDefaultReplacers($user);
                                $replacers["__METHOD__"] = phive()->getSiteTitle() .' Affiliate Program';
                                $replacers["__AMOUNT__"] = nfCents($transaction['amount'], true);
                                phive('UserHandler')->sendMailPR("withdrawal.ok", $user, $replacers);
                            } else {

                                switch ($transaction['transactiontype']) {

                                    case 5:

                                        $username = phive('UserHandler')->getUserBankVS($transaction['user_id'])['username'];
                                        phive('PRDistAffiliater')->transferCashBalanceToProduct($product, $username, ( $transaction['amount'] * -1), $company['currency'], $transaction['transaction_id']);
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function disapprovePending($pid ,$user = '') {

        $pending = phive('UserHandler')->getPendingWithdrawalByID($pid);

        if ($pending['status'] == 'pending') {

            $approved_by = $_SESSION['user'];
            if(!empty($user)){
                $approved_by = $user;
            }

            $to_update = [
                "status" => "disapproved",
                "approved_by" => $approved_by,
                "approved_at" => date('Y-m-d H:i:s')
            ];

            phive("SQL")->updateArray('pending_withdrawals', $to_update, "id = " . $pid);

            return $this->transactUser($pending['user_id'], $pending['amount'], 13, 2);
        }
    }

}

function cuAff(){
    return $_SESSION['user'];
}

function nfPr($num, $currency = '', $divide = true){
    $divide_by = empty($divide) ? 1 : 100;
    $num      /= $divide_by;
    $decimals  = $num > 999 ? 0 : 2;
    $str       = number_format($num, $decimals);
    if(empty($currency))
        return $str;
    return "$currency $str";
}
