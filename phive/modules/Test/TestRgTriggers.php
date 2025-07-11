<?php

use Carbon\Carbon;

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36';

class TestRgTriggers extends TestPhive
{
    function testRG36($country = "SE"): void
    {
        $trigger = "RG36";
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $rg_limit_type = 'deposit';
        $thold = phive('Config')->getValue('RG', "$trigger-threshold", 10000);
        $this->printOutputData($trigger, true);
        $this->printOutputData("Country: {$country}");
        $this->printOutputData("Deposit thold: {$thold}");
        $default_limits = [
            ["limit" => 100, "type" => $rg_limit_type, "time_span" => "day"],
            ["limit" => 700, "type" => $rg_limit_type, "time_span" => "week"],
            ["limit" => 1000, "type" => $rg_limit_type, "time_span" => "month"]
        ];
        foreach ($default_limits as $limit) {
            rgLimits()->addLimit($user, $rg_limit_type, $limit['time_span'], $limit['limit']);
            $this->printOutputData("Set up default deposit limit {$limit['limit']} SEK/{$limit['time_span']}");
        }
        $this->printOutputData("Case1 (negative): User changes the limit but not reach the threshold. Should not trigger");
        $limits_under_thold = array_map(function ($limit) {
            $limit['limit'] += 100;
            return $limit;
        }, $default_limits);
        $resettable_limits = rgLimits()->getByTypeUser($user, $rg_limit_type);
        phive('DBUserHandler/RgLimitsActions')->changeResettable(
            $user,
            $limits_under_thold,
            $resettable_limits,
            true
        );
        sleep(5);
        $this->msg(!$this->doesUserHaveTrigger($user_id, $trigger));
        $this->printOutputData("Case2 (positive): The user changes the limit and exceeds the threshold value. Should trigger");
        $limits_over_thold = array_map(function ($limit) {
            $limit['limit'] *= 10;
            return $limit;
        }, $default_limits);
        $resettable_limits = rgLimits()->getByTypeUser($user, $rg_limit_type);
        phive('DBUserHandler/RgLimitsActions')->changeResettable(
            $user,
            $limits_over_thold,
            $resettable_limits,
            true
        );
        sleep(10);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->printLatest($user, 'triggers_log', 10);
        $this->cleanupTestPlayer($user_id);
    }

