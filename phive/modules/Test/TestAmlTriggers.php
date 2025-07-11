<?php

use Carbon\Carbon;

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36';

class TestAmlTriggers extends TestPhive
{
    /**
     * AML52 - Self-Excluder refund
     *
     * @param string $country
     *
     * @return bool
     * @throws Throwable
     */
    public function testAML52(string $country = 'GB'): bool
    {
        $trigger = 'AML52';
        $configs = phive('Config')->valAsArray('AML', 'AML52');
        $by_country_config = [];
        foreach($configs as $config){
            list($allowed_country, $balance_min) = explode(':', $config);
            $by_country_config[$allowed_country] = $balance_min;
        }
        $countries_list = implode(',', array_keys($by_country_config));

        throw_if(
            !array_key_exists($country, $by_country_config),
            new RuntimeException("The $country country does not match the configuration: $countries_list")
        );
        $this->printOutputData($trigger, true);
        $this->printOutputData("Country: " . $country);
        $this->printOutputData("Cash balance thold: " . $by_country_config[$country]);
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $balance_threshold_in_cents = $by_country_config[$country] * 100;
        $this->printOutputData("Case1 (negative): User has not been self excluded. Should not trigger");
        phive('Cashier/Aml')->selfExcluderRefund();
        sleep(30);
        $this->msg(!$this->doesUserHaveTrigger($user_id, $trigger));
        $this->printOutputData(
            "Case2 (negative): User is self excluded but cash_balance is set lower than thold. Should not trigger."
        );
        $user->setAttr('cash_balance', $balance_threshold_in_cents - 50);
        phive('DBUserHandler')->selfExclude(cu($user_id), 30);
        phive('Cashier/Aml')->selfExcluderRefund();
        sleep(30);
        $user->refresh();
        $this->printOutputData("Cash balance: " . $user->getAttribute('cash_balance') / 100);
        $this->msg(!$this->doesUserHaveTrigger($user_id, $trigger));
        $this->printOutputData(
            "Case3 (positive): User is self excluded and cash_balance reached threshold. Should trigger."
        );
        $user->setAttr('cash_balance', $balance_threshold_in_cents + 50);
        phive('Cashier/Aml')->selfExcluderRefund();
        sleep(30);
        $user->refresh();
        $this->printOutputData("Cash balance: " . $user->getAttribute('cash_balance') / 100);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->printLatest($user, 'triggers_log', 1);

        $this->cleanupTestPlayer($user_id);
        return true;
    }

    /**
     * AML Risk Profile Rating of people with a Deposited amount last 12 months score of 100
     *
     * @param string $country
     *
     * @return void
     */
    function testAML43(string $country = 'GB'): bool
    {
        $trigger = 'AML43';
        $this->printOutputData($trigger, true);
        $this->printOutputData("Country: " . $country);
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $this->printOutputData(
            "Add info to users_daily_stats to met the triggering condition."
        );
        $uds = [
            'username' => $user->getUsername(),
            'bets' => 1,
            'affe_id' => rand(100000, 900000000),
            'firstname' => $user->data['firstname'],
            'user_id' => $user_id,
            'aff_rate' => 0.5,
            'lastname' => $user->data['lastname'],
            'deposits' => 9000001,
            'withdrawals' => 5000,
            "currency" => "EUR",
            "country" => $country,
            'date' => date('Y-m-d'),

        ];
        phive('SQL')->sh($user_id)->save('users_daily_stats', $uds);
        phive('Casino')->depositCash($user_id, 1000, 'wirecard', uniqid('', true), 'visa');
        sleep(10);
        $this->msg($this->doesUserHaveTrigger($user_id, $trigger));
        $this->cleanupTestPlayer($user_id);
        return true;
    }