    public function testRG5($country = "GB"): void
    {
        $trigger = "RG5";
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $this->printOutputData($trigger, true);

        $this->printOutputData("Case1 (external-excluded): User is external-excluded. Should trigger");
        phive('UserHandler')->externalSelfExclude($user);
        sleep(10);
        phive('UserHandler')->removeExternalSelfExclusion($user);
        sleep(5);
        $dep_insert = [
            'user_id' => $user_id,
            'amount' => 10000,
            'dep_type' => 'trustly',
            'timestamp' => Carbon::now()->toDateTimeString(),
            'ext_id' => uniqid('', true),
            'scheme' => "",
            'card_hash' => "",
            'loc_id' => "",
            'status' => "approved",
            'currency' => $user->getAttr('currency'),
            'display_name' => ucfirst(ucfirst('trustly')),
            'ip_num' => $user->getAttr('cur_ip'),
            'mts_id' => 0,
        ];
        phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);
        phive('Cashier/Rg')->returningSelfExcluders($user);
        sleep(15);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        phive('SQL')->sh($user_id)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);

        $this->printOutputData("Case2 (self-excluded): User self-excluded. Should trigger");
        $user->deleteSettings(['unexclude-date', 'external-unexcluded-date', 'external-excluded']);
        $user->setSetting('excluded-date', Carbon::now()->subDay()->toDateString());
        $user->setSetting('unexcluded-date', Carbon::now()->subHour()->toDateString());
        sleep(10);
        $dep_insert = [
            'user_id' => $user_id,
            'amount' => 10000,
            'dep_type' => 'trustly',
            'timestamp' => Carbon::now()->toDateTimeString(),
            'ext_id' => uniqid('', true),
            'scheme' => "",
            'card_hash' => "",
            'loc_id' => "",
            'status' => "approved",
            'currency' => $user->getAttr('currency'),
            'display_name' => ucfirst(ucfirst('trustly')),
            'ip_num' => $user->getAttr('cur_ip'),
            'mts_id' => 0,
        ];
        phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);
        phive('Cashier/Rg')->returningSelfExcluders($user);
        sleep(15);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->printLatest($user, 'triggers_log', 10);
        $this->cleanupTestPlayer($user_id);
    }

    function testRG66($country = "GB")
    {
        $trigger = "RG66";
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $net_account = 'test@test.com';
        $deposit_ext_id = 'test';
        $net_deposit = null;
        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-net-deposit'");
        $config_values = phive('Config')->getValueFromTemplate($config[0]);
        foreach ($config_values as $percentage => $countries) {
            if (in_array($user->getCountry(), $countries, true)) {
                $net_deposit = (int) $percentage;
                break;
            }
        }

        $this->printOutputData($trigger, true);

        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('pending_withdrawals', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id, 'trigger_name' => $trigger]);

        $this->printOutputData("Case0: Net-deposit < {$net_deposit}. Should not trigger");
        phive('Casino')->depositCash($user_id, 100000, 'wirecard', $deposit_ext_id, 'visa', '3243 56** **** 1234', 122);
        sleep(10);
        $this->msg(!$this->doesUserHaveTrigger($user_id, $trigger));

        $this->printOutputData("Case1: Net-deposit = {$net_deposit}. Should trigger");
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('Casino')->depositCash($user_id, 150000, 'wirecard', $deposit_ext_id, 'visa', '3243 56** **** 1234', 122);
        sleep(10);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));

        $this->printOutputData("Case2: Should trigger only once per month");
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id, 'trigger_name' => $trigger]);
        phive('Casino')->depositCash($user_id, 400000, 'wirecard', $deposit_ext_id, 'visa', '3243 56** **** 1234', 122);
        sleep(10);
        phive('Cashier')->insertPendingCommon($user, 200000,
            ['net_account' => $net_account, 'payment_method' => 'neteller']);
        sleep(10);
        $trigger_count = $this->getTriggerCount($user_id, $trigger);
        if ($trigger_count == 1) {
            $this->msg(true);
        } else {
            $this->msg(false);
        }

        $this->printLatest($user, 'triggers_log', 10);
        $this->cleanupTestPlayer($user_id);
    }

    /**
     * @throws Exception
     */
    public function testRG67()
    {
        $trigger = "RG67";

        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-top-loser'");
        $config_values = phive('Config')->getValueFromTemplate($config[0]);

        Phive('SQL')->query("DELETE FROM users_daily_stats WHERE date >= '".Carbon::today()->subWeek(2)->toDateString()."' ");

        $users_array = [];
        $users_before_period = [];
        $users_after_period = [];

        $this->printOutputData($trigger, true);

        foreach ($config_values as $loser_count => $countries) {
            $loser_count = (int) $loser_count;

            foreach ($countries as $user_country) {
                foreach (range(1, ($loser_count + 2)) as $count) {
                    $user = $this->getTestPlayer($user_country);
                    $user_id = $user->getId();
                    $user_currency = $user->getCurrency();

                    phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
                    phive('SQL')->delete('users_daily_stats', ['user_id' => $user_id]);
                    phive('SQL')->sh($user_id)->delete('rg_limits', ['user_id' => $user_id]);
                    phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id]);

                    $deposits = 25000;
                    $withdrawals = random_int(10000, 50000);
                    $loss = $deposits - $withdrawals;
                    $loss_date = Carbon::today()->subWeek()->startOfWeek()->addDays(rand(1, 6))->toDateTimeString();

                    phive('SQL')->insertArray(
                        'users_daily_stats',
                        [
                            'date' => $loss_date,
                            'user_id' => $user_id,
                            'currency' => $user_currency,
                            'deposits' => $deposits,
                            'withdrawals' => $withdrawals,
                            'rewards' => 0,
                            'jp_contrib' => 0,
                            'country' => $user_country,
                            'affe_id' => rand(100000, 900000000),
                        ]
                    );

                    $users_array[$loser_count][$user_country][] = [
                        'user_id' => $user_id,
                        'loss' => $loss,
                        'date' => $loss_date,
                        'country' => $user_country,
                    ];
                    sleep(5);
                }

                // create a user before the period
                $before_user = $this->getTestPlayer($user_country);
                $before_user_id = $before_user->getId();

                $before_date = Carbon::today()->subWeek(2)->endOfWeek()->toDateString();
                phive('SQL')->insertArray(
                    'users_daily_stats',
                    [
                        'date' => $before_date,
                        'user_id' => $before_user_id,
                        'currency' => $before_user->getCurrency(),
                        'deposits' => 1000,
                        'withdrawals' => 10000,
                        'rewards' => 0,
                        'jp_contrib' => 0,
                        'country' => $user_country,
                        'affe_id' => rand(100000, 900000000),
                    ]
                );

                $users_before_period[$loser_count][$user_country] = $before_user_id;
                sleep(5);

                // create a user after the period
                $after_user = $this->getTestPlayer($user_country);
                $after_user_id = $after_user->getId();

                $after_date = Carbon::today()->toDateString();
                phive('SQL')->insertArray(
                    'users_daily_stats',
                    [
                        'date' => $after_date,
                        'user_id' => $after_user_id,
                        'currency' => $after_user->getCurrency(),
                        'deposits' => 1000,
                        'withdrawals' => 10000,
                        'rewards' => 0,
                        'jp_contrib' => 0,
                        'country' => $user_country,
                        'affe_id' => rand(100000, 900000000),
                    ]
                );

                $users_after_period[$loser_count][$user_country] = $after_user_id;
                sleep(5);
            }
        }

        phive('Cashier/Rg')->customerIsTopLoser();
        sleep(5);

        $this->printOutputData("Users created within the period");
        print_r($users_array);

        foreach ($config_values as $loser_count => $countries) {
            foreach ($countries as $country) {
                $this->printOutputData("Case 1: Triggers for top {$loser_count} losers in {$country} only");

                $top_losers = $users_array[$loser_count][$country];
                // sort array by loss
                usort($top_losers, function ($a, $b) {
                    return $a['loss'] > $b['loss'] ? -1 : 1;
                });
                $top_losers = array_filter($top_losers, function ($value) {
                    return $value['loss'] > 0;
                });

                $top_losers = array_chunk($top_losers, $loser_count)[0];

                $this->printOutputData("Top losers are: ");
                print_r($top_losers);

                $trigger_count = 0;

                foreach ($top_losers as $loser) {
                    if ($this->doesUserHaveTrigger($loser['user_id'], $trigger)) {
                        $trigger_count++;
                    }
                }

                $this->msg(($trigger_count === count($top_losers)));

                $this->printOutputData("Case 2: Top loser before previous week should not be considered");
                $this->msg(!$this->doesUserHaveTrigger($users_before_period[$loser_count][$country], $trigger));
                sleep(5);

                $this->printOutputData("Case 3: Top loser after previous week should not be considered");
                $this->msg(!$this->doesUserHaveTrigger($users_after_period[$loser_count][$country], $trigger));

                $this->printOutputData("Case 4: GRS is set to medium risk");
                $random_top_loser = $top_losers[rand(0, count($top_losers) - 1)];
                $random_loser_id = $random_top_loser['user_id'];
                $this->printOutputData("Selected top loser: {$random_loser_id}");

                $old_risk_tag = Phive('SQL')->sh($random_loser_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$random_loser_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
                $this->printOutputData("Current risk of the user: {$old_risk_tag}");
                $this->printLatest(cu($random_loser_id), 'risk_profile_rating_log', 5);
                $this->printLatest(cu($random_loser_id), 'triggers_log', 10);

                phive('Cashier/Arf')->invoke('onLogin', $random_loser_id);
                sleep(30);
                $risk_tag = Phive('SQL')->sh($random_loser_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$random_loser_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
                $this->printOutputData("latest risk: {$risk_tag}");
                $this->msg($risk_tag === 'Medium Risk');

                $this->printOutputData("Case 5: For 2 triggers in 30 days, GRS is set to high risk");
                phive('SQL')->sh($random_loser_id)->insertArray('triggers_log', [
                    'user_id' => $random_loser_id,
                    'trigger_name' => $trigger,
                    'created_at' => phive()->hisMod('-15 days'),
                    'descr' => '',
                    'data' => '',
                    'cnt' => 0,
                    'txt' => ''
                ]);
                // invoke grs calculation
                phive('Cashier/Arf')->invoke('onLogin', $random_loser_id);
                sleep(30);
                // check if its set to high risk
                $risk_tag = Phive('SQL')->sh($random_loser_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$random_loser_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
                $this->msg($risk_tag === 'High Risk');
            }
        }

        foreach (array_column($users_array, 'user_id') as $user_id) {
            $this->cleanupTestPlayer($user_id);
        }

        foreach (phive()->flatten($users_before_period) as $user_id) {
            $this->cleanupTestPlayer($user_id);
        }

        foreach (phive()->flatten($users_after_period) as $user_id) {
            $this->cleanupTestPlayer($user_id);
        }
    }

    public function testRG68($country = "GB")
    {
        $trigger = "RG68";
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $ndl_percentage = null;
        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-ndl-percentage'");
        $config_values = phive('Config')->getValueFromTemplate($config[0]);
        foreach ($config_values as $percentage => $countries) {
            if (in_array($user->getCountry(), $countries, true)) {
                $ndl_percentage = (int) $percentage;
                break;
            }
        }
        $this->printOutputData($trigger, true);
        $this->printOutputData("Does RG68 config exist for the country {$country}?");
        $this->msg(!empty($ndl_percentage), null, null, true);
        $rgl = rgLimits()->getLimit($user, rgLimits()::TYPE_NET_DEPOSIT, 'month');

        $this->printOutputData("Step 0: negative test case");
        $rgl['progress'] = 0;
        phive("SQL")->sh($user_id)->save('rg_limits', $rgl);
        phive('Casino')->depositCash($user_id, 10000, 'trustly', uniqid('', true));
        sleep(30);
        $this->msg(!$this->doesUserHaveTrigger($user_id, $trigger));

        $this->printOutputData("Step 1: modify 'net_deposit' progress to met the triggering condition ($ndl_percentage% from NDL)");
        $current_limit = (int)$rgl["cur_lim"];
        $rgl['progress'] = ($current_limit * $ndl_percentage) / 100;
        phive("SQL")->sh($user_id)->save('rg_limits', $rgl);

        $this->printOutputData("Step 2: deposit cash over NDL thold {$rgl['progress']}. Should trigger");
        phive('Casino')->depositCash($user_id, 10000, 'trustly', uniqid('', true));
        sleep(30);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->printLatest($user, 'triggers_log', 10);
        $this->cleanupTestPlayer($user_id);
    }

    public function testRG19()
    {
        $trigger = "RG19";
        $this->printOutputData(__METHOD__);
        $annual_income = '£20,000-40,000';
        $total_withdrawals = 100000;
        $break_threshold = 100;
        $countries = phive('Config')->valAsArray('RG', 'RG19-countries');
        $dmapi = phive('Dmapi');
        $sql = phive('SQL');
        $rg = phive('Cashier/Rg');
        foreach ($countries as $country) {
            $user = $this->getTestPlayer($country);
            $user_id = $user->getId();
            $this->printOutputData("Created test user ID {$user_id} from {$country}.");
            $this->printOutputData("Creating `sourceoffunds` document with status 'approved'");
            $dmapi->createEmptyDocument($user->getId(), 'sourceoffunds');
            $document = $dmapi->getDocumentByTag('sourceoffundspic', $user_id);
            $this->msg(
                !empty($document),
                "The document `sourceoffundspic` wasn\'t created",
                'The document `sourceoffundspic` created',
                true
            );
            sleep(3);
            $dmapi->updateDocumentStatus($user_id, $document['id'], 'approved', $user_id);
            sleep(2);
            $data = [
                'document_id'            => $document['id'],
                'actor_id'               => 1,
                'user_id'                => $user_id,
                'name_of_account_holder' => $user->getFullName(),
                'name'                   => $user->getFirstName(),
                'address'                => $user->getAddress(),
                'funding_methods'        => 'funding_methods',
                'other_funding_methods'  => 'other_funding_methods',
                'occupation'             => 'occupation',
                'annual_income'          => $annual_income,
                'currency'               => 'GBP',
                'no_income_explanation'  => 'no_income_explanation',
                'your_savings'           => 0,
                'savings_explanation'    => 'savings_explanation',
                'date_of_submission'     => phive()->hisNow(),
                'form_version'           => 'form_version',
            ];
            $dmapi_result = $dmapi->createSourceOfFundsData($user_id, $data);

            $this->msg(
                !empty($dmapi_result['data']['id']),
                'The `SourceOfFundsData` wasn\'t created',
                'The `SourceOfFundsData` was created',
                true);

            $parameters = phive('Cashier/Rg')->getRG19ThresholdParameters($user->getCountry(), $annual_income);
            $threshold = (int)$parameters['threshold'];
            $this->printOutputData("Net Deposit threshold $threshold " . $user->getCurrency());
            $total_deposit = ($threshold * 100) + $total_withdrawals + $break_threshold;
            $net_deposit = $total_deposit - $total_withdrawals;
            $dep_insert = [
                'user_id' => $user_id,
                'amount' => $total_deposit,
                'dep_type' => 'trustly',
                'timestamp' => Carbon::now()->toDateTimeString(),
                'ext_id' => uniqid('', true),
                'scheme' => "",
                'card_hash' => "",
                'loc_id' => "",
                'status' => "approved",
                'currency' => $user->getAttr('currency'),
                'display_name' => ucfirst(ucfirst('trustly')),
                'ip_num' => $user->getAttr('cur_ip'),
                'mts_id' => 0,
            ];
            $sql->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);

            $sql->sh($user_id)->insertArray('pending_withdrawals', [
                'user_id' => $user_id,
                'payment_method' => 'paypal',
                'amount' => $total_withdrawals,
                'status' => 'approved'
            ]);
            $this->printOutputData("Total deposit - $total_deposit, total withdrawals - $total_withdrawals. Diff $net_deposit");
            sleep(5);
            $rg->sourceOfIncomeCheck($user);
            sleep(30);
            $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
            $this->cleanupTestPlayer($user_id);
        }
    }

    public function testRG20(string $country)
    {
        $trigger = "RG20";

        $sql = phive('SQL');
        $rg = phive('Cashier/Rg');
        $test_player_base_deposit = 50000; // getTestPlayer already adds a deposit.

        $this->printOutputData(__METHOD__ . " Test 1");
        $break_threshold = 100;
        $total_withdrawals = 100000;
        $threshold = (int)phive('Config')->getValue('RG', "$trigger-amount", 500000);
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $this->printOutputData("Created test user ID {$user_id} from {$country}.");
        $total_deposit = $threshold + $total_withdrawals + $break_threshold + $test_player_base_deposit;
        $total_deposit_adjusted = $total_deposit - $test_player_base_deposit;
        $net_deposit = $total_deposit - $total_withdrawals;
        $dep_insert = [
            'user_id' => $user_id,
            'amount' => $total_deposit_adjusted,
            'dep_type' => 'trustly',
            'timestamp' => Carbon::now()->toDateTimeString(),
            'ext_id' => uniqid('', true),
            'scheme' => "",
            'card_hash' => "",
            'loc_id' => "",
            'status' => "approved",
            'currency' => $user->getAttr('currency'),
            'display_name' => ucfirst(ucfirst('trustly')),
            'ip_num' => $user->getAttr('cur_ip'),
            'mts_id' => 0,
        ];
        $sql->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);

        $sql->sh($user_id)->insertArray('pending_withdrawals', [
            'user_id' => $user_id,
            'payment_method' => 'paypal',
            'amount' => $total_withdrawals,
            'status' => 'approved'
        ]);
        $this->printOutputData("Total deposit - $total_deposit, total withdrawals - $total_withdrawals.
        Diff $net_deposit. Threshold $threshold");
        sleep(2);
        $rg->lostMoreThanXEuroInTheLastXDays(Carbon::now()->toDateString());
        sleep(3);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->printLatest($user, 'triggers_log', 10);
        $this->cleanupTestPlayer($user_id);

        $this->printOutputData(__METHOD__ . " Test 2");

        $total_withdrawals = 0;
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $this->printOutputData("Created test user ID {$user_id} from {$country}.");
        $total_deposit = $threshold;
        $net_deposit = $total_deposit - $total_withdrawals;

        $dep_insert = [
            'user_id' => $user_id,
            'amount' => $threshold - $test_player_base_deposit, // getTestPlayer already adds a deposit.
            'dep_type' => 'trustly',
            'timestamp' => Carbon::now()->toDateTimeString(),
            'ext_id' => uniqid('', true),
            'scheme' => "",
            'card_hash' => "",
            'loc_id' => "",
            'status' => "approved",
            'currency' => $user->getAttr('currency'),
            'display_name' => ucfirst(ucfirst('trustly')),
            'ip_num' => $user->getAttr('cur_ip'),
            'mts_id' => 0,
        ];
        $sql->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);

        $this->printOutputData("Total deposit - $total_deposit, total withdrawals - $total_withdrawals.
        Diff $net_deposit. Threshold $threshold");
        sleep(2);
        $rg->lostMoreThanXEuroInTheLastXDays(Carbon::now()->toDateString());
        sleep(3);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->printLatest($user, 'triggers_log', 10);
        $this->cleanupTestPlayer($user_id);
    }

    public function testRG38and39(string $country)
    {
        $sql = phive('SQL');
        $rg = phive('Cashier/Rg');
        $break_threshold = 100;
        $total_withdrawals = 100000;
        $triggers = phive('SQL')->loadArray("SELECT name, ngr_threshold FROM triggers WHERE name IN ('RG38', 'RG39')");

        foreach ($triggers as $trigger) {
            $this->printOutputData("test" . $trigger['name']);
            $trigger_name = $trigger['name'];
            $threshold = (int)$trigger['ngr_threshold'];
            $user = $this->getTestPlayer($country);
            $user_id = $user->getId();
            $this->printOutputData("Created test user ID {$user_id} from {$country}.");
            $total_deposit = $threshold + $total_withdrawals + $break_threshold;
            $net_deposit = $total_deposit - $total_withdrawals;
            $dep_insert = [
                'user_id' => $user_id,
                'amount' => $total_deposit,
                'dep_type' => 'trustly',
                'timestamp' => Carbon::now()->toDateTimeString(),
                'ext_id' => uniqid('', true),
                'scheme' => "",
                'card_hash' => "",
                'loc_id' => "",
                'status' => "approved",
                'currency' => $user->getAttr('currency'),
                'display_name' => ucfirst(ucfirst('trustly')),
                'ip_num' => $user->getAttr('cur_ip'),
                'mts_id' => 0,
            ];
            $sql->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);

            $sql->sh($user_id)->insertArray('pending_withdrawals', [
                'user_id' => $user_id,
                'payment_method' => 'paypal',
                'amount' => $total_withdrawals,
                'status' => 'approved'
            ]);
            $this->printOutputData("Total deposit - $total_deposit, total withdrawals - $total_withdrawals.
        Diff $net_deposit. Threshold $threshold");
            sleep(5);
            $game_session = ['end_time' => Carbon::now()->toDateTimeString()];
            $rg->checkLossAmountBasedOnNGR($user, $game_session);
            sleep(30);
            $this->msg($this->doesUserHaveTrigger($user_id, $trigger_name));
            $this->printLatest($user, 'triggers_log', 10);
            sleep(5);
            $this->cleanupTestPlayer($user_id);
        }
    }

    /**
     * @throws JsonException
     */
    public function testRG73(): void
    {
        $trigger_name = "RG73";
        $this->printOutputData($trigger_name, true);
        $country = 'DK';

        $hours_played_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = 'RG73-hours-played';
        ")['config_value'];
        $duration_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = 'RG73-duration';
        ")['config_value'];

        $config_type = json_encode([
            "type" => "template",
            "delimiter" => "::",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Hours>"
        ], JSON_THROW_ON_ERROR);

        $config_hours_played_where = [
            'config_name' => 'RG73-hours-played',
            'config_tag' => 'RG',
        ];
        $test_config_hours_played = "UKGC::1;SGA::1;DGA::1;DGOJ::0;ADM::0;AGCO::0;MGA::0";
        $this->db->shs()->save('config',
            [
                'config_name' => 'RG73-hours-played',
                'config_tag' => 'RG',
                'config_value' => $test_config_hours_played,
                'config_type' => $config_type,
            ],
            $config_hours_played_where
        );

        $config_duration_where = [
            'config_name' => 'RG73-duration',
            'config_tag' => 'RG',
        ];
        $test_config_duration = "UKGC::4;SGA::4;DGA::4;DGOJ::0;ADM::0;AGCO::0;MGA::0";
        $this->db->shs()->save('config',
            [
                'config_name' => 'RG73-duration',
                'config_tag' => 'RG',
                'config_value' => $test_config_duration,
                'config_type' => $config_type,
            ],
            $config_duration_where
        );

        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();

        $this->db->sh($user_id)->insertArray('users_game_sessions', [
            'user_id' => $user_id,
            'start_time' => date('Y-m-d H:i:s', strtotime('-2 hour')),
            'end_time' => date('Y-m-d H:i:s'),
            'game_ref' => 'xyz',
            'device_type_num' => 1,
            'ip' => '127.0.0.1',
            'bet_amount' => 0,
            'win_amount' => 0,
            'result_amount' => 0,
            'balance_start' => 0,
            'balance_end' => 0,
            'session_id' => rand(1000000, 9000000),
            'bet_cnt' => 0,
            'bets_rollback' => 0,
            'wins_rollback' => 0,
            'win_cnt' => 0,
        ]);

        $this->rgModule->hasPlayedXHoursInLastYHours($user);

        $this->printLatest($user, 'triggers_log');
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger_name));

        sleep(2);

        $this->cleanupTestPlayer($user_id);
        $config_missing = empty($hours_played_config_value || $duration_config_value);
        if ($config_missing) {
            $this->db->shs()->delete('config', $config_hours_played_where);
            $this->db->shs()->delete('config', $config_duration_where);
        } else {
            $this->db->shs()->updateArray('config', ['config_value' => $hours_played_config_value], $config_hours_played_where);
            $this->db->shs()->updateArray('config', ['config_value' => $duration_config_value], $config_duration_where);
        }

    }

    /**
     * @throws Exception
     */
    public function testRG74(): void
    {
        $trigger = "RG74";

        $rg = phive('Cashier/Rg');
        $jurisdictions = ['UKGC', 'MGA', 'SGA'];
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $duration = phive('Config')->valAsArray('RG', "$trigger-duration", ';', ':');
        $spins = phive('Config')->valAsArray('RG', "$trigger-spins", ';', ':');
        $this->printOutputData($trigger, true);

        foreach ($jurisdictions as $jurisdiction) {
            $hours = !empty((int)$duration[$jurisdiction]) ? (int)$duration[$jurisdiction] : 4;
            $spins_thold = !empty((int)$spins[$jurisdiction]) ? (int)$spins[$jurisdiction] : 10;
            $country = array_flip($country_jurisdiction_map)[$jurisdiction];
            if ($jurisdiction === 'MGA') {
                $country = 'MT';
            }

            $this->printOutputData("Spins threshold: {$spins_thold} Hours: {$hours} Jurisdictions: {$jurisdiction}");
            foreach (range(1, 2) as $step) {
                $user = $this->getTestPlayer($country);
                $user_id = $user->getId();

                $bets_rand = ($step === 1) ? ($spins_thold * 2) : ($spins_thold / 2);
                $compare_str = ($bets_rand > $spins_thold) ? "grater" : "lower";
                $this->printOutputData("Step {$step}: Number of spins {$compare_str} then {$spins_thold}");
                $this->printOutputData("Created test user ID {$user_id} Country: {$country} Jurisdiction: {$jurisdiction}");
                $this->printOutputData("Inserting {$bets_rand} spins");

                $amount = 200;
                foreach (range(1, $bets_rand) as $bet) {
                    $user_balance = $user->getBalance();
                    $tr_id = $this->randId();
                    $game_list = [
                        'playngo100984',
                        'MGS_goldBlitzV94',
                        'pragmatic_vs12bgrbspl',
                        'playtech_gpas_kgomoon_pop',
                    ];
                    $idx_rand = rand(0, (sizeof($game_list) - 1));
                    $cur_game = $game_list[$idx_rand];
                    $ext_id = $cur_game . "-dev-" . $this->randId() . "-" . $this->randId();
                    $bonus_bet = rand(0, 3);
                    phive('Casino')->insertBet($user->data, ['ext_game_name' => $cur_game], $tr_id, $ext_id, $amount,
                        0.0,
                        $bonus_bet,
                        $user_balance);
                    Phive('Casino')->changeBalance($user, -$amount);
                }
                sleep(2);
                $rg->hasPlayedXSpinsInLastYHours($user);
                sleep(2);
                $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
                $this->printLatest($user, 'triggers_log', 1);
                $this->cleanupTestPlayer($user_id, ['bets']);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testRG75(): void
    {
        $trigger = "RG75";

        $rg = phive('Cashier/Rg');
        $jurisdictions = ['UKGC', 'MGA', 'SGA'];
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $duration = phive('Config')->valAsArray('RG', "$trigger-duration", ';', ':');
        $wagers = phive('Config')->valAsArray('RG', "$trigger-wager", ';', ':');
        $this->printOutputData($trigger, true);

        foreach ($jurisdictions as $jurisdiction) {
            $hours = !empty((int)$duration[$jurisdiction]) ? (int)$duration[$jurisdiction] : 4;
            $wagers_thold = !empty((int)$wagers[$jurisdiction]) ? (int)$wagers[$jurisdiction] : 1000;
            $country = array_flip($country_jurisdiction_map)[$jurisdiction];
            if ($jurisdiction === 'MGA') {
                $country = 'MT';
            }

            $this->printOutputData("Wagers threshold: {$wagers_thold} Hours: {$hours} Jurisdictions: {$jurisdiction}");
            foreach (range(1, 2) as $step) {
                $user = $this->getTestPlayer($country);
                $user_id = $user->getId();

                $total_wagered = ($step === 1) ? ($wagers_thold * 2) : ($wagers_thold / 2);
                $compare_str = ($total_wagered > $wagers_thold) ? "greater" : "lower";
                $this->printOutputData("Step {$step}: Total wagered amount {$compare_str} than {$wagers_thold}");
                $this->printOutputData("Created test user ID {$user_id} Country: {$country} Jurisdiction: {$jurisdiction}");
                $this->printOutputData("Inserting wagers totaling {$total_wagered} units");

                $amount = 200;
                $bets_rand = ceil($total_wagered / $amount);
                foreach (range(1, $bets_rand) as $bet) {
                    $user_balance = $user->getBalance();
                    $tr_id = $this->randId();
                    $game_list = [
                        'playngo100984',
                        'MGS_goldBlitzV94',
                        'pragmatic_vs12bgrbspl',
                        'playtech_gpas_kgomoon_pop',
                    ];
                    $idx_rand = rand(0, (sizeof($game_list) - 1));
                    $cur_game = $game_list[$idx_rand];
                    $ext_id = $cur_game . "-dev-" . $this->randId() . "-" . $this->randId();
                    $bonus_bet = rand(0, 3);
                    phive('Casino')->insertBet($user->data, ['ext_game_name' => $cur_game], $tr_id, $ext_id, $amount,
                                               0.0,
                                               $bonus_bet,
                                               $user_balance);
                    Phive('Casino')->changeBalance($user, -$amount);
                }
                sleep(2);
                $rg->triggerUsersWageringInLastYHours();
                sleep(2);
                $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
                $this->printLatest($user, 'triggers_log', 1);
                $this->cleanupTestPlayer($user_id, ['bets']);
            }
        }
    }


    /**
     * @throws JsonException
     */
    public function testRG77(): void
    {
        $trigger_name = "RG77";
        $this->printOutputData($trigger_name, true);
        $country = 'DK';

        $top_depositors_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-top-depositors';
        ")['config_value'];
        $months_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-months';
        ")['config_value'];

        $config_type_top_depositors = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Top_Depositors>"
        ], JSON_THROW_ON_ERROR);

        $config_top_depositors_where = [
            'config_name' => "{$trigger_name}-top-depositors",
            'config_tag' => 'RG',
        ];
        $test_config_top_depositors = "UKGC:10;SGA:10;DGA:10;DGOJ:0;ADM:0;AGCO:0;MGA:0";
        $this->db->shs()->save('config',
            [
                'config_name' => "{$trigger_name}-top-depositors",
                'config_tag' => 'RG',
                'config_value' => $test_config_top_depositors,
                'config_type' => $config_type_top_depositors,
            ],
            $config_top_depositors_where
        );

        $config_type_months = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Months>"
        ], JSON_THROW_ON_ERROR);
        $config_months_where = [
            'config_name' => "{$trigger_name}-months",
            'config_tag' => 'RG',
        ];
        $test_config_months = "UKGC:6;SGA:6;DGA:6;DGOJ:0;ADM:0;AGCO:0;MGA:0";
        $this->db->shs()->save('config',
            [
                'config_name' => "{$trigger_name}-months",
                'config_tag' => 'RG',
                'config_value' => $test_config_months,
                'config_type' => $config_type_months,
            ],
            $config_months_where
        );

        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $jurisdiction = $user->getJurisdiction();

        $deposits = 100000000000;
        $withdrawals = 0;
        $date = date('Y-m-d', strtotime('-1 day'));
        $this->db->sh($user_id)->updateArray('users', ['register_date' => $date], ['id' => $user_id]);
        $this->db->sh($user_id)->insertArray(
            'users_daily_stats',
            [
                'date' => $date,
                'user_id' => $user_id,
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'username' => $user->getLastName(),
                'currency' => $user->getCurrency(),
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'rewards' => 0,
                'jp_contrib' => 0,
                'country' => $country,
                'province' => '',
                'affe_id' => rand(100000, 900000000),
            ]
        );

        $this->rgModule->topXDepositorsRegisteredInLastYMonths();

        $this->printLatest($user, 'triggers_log');
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger_name));

        sleep(2);

        $this->cleanupTestPlayer($user_id);
        $config_missing = empty($top_depositors_config_value || $months_config_value);
        if ($config_missing) {
            $this->db->shs()->delete('config', $config_top_depositors_where);
            $this->db->shs()->delete('config', $config_months_where);
        } else {
            $this->db->shs()->updateArray(
                'config',
                ['config_value' => $top_depositors_config_value],
                $config_top_depositors_where
            );
            $this->db->shs()->updateArray('config', ['config_value' => $months_config_value], $config_months_where);
        }

    }

    public function testRG78()
    {
        $trigger = "RG78";

        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-losing-customers'");
        $top_losers_jur = phive('Config')->getValueFromTemplate($config[0]);
        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-months'");
        $months_jur = phive('Config')->getValueFromTemplate($config[0]);

        $this->printOutputData($trigger, true);

        $jurisdiction = 'UKGC';
        $top_loser_count = !empty((int)$top_losers_jur[$jurisdiction]) ? (int)$top_losers_jur[$jurisdiction] : 5;
        $months = !empty((int)$months_jur[$jurisdiction]) ? (int)$months_jur[$jurisdiction] : 3;
        $country = array_flip($country_jurisdiction_map)[$jurisdiction];

        $this->printOutputData("Selected values for user creation jur: {$jurisdiction} country: {$country}");
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();

        $number_in_days = Carbon::today()->diffInDays(Carbon::today()->subMonths($months));
        $random_reg_date = Carbon::today()->subDays(rand(1, $number_in_days))->toDateString();
        $user->setAttr('register_date', $random_reg_date);

        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('SQL')->delete('users_daily_stats', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('rg_limits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id]);

        $deposits = 25000;
        $withdrawals = 1000;
        $loss_date = Carbon::parse($random_reg_date)->addDays(rand(1, 3))->toDateString();

        phive('SQL')->sh($user_id)->insertArray(
            'users_daily_stats',
            [
                'date' => $loss_date,
                'user_id' => $user_id,
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'username' => $user->getLastName(),
                'currency' => $user->getCurrency(),
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'rewards' => 0,
                'jp_contrib' => 0,
                'country' => $country,
                'province' => '',
                'affe_id' => rand(100000, 900000000),
            ]
        );

        $old_risk_tag = Phive('SQL')->sh($user_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$user_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
        sleep(5);
        phive('Cashier/Rg')->topXLosingCustomersInYMonths();

        $this->printOutputData("Case 1: Triggers for top {$top_loser_count} losers in {$country} only");

        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));

        $this->printOutputData("Case 2: GRS is set to medium risk");

        $this->printOutputData("Current risk of the user: {$old_risk_tag}");
        $this->printLatest($user, 'risk_profile_rating_log', 5);
        $this->printLatest($user, 'triggers_log', 5);

        phive('Cashier/Arf')->invoke('onLogin', $user_id);
        sleep(30);
        $risk_tag = Phive('SQL')->sh($user_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$user_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
        $this->printOutputData("latest risk: {$risk_tag}");
        $this->msg($risk_tag === 'Medium Risk');

        $this->cleanupTestPlayer($user_id);
    }

    public function testRG80()
    {
        $trigger = "RG80";
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-losing-young-customers'");
        $top_young_losers_jur = phive('Config')->getValueFromTemplate($config[0]);
        $config = phive('SQL')->loadArray("SELECT * FROM config WHERE config_name = '{$trigger}-months'");
        $months_jur = phive('Config')->getValueFromTemplate($config[0]);

        $this->printOutputData($trigger, true);

        $jurisdiction = 'UKGC';
        $user_age = (int)phive('Licensed')->getSetting('jurisdiction_young_age_map')[$jurisdiction];
        $user_age = ($user_age - rand(1,3));
        $top_young_loser_count = !empty((int)$top_young_losers_jur[$jurisdiction]) ? (int)$top_young_losers_jur[$jurisdiction] : 5;
        $months = !empty((int)$months_jur[$jurisdiction]) ? (int)$months_jur[$jurisdiction] : 3;
        $country = array_flip($country_jurisdiction_map)[$jurisdiction];

        $this->printOutputData("Selected values for user creation jur: {$jurisdiction} country: {$country}");
        $user = $this->getTestPlayer($country, $user_age);
        $user_id = $user->getId();

        $number_in_days = Carbon::today()->diffInDays(Carbon::today()->subMonths($months));
        $random_reg_date = Carbon::today()->subDays(rand(1, $number_in_days))->toDateString();
        $user->setAttr('register_date', $random_reg_date);

        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('SQL')->delete('users_daily_stats', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('rg_limits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id]);

        $deposits = 25000;
        $withdrawals = 1000;
        $loss_date = Carbon::parse($random_reg_date)->addDays(rand(1, 3))->toDateString();

        phive('SQL')->sh($user_id)->insertArray(
            'users_daily_stats',
            [
                'date' => $loss_date,
                'user_id' => $user_id,
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'username' => $user->getLastName(),
                'currency' => $user->getCurrency(),
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'rewards' => 0,
                'jp_contrib' => 0,
                'country' => $country,
                'province' => '',
                'affe_id' => rand(100000, 900000000),
            ]
        );

        phive('Cashier/Arf')->invoke('onLogin', $user_id);
        sleep(10);
        $old_risk_tag = Phive('SQL')->sh($user_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$user_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
        sleep(5);
        phive('Cashier/Rg')->topXLosingYoungCustomersInYMonths();
        sleep(5);
        $this->printOutputData("Case 1: Triggers for top young {$top_young_loser_count} losers in {$country} with age up to {$user_age}");

        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));

        $this->printOutputData("Case 2: GRS is set to medium risk");

        $this->printOutputData("Current risk of the user: {$old_risk_tag}");
        $this->printLatest($user, 'risk_profile_rating_log', 5);
        $this->printLatest($user, 'triggers_log', 5);

        phive('Cashier/Arf')->invoke('onLogin', $user_id);
        sleep(30);
        $risk_tag = Phive('SQL')->sh($user_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$user_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
        $this->printOutputData("latest risk: {$risk_tag}");
        $this->msg($risk_tag === 'Medium Risk');
        $this->cleanupTestPlayer($user_id);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testRG79(): void
    {
        $trigger_name = "RG79";
        $this->printOutputData($trigger_name, true);

        $top_winning_customers_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-winning-customers';
        ")['config_value'];
        $months_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-months';
        ")['config_value'];

        $top_customer_count = 5;
        $month_count = 2;
        $config_top_winning_customers_where = [
            'config_name' => "{$trigger_name}-winning-customers",
            'config_tag' => 'RG',
        ];
        $this->db->shs()->save('config',
                               [
                                   'config_value' => "UKGC:{$top_customer_count};SGA:0;DGA:0;DGOJ:0;ADM:0;AGCO:0;MGA:0",
                               ],
                               $config_top_winning_customers_where
        );

        $config_months_where = [
            'config_name' => "{$trigger_name}-days",
            'config_tag' => 'RG',
        ];
        $this->db->shs()->save('config',
                               [
                                   'config_value' => "UKGC:{$month_count};SGA:0;DGA:0;DGOJ:0;ADM:0;AGCO:0;MGA:0",
                               ],
                               $config_months_where
        );

        $jurisdiction = 'UKGC';
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $country = array_flip($country_jurisdiction_map)[$jurisdiction];

        $this->printOutputData("Selected values for user creation jur: {$jurisdiction} country: {$country}");
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();

        $number_in_days = Carbon::today()->diffInDays(Carbon::today()->subMonths($month_count));
        $random_reg_date = Carbon::today()->subDays(rand(1, $number_in_days))->toDateString();
        $user->setAttr('register_date', $random_reg_date);
        $random_deposit = random_int(50000, 150000); // Random deposit between 50,000 and 150,000
        $random_withdrawal = random_int($random_deposit + 10000, $random_deposit + 50000); // Ensure withdrawals > deposits

        phive('SQL')->sh($user_id)->delete('users_daily_stats', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('rg_limits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id]);

        $bet_amount = 1000;
        $win_amount = 50000;
        $winning_date = Carbon::parse($random_reg_date)->addDays(rand(1, 3))->toDateTimeString();

        phive('SQL')->sh($user_id)->insertArray(
            'users_daily_stats',
            [
                'date' => $winning_date,
                'user_id' => $user_id,
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'username' => $user->getLastName(),
                'currency' => $user->getCurrency(),
                'deposits' => $random_deposit,
                'withdrawals' => $random_withdrawal,
                'bets' => $bet_amount,
                'wins' => $win_amount,
                'rewards' => 0,
                'jp_contrib' => 0,
                'country' => $country,
                'province' => '',
                'affe_id' => rand(100000, 900000000),
            ]
        );

        $old_risk_tag = Phive('SQL')->sh($user_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$user_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
        phive('Cashier/Rg')->topXWinningCustomersRegisteredInLastYMonths();

        $this->printOutputData("Case 1: Triggers for top {$top_customer_count} highest winning customers in {$jurisdiction} only");

        $this->msg($this->doesUserHaveTrigger($user_id, $trigger_name));

        $this->printOutputData("Case 2: GRS is set to medium risk");

        $this->printOutputData("Current risk of the user: {$old_risk_tag}");
        $this->printLatest($user, 'risk_profile_rating_log', 5);
        $this->printLatest($user, 'triggers_log', 5);

        phive('Cashier/Arf')->invoke('onLogin', $user_id);
        sleep(30);
        $risk_tag = Phive('SQL')->sh($user_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$user_id}
                AND rating_type = 'RG'
                ORDER BY created_at DESC
                LIMIT 1");
        $this->printOutputData("latest risk: {$risk_tag}");
        $this->msg($risk_tag === 'Medium Risk');

        $this->printOutputData("Case 3: Does not trigger for user with negative or zero winnings");
        phive('SQL')->sh($user_id)->delete('users_daily_stats', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id]);

        phive('SQL')->sh($user_id)->insertArray(
            'users_daily_stats',
            [
                'date' => $winning_date,
                'user_id' => $user_id,
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'username' => $user->getLastName(),
                'currency' => $user->getCurrency(),
                'deposits' => $random_deposit,
                'withdrawals' => 0,
                'bets' => $bet_amount,
                'wins' => 0,
                'rewards' => 0,
                'jp_contrib' => 0,
                'country' => $country,
                'province' => '',
                'affe_id' => rand(100000, 900000000),
            ]
        );
        phive('Cashier/Rg')->topXWinningCustomersRegisteredInLastYMonths();
        $this->msg(!$this->doesUserHaveTrigger($user_id, $trigger_name));

        $this->printOutputData("Case 4: Does not trigger in SGA jurisdiction");
        $sga_country = array_flip($country_jurisdiction_map)['SGA'];
        $sga_user = $this->getTestPlayer($sga_country);
        $sga_user_id = $sga_user->getId();
        $sga_user->setAttr('register_date', $random_reg_date);

        phive('SQL')->sh($sga_user_id)->delete('users_daily_stats', ['user_id' => $sga_user_id]);
        phive('SQL')->sh($sga_user_id)->delete('rg_limits', ['user_id' => $sga_user_id]);
        phive('SQL')->sh($sga_user_id)->delete('triggers_log', ['user_id' => $sga_user_id]);

        phive('SQL')->sh($sga_user_id)->insertArray(
            'users_daily_stats',
            [
                'date' => $winning_date,
                'user_id' => $sga_user_id,
                'firstname' => $sga_user->getFirstName(),
                'lastname' => $sga_user->getLastName(),
                'username' => $sga_user->getLastName(),
                'currency' => $sga_user->getCurrency(),
                'deposits' => $random_deposit,
                'withdrawals' => $random_withdrawal,
                'bets' => $bet_amount,
                'wins' => $win_amount,
                'rewards' => 0,
                'jp_contrib' => 0,
                'country' => $sga_country,
                'province' => '',
                'affe_id' => random_int(100000, 900000000),
            ]
        );

        phive('Cashier/Rg')->topXUniqueBetsCustomersRegisteredInLastYDays();
        $this->msg(!$this->doesUserHaveTrigger($sga_user_id, $trigger_name));

        $config_missing = empty($top_winning_customers_config_value || $months_config_value);
        if ($config_missing) {
            $this->db->shs()->delete('config', $config_top_winning_customers_where);
            $this->db->shs()->delete('config', $config_months_where);
        } else {
            $this->db->shs()->updateArray(
                'config',
                ['config_value' => $top_winning_customers_config_value],
                $config_top_winning_customers_where
            );
            $this->db->shs()->updateArray('config', ['config_value' => $months_config_value], $config_months_where);
        }

        $this->cleanupTestPlayer($user_id);
        $this->cleanupTestPlayer($sga_user_id);
    }

    /**
     * @throws JsonException
     */
    public function testRG81(): void
    {
        $trigger_name = "RG81";
        $this->printOutputData($trigger_name, true);

        // SETUP CONFIGS
        $this->printOutputData("SETTING UP CONFIGS");
        $top_unique_bets_customers_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-top-unique-bets-customers';
        ")['config_value'];
        $days_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-days';
        ")['config_value'];

        $top_customer_count = 5;
        $days_count = 5;

        $config_type_top_unique_bets_customers = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Top_unique_bets_customers>"
        ], JSON_THROW_ON_ERROR);
        $config_top_unique_bets_customers_where = [
            'config_name' => "{$trigger_name}-top-unique-bets-customers",
            'config_type' => $config_type_top_unique_bets_customers,
            'config_tag' => "RG",
            'config_value' => "UKGC:{$top_customer_count};SGA:0;DGA:0;DGOJ:0;ADM:0;AGCO:0;MGA:0",
        ];
        $this->db->insertOrUpdate(
            'config',
            $config_top_unique_bets_customers_where,
            ['config_value' => "UKGC:{$top_customer_count};SGA:0;DGA:0;DGOJ:0;ADM:0;AGCO:0;MGA:0",],
        );

        $config_type_days = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Days>"
        ], JSON_THROW_ON_ERROR);
        $config_days_where = [
            'config_name' => "{$trigger_name}-days",
            'config_tag' => "RG",
            'config_type' => $config_type_days,
            'config_value' => "UKGC:{$days_count};SGA:0;DGA:0;DGOJ:0;ADM:0;AGCO:0;MGA:0",
        ];
        $this->db->insertOrUpdate(
            'config',
            $config_days_where,
            ['config_value' => "UKGC:{$days_count};SGA:0;DGA:0;DGOJ:0;ADM:0;AGCO:0;MGA:0",],
        );
        // END SETUP CONFIGS

        // SETUP TEST PLAYERS
        $this->printOutputData("SETTING UP TEST PLAYERS");
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');

        $reg_date = Carbon::today()->subDays($days_count + 1)->toDateString();

        $jurisdiction_ukgc = 'UKGC';
        $country_ukgc = array_flip($country_jurisdiction_map)[$jurisdiction_ukgc];
        $user_ukgc = $this->getTestPlayer($country_ukgc);
        $user_id_ukgc = $user_ukgc->getId();
        $user_ukgc->setAttr('register_date', $reg_date);

        $jurisdiction_sga = 'SGA';
        $country_sga = array_flip($country_jurisdiction_map)[$jurisdiction_sga];
        $user_sga = $this->getTestPlayer($country_sga);
        $user_id_sga = $user_sga->getId();
        $user_sga->setAttr('register_date', $reg_date);

        $test_players = [$user_ukgc, $user_sga];
        // END SETUP TEST PLAYERS

        // ADD DATA FOR TESTING
        $this->printOutputData("SETTING UP DB WITH DATA");
        // NOTE: We are using an arbitrary high number here for nr of unique bets.
        // This doesn't ensure that the test users will be at the top X.
        // No current solution for this, since for most testing envs this is potentially not really required.
        $nr_of_unique_bets = 100;
        $bet_date = Carbon::now()->subDays($days_count)->toDateString();

        $this->printOutputData("user_ids");
        foreach ($test_players as $test_player) {
            $test_player_id = $test_player->getId();

            foreach (range(1, $nr_of_unique_bets) as $i) {
                phive('SQL')->sh($test_player_id)->insertArray(
                    'users_daily_unique_bets',
                    [
                        'user_id' => $test_player_id,
                        'amount' => $i,
                        'date' => $bet_date,
                        'count' => 1,
                    ]
                );
            }

            $test_player_jurisdiction = $test_player->getJurisdiction();
            $this->printOutputData("$test_player_id|$test_player_jurisdiction");
        }

        // TEST 1
        $old_risk_tag = $this->getLatestRiskLevel($user_id_ukgc);

        phive('Cashier/Rg')->topXUniqueBetsCustomersRegisteredInLastYDays();

        $this->printOutputData("Selected values for user creation jur: {$jurisdiction_ukgc} country: {$country_ukgc}");
        $this->printOutputData("Case 1: Triggers for top {$top_customer_count} unique bets in {$jurisdiction_ukgc} only");

        $this->msg($this->doesUserHaveTrigger($user_id_ukgc, $trigger_name));
        // END TEST 1

        // TEST 2
        $sleep = 9;
        $this->printOutputData("Case 2: RG Risk level is set to medium risk - sleep($sleep)");

        sleep($sleep);

        $new_risk_tag = $this->getLatestRiskLevel($user_id_ukgc);

        $this->printOutputData("Current risk of the user: {$old_risk_tag}");
        $this->printOutputData("latest risk: {$new_risk_tag}");
        $this->msg($new_risk_tag === 'Medium Risk');
        // END TEST 2

        // TEST 3
        $this->printOutputData("Case 3: Does not trigger in SGA jurisdiction");

        phive('Cashier/Rg')->topXUniqueBetsCustomersRegisteredInLastYDays();

        $this->msg(!$this->doesUserHaveTrigger($user_id_sga, $trigger_name));
        // END TEST 3

        // CLEANUP
        $this->printOutputData("RESTORING CONFIG STATE");
        $config_missing = empty($top_unique_bets_customers_config_value || $days_config_value);
        if ($config_missing) {
            $this->db->shs()->delete('config', $config_top_unique_bets_customers_where);
            $this->db->shs()->delete('config', $config_days_where);
        } else {
            $this->db->shs()->updateArray('config', ['config_value' => $top_unique_bets_customers_config_value], $config_top_unique_bets_customers_where);
            $this->db->shs()->updateArray('config', ['config_value' => $days_config_value], $config_days_where);
        }

        $this->printOutputData("REMOVING TEST PLAYERS AND THEIR DATA");
        foreach ($test_players as $test_player) {
            $this->cleanupTestPlayer($test_player->getId());
        }
        //END CLEANUP

        $this->printOutputData("RG81 Test finished");
    }

    /**
     * @throws JsonException
     */
    public function testRG82(): void
    {
        $trigger_name = "RG82";
        $this->printOutputData($trigger_name, true);
        $country = 'DK';

        $top_time_spent_config_name = 'top-time-spent-customers';
        $days_config_name = 'days';

        $top_time_spent_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-{$top_time_spent_config_name}';
        ")['config_value'];
        $days_config_value = $this->db->loadAssoc("
            SELECT config_value
            FROM config
            WHERE config_tag = 'RG'
              AND config_name = '{$trigger_name}-{$days_config_name}';
        ")['config_value'];

        $config_type_top_time_spent = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Top time spent customers>"
        ], JSON_THROW_ON_ERROR);
        $config_top_time_spent_where = [
            'config_name' => "{$trigger_name}-{$top_time_spent_config_name}",
            'config_tag' => 'RG',
        ];
        $test_config_top_depositors = "UKGC:10;SGA:10;DGA:10;DGOJ:0;ADM:0;AGCO:0;MGA:0";

        $this->db->shs()->save('config',
            [
                'config_name' => "{$trigger_name}-{$top_time_spent_config_name}",
                'config_tag' => 'RG',
                'config_value' => $test_config_top_depositors,
                'config_type' => $config_type_top_time_spent,
            ],
            $config_top_time_spent_where
        );

        $config_type_days = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Days>"
        ], JSON_THROW_ON_ERROR);
        $config_days_where = [
            'config_name' => "{$trigger_name}-{$days_config_name}",
            'config_tag' => 'RG',
        ];
        $test_config_days = "UKGC:7;SGA:7;DGA:7;DGOJ:0;ADM:0;AGCO:0;MGA:0";

        $this->db->shs()->save('config',
            [
                'config_name' => "{$trigger_name}-{$days_config_name}",
                'config_tag' => 'RG',
                'config_value' => $test_config_days,
                'config_type' => $config_type_days,
            ],
            $config_days_where
        );

        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $jurisdiction = $user->getJurisdiction();
        $this->printOutputData("User: {$user_id}", true);

        $start_time = date('Y-m-d H:i:s', strtotime('-128 hour'));
        $end_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $session = [
            'user_id' => $user_id,
            'created_at' => $start_time,
            'updated_at' => $end_time,
            'ended_at' => $end_time,
            'equipment' => 'PC',
            'end_reason' => 'forced logout',
            'ip' => '',
            'fingerprint' => '',
            'ip_classification_code' => '',

        ];
        phive('SQL')->sh($user_id)->insertArray('users_sessions', $session);

        $this->rgModule->topXUsersTimeSpentOnSite();

        $this->printLatest($user, 'triggers_log');
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger_name));

        sleep(2);

        $this->cleanupTestPlayer($user_id);
        $config_missing = empty($top_time_spent_config_value || $days_config_value);
        if ($config_missing) {
            $this->db->shs()->delete('config', $config_top_time_spent_where);
            $this->db->shs()->delete('config', $config_days_where);
        } else {
            $this->db->shs()->updateArray(
                'config',
                ['config_value' => $top_time_spent_config_value],
                $config_top_time_spent_where
            );
            $this->db->shs()->updateArray('config',
                ['config_value' => $days_config_value],
                $config_days_where
            );
        }

    }

    public function testRG22($country = 'GB'): void
    {
        $trigger_name = "RG22";
        $this->printOutputData($trigger_name, true);
        // 1. creating first test customer
        $user1 = $this->getTestPlayer($country);
        $user1_id = $user1->getId();
        $user1_dob = $user1->getAttr('dob');
        $user1_lastname = $user1->getAttr('lastname');
        $generic_user_data = [
            'city' => "Carlos Elmer's",
            'address' => "Elliott's Road 100",
            'zipcode' => "TD11/3JZ",
            'username' => 'john@gmail.test'
        ];
        $this->db->sh($user1_id)->updateArray(
            'users',
            $generic_user_data,
            ['id' => $user1_id]
        );
        $generic_user_data["dob"] = $user1_dob;
        $generic_user_data["lastname"] = $user1_lastname;
        $output1 = "Created test user {$user1_id}. User data: " . json_encode($generic_user_data, JSON_THROW_ON_ERROR);
        $this->printOutputData($output1, true);

        // 2. creating second test customer
        $user2 = $this->getTestPlayer($country);
        $user2_id = $user2->getId();
        $generic_user_data["username"] = 'mark@gmail.test';
        $this->db->sh($user2_id)->updateArray(
            'users',
            $generic_user_data,
            ['id' => $user2_id]
        );
        $output2 = "Created test user {$user2_id}. User data: " . json_encode($generic_user_data, JSON_THROW_ON_ERROR);
        $this->printOutputData($output2, true);
        sleep(3);
        // 3. check the triggering logic
        //refresh user data
        $user1 = cu($user1->getId());
        phive('Cashier/Rg')->otherLinks($user1);
        sleep(5);
        $this->msg($this->doesUserHaveTrigger($user1_id, $trigger_name));
        $this->printLatest($user1, 'triggers_log');
    }
}