    public function testAML58()
    {
        $trigger = "AML58";

        $config = phive('SQL')->loadAssoc("SELECT * FROM config WHERE config_name = '{$trigger}-top-depositor'");
        $config_values = phive('Config')->getValueFromTemplate($config);

        Phive('SQL')->query("DELETE FROM users_daily_stats WHERE date >= '".Carbon::today()->subWeek(2)->toDateString()."' ");

        $users_array = [];
        $users_before_period = [];
        $users_after_period = [];

        $this->printOutputData($trigger, true);

        foreach ($config_values as $depositor_count => $countries) {
            $depositor_count = (int) $depositor_count;

            foreach ($countries as $user_country) {
                foreach (range(1, ($depositor_count + 2)) as $count) {
                    $user = $this->getTestPlayer($user_country);
                    $user_id = $user->getId();
                    $user_currency = $user->getCurrency();

                    phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
                    phive('SQL')->delete('users_daily_stats', ['user_id' => $user_id]);
                    phive('SQL')->sh($user_id)->delete('rg_limits', ['user_id' => $user_id]);
                    phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id]);

                    $deposit = random_int(100, 200);
                    $deposit_date = Carbon::today()->subWeek()->startOfWeek()->addDays(rand(1, 6))->toDateString();

                    phive('SQL')->insertArray(
                        'users_daily_stats',
                        [
                            'username' => $user->getUsername(),
                            'affe_id' => rand(100000, 900000000),
                            'firstname' => $user->data['firstname'],
                            'user_id' => $user_id,
                            'aff_rate' => 0.5,
                            'lastname' => $user->data['lastname'],
                            'deposits' => $deposit,
                            "currency" => $user_currency,
                            "country" => $user_country,
                            'date' => $deposit_date,

                        ]
                    );

                    $users_array[$depositor_count][$user_country][] = [
                        'user_id' => $user_id,
                        'deposit' => $deposit,
                        'date' => $deposit_date,
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
                        'username' => $before_user->getUsername(),
                        'affe_id' => rand(100000, 900000000),
                        'firstname' => $before_user->data['firstname'],
                        'user_id' => $before_user_id,
                        'aff_rate' => 0.5,
                        'lastname' => $before_user->data['lastname'],
                        'deposits' => 100,
                        "currency" => $before_user->getCurrency(),
                        "country" => $user_country,
                        'date' => $before_date,

                    ]
                );

                $users_before_period[$depositor_count][$user_country] = $before_user_id;
                sleep(5);

                // create a user after the period
                $after_user = $this->getTestPlayer($user_country);
                $after_user_id = $after_user->getId();

                $after_date = Carbon::today()->toDateString();
                phive('SQL')->insertArray(
                    'users_daily_stats',
                    [
                        'username' => $after_user->getUsername(),
                        'affe_id' => rand(100000, 900000000),
                        'firstname' => $after_user->data['firstname'],
                        'user_id' => $after_user_id,
                        'aff_rate' => 0.5,
                        'lastname' => $after_user->data['lastname'],
                        'deposits' => 100,
                        "currency" => $after_user->getCurrency(),
                        "country" => $user_country,
                        'date' => $after_date,

                    ]
                );

                $users_after_period[$depositor_count][$user_country] = $after_user_id;
                sleep(5);
            }
        }

        phive('Cashier/Aml')->customerIsTopDepositor();
        sleep(5);

        $this->printOutputData("Users created within the period");
        print_r($users_array);

        foreach ($config_values as $depositor_count => $countries) {
            foreach ($countries as $country) {
                $this->printOutputData("Case 1: Triggers for top {$depositor_count} depositors in {$country} only");

                $top_depositors = $users_array[$depositor_count][$country];

                // sort array by deposit
                usort($top_depositors, function ($a, $b){
                    return $a['deposit'] > $b['deposit'] ? -1 : 1;
                });
                $top_depositors = array_chunk($top_depositors, $depositor_count)[0];

                $this->printOutputData("Top depositors are: ");
                print_r($top_depositors);

                $trigger_count = 0;

                foreach($top_depositors as $depositor) {
                    if ($this->doesUserHaveTrigger($depositor['user_id'], $trigger)) {
                        $trigger_count++;
                    }
                }
                $this->msg(($trigger_count === count($top_depositors)));

                $this->printOutputData("Case 2: Top depositor before previous week should not be considered");
                $this->msg(!$this->doesUserHaveTrigger($users_before_period[$depositor_count][$country], $trigger));
                sleep(5);

                $this->printOutputData("Case 3: Top depositor after previous week should not be considered");
                $this->msg(!$this->doesUserHaveTrigger($users_after_period[$depositor_count][$country], $trigger));

                $this->printOutputData("Case 4: GRS is set to medium risk");
                $random_top_depositor = $top_depositors[rand(0, count($top_depositors) - 1)];
                $random_depositor_id = $random_top_depositor['user_id'];
                $this->printOutputData("Selected top depositor: {$random_depositor_id}");

                $old_risk_tag = Phive('SQL')->sh($random_depositor_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$random_depositor_id}
                AND rating_type = 'AML'
                ORDER BY created_at DESC
                LIMIT 1");
                $this->printOutputData("Current risk of the user: {$old_risk_tag}");
                $this->printLatest(cu($random_depositor_id), 'risk_profile_rating_log', 5);
                $this->printLatest(cu($random_depositor_id), 'triggers_log', 10);

                phive('Cashier/Arf')->invoke('onLogin', $random_depositor_id);
                sleep(5);
                $risk_tag = Phive('SQL')->sh($random_depositor_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$random_depositor_id}
                AND rating_type = 'AML'
                ORDER BY created_at DESC
                LIMIT 1");
                $this->printOutputData("latest risk: {$risk_tag}");
                $this->msg($risk_tag === 'Medium Risk');

                $this->printOutputData("Case 5: For 2 triggers in 30 days, GRS is set to high risk");
                phive('SQL')->sh($random_depositor_id)->insertArray('triggers_log', [
                    'user_id' => $random_depositor_id,
                    'trigger_name' => $trigger,
                    'created_at' => phive()->hisMod('-15 days'),
                    'descr' => '',
                    'data' => '',
                    'cnt' => 0,
                    'txt' => ''
                ]);
                // invoke grs calculation
                phive('Cashier/Arf')->invoke('onLogin', $random_depositor_id);
                sleep(5);
                // check if its set to high risk
                $risk_tag = Phive('SQL')->sh($random_depositor_id)->getValue("SELECT rating_tag
                FROM risk_profile_rating_log WHERE user_id = {$random_depositor_id}
                AND rating_type = 'AML'
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
}
