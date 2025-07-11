<?php

require_once __DIR__ . '/../../../phive/phive.php';
use Carbon\Carbon;

$count_users = $argv[1] ?? 1;
$currency = $argv[2] ?? 'EUR' ;
$country = $argv[3] ?? 'MT';
$province = $argv[4] ?? '';
#--------------------------------------------------------------------------
# Entity creation logic
#--------------------------------------------------------------------------

interface LoggerInterface
{
    public function terminal(array $context): void;
}

class UserLogger implements LoggerInterface
{
    public function terminal(array $context): void
    {
        echo "#--------------------------------------------------------------------------" . PHP_EOL;
        echo "# User created successfully:                                               " . PHP_EOL;
        echo "#--------------------------------------------------------------------------" . PHP_EOL;
        echo "#                                                                          " . PHP_EOL;
        echo "# id: {$context['id']}                                                     " . PHP_EOL;
        echo "# jurisdiction: {$context['country']}                                      " . PHP_EOL;
        echo "# username: {$context['username']}                                         " . PHP_EOL;
        echo "# password: {$context['password']} = 123456                                " . PHP_EOL;
        echo "#                                                                          " . PHP_EOL;
    }
}

class Entity
{
    protected $table;

    protected $database;

    protected $attributes;

    protected $printer = null;

    public static function create(string $entity)
    {
        return new static($entity, phive('SQL'));
    }

    public function __construct(string $entity, SQL $database)
    {
        $this->database = $database;
        $this->table = $entity;
    }

    public function setLogger(LoggerInterface $printer)
    {
        $this->printer = $printer;
        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->printer;
    }

    public function setAttributes(array $attributes = [])
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function saveGlobal(): ?int
    {
        $this->database->beginTransaction();

        try {
            $entity_id = $this->database->insertArray(
                $this->getTable(),
                $this->getAttributes()
            );

            $this->setAttributes(array_merge(
                $this->getAttributes(), ['id' => (int) $entity_id]
            ));

            if (empty($entity_id)) {
                throw new \Exception("Error creating global {$this->getTable()} entity: " . json_encode($this->getAttributes()));
            }

            $this->database->sh(cu($entity_id, 'id', true))->insertArray(
                $this->getTable(),
                $this->getAttributes()
            );

            if (empty($entity_id)) {
                throw new \Exception("Error creating sharded {$this->getTable()} entity: " . json_encode($this->getAttributes()));
            }

            if ($this->getLogger() instanceof LoggerInterface) {
                $this->getLogger()->terminal($this->getAttributes());
            }

            $this->database->commitTransaction();
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            $this->database->rollbackTransaction();
        }

        return $entity_id ?? null;
    }

    public function saveShard(object $user)
    {
        $this->database->beginTransaction();

        if (is_array($this->getAttributes())) {
            foreach ($this->getAttributes() as $attribute) {
                try {
                    $this->database->sh($user)->insertArray(
                        $this->getTable(),
                        $attribute
                    );

                    if ($this->getLogger() instanceof LoggerInterface) {
                        $this->getLogger()->terminal($this->getAttributes());
                    }

                } catch (\Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                    $this->database->rollbackTransaction();
                }
            }
        }

        $this->database->commitTransaction();

    }
}

for ($i = 1; $i <= $count_users; $i++)
{
    $user_number = random_int(10000, 99999);
    $user_id = Entity::create('users')
        ->setLogger(new UserLogger)
        ->setAttributes([
            'email' => "db-filter{$user_number}@videoslots.com",
            'mobile' => '123454589',
            'country' => $country,
            'last_login' => '0000-00-00 00:00:00',
            'newsletter' => 0,
            'sex' => 'Male',
            'lastname' => "Doe {$user_number}",
            'firstname' => 'John ',
            'address' => 'lfred Craig Street, Pieta, PTA',
            'city' => 'Pieta',
            'zipcode' => '345345',
            'dob' => '1976-01-01',
            'preferred_lang' => 'en',
            'username' => "devtest{$country}{$province}{$user_number}",
            'password' => 'c79194b0356573ee78398fc6486b4644',
            'bonus_code' => 'ghghfjjf',
            'register_date' => Carbon::today()->format('Y-m-d'),
            'cash_balance' => 99999999,
            'bust_treshold' => 50,
            'reg_ip' => '',
            'active' => 1,
            'verified_phone' => 1,
            'friend' => '',
            'alias' => "gp-integrations{$user_number}",
            'last_logout' => '0000-00-00 00:00:00',
            'cur_ip' => '',
            'logged_in' => 0,
            'currency' => $currency,
            'affe_id' => 0,
            'nid' => ''
        ])
        ->saveGlobal();

    if (empty($user_id)) {
        continue;
    }

    $user = cu($user_id, 'id', true);

    if ($country === 'CA' && !empty($province)) {
        Entity::create('users_settings')
            ->setAttributes([
                ['user_id' => $user_id, 'setting' => 'main_province', 'value' => $province]
            ])
            ->saveShard($user);
    }

    Entity::create('users_settings')
        ->setAttributes([
            ['user_id' => $user_id, 'setting' => 'accept_offers', 'value' => '0'],
            ['user_id' => $user_id, 'setting' => 'acuris_full_res', 'value' => '{
          "recordsFound": 0,
          "matches": []
        }'],
            ['user_id' => $user_id, 'setting' => 'acuris_pep_res', 'value' => 'NO MATCH'],
            ['user_id' => $user_id, 'setting' => 'bankpic', 'value' => '569d8a73cc758.jpg'],
            ['user_id' => $user_id, 'setting' => 'bankpic-verified', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'bankpic_orig', 'value' => 'black.jpg'],
            ['user_id' => $user_id, 'setting' => 'betmax-lim', 'value' => '300'],
            ['user_id' => $user_id, 'setting' => 'betmax-lim_duration', 'value' => 'none'],
            ['user_id' => $user_id, 'setting' => 'betmax-lim_stamp', 'value' => '2016-10-26 15:15:38'],
            ['user_id' => $user_id, 'setting' => 'betmax-lim_unlock', 'value' => '2016-10-26 14:15:38'],
            ['user_id' => $user_id, 'setting' => 'booster_vault', 'value' => '219'],
            ['user_id' => $user_id, 'setting' => 'bos_mobile_search_filter_selection', 'value' => '{"start_format":"all","category":"all","status":"all","str_search":""}'],
            ['user_id' => $user_id, 'setting' => 'c101_id', 'value' => '238794'],
            ['user_id' => $user_id, 'setting' => 'calls', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'captcha-login-allowed-DK', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'captcha-login-allowed-IE', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'captcha-login-allowed-IT', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'captcha-login-allowed-SK', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'cur-betmax-lim', 'value' => '300'],
            ['user_id' => $user_id, 'setting' => 'cur-lgaloss-lim', 'value' => '1000000'],
            ['user_id' => $user_id, 'setting' => 'cur-lgawager-lim', 'value' => '20000'],
            ['user_id' => $user_id, 'setting' => 'deposit-freeroll-num', 'value' => '3'],
            ['user_id' => $user_id, 'setting' => 'email_code_verified', 'value' => 'yes'],
            ['user_id' => $user_id, 'setting' => 'file_1453165171', 'value' => '569d8a73cc758.jpg'],
            ['user_id' => $user_id, 'setting' => 'generic_communications', 'value' => '0'],
            ['user_id' => $user_id, 'setting' => 'has_interac', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'has_privacy_settings', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'id3global_pep_full_res', 'value' => '{"AuthenticateSPResult":{"AuthenticationID":"bd4bbc8f-7732-4778-a917-bf5c37d51ac4","Timestamp":"2019-12-06T15:09:07.1278061+00:00","CustomerRef":"PEPs and Sanctions check","ProfileID":"8e0785f8-fca8-4acb-a9c1-292d98d89929","ProfileName":"PEP & Sanctions ","ProfileVersion":1,"ProfileRevision":1,"ProfileState":"Effective","ResultCodes":{"GlobalItemCheckResultCodes":[{"Name":"International Sanctions (Enhanced)","Description":"International Sanctions (Enhanced) check.  Provides authentication against multiple Sanctions and Enforcement lists across the globe (lists are selectable at profile level)","Match":{"GlobalItemCheckResultCode":{"Description":"Supplied full name did not match.","Code":3500}},"ID":208,"Pass":"NA","Address":"Nomatch","Forename":"Nomatch","Surname":"Nomatch","DOB":"Nomatch","Alert":"Nomatch","SanctionsMatches":{}},{"Name":"International PEP (Enhanced)","Description":"International PEP (Enhanced) Database check.  Provides authentication against Politically Exposed Persons lists from across the globe (contains known associates and known alias details)","Match":{"GlobalItemCheckResultCode":{"Description":"Supplied full name did not match.","Code":3500}},"ID":209,"Pass":"NA","Address":"NA","Forename":"Nomatch","Surname":"Nomatch","DOB":"Nomatch","Alert":"Nomatch","SanctionsMatches":{}}]},"Score":0,"BandText":"Pass","Country":"International"}}'],
            ['user_id' => $user_id, 'setting' => 'id3global_pep_res', 'value' => 'PASS'],
            ['user_id' => $user_id, 'setting' => 'lgaloss-lim', 'value' => '1000000'],
            ['user_id' => $user_id, 'setting' => 'lgaloss-lim_duration', 'value' => 'month'],
            ['user_id' => $user_id, 'setting' => 'lgaloss-lim_stamp', 'value' => '2018-11-28 15:45:02'],
            ['user_id' => $user_id, 'setting' => 'lgawager-lim', 'value' => '20000'],
            ['user_id' => $user_id, 'setting' => 'lgawager-lim_duration', 'value' => 'day'],
            ['user_id' => $user_id, 'setting' => 'lgawager-lim_stamp', 'value' => '2018-12-27 09:00:02'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-BG', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-CA', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-CR', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-DE', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-FI', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-GB', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-GI', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-LT', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-LU', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-MT', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-PH', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-PL', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-RO', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-SE', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-SI', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-UA', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'login-allowed-US', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'manual_adjustment-fraud-flag', 'value' => '2020-09-03 13:22:17'],
            ['user_id' => $user_id, 'setting' => 'mb_email_start', 'value' => 'vadim.jefimenko@videoslots.com'],
            ['user_id' => $user_id, 'setting' => 'mp-hiw-general-understood', 'value' => 'yes'],
            ['user_id' => $user_id, 'setting' => 'mp-hiw-types-understood', 'value' => 'yes'],
            ['user_id' => $user_id, 'setting' => 'newsletter', 'value' => '0'],
            ['user_id' => $user_id, 'setting' => 'pp-version', 'value' => '1.0'],
            ['user_id' => $user_id, 'setting' => 'privacy-bonus-direct-mail', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-bonus-interactive-voice', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-bonus-outbound-calls', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-main-new-email', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-main-new-sms', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-main-promo-email', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-main-promo-sms', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-main-status-email', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-main-status-sms', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'privacy-pinfo-hidealias', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'realtime_updates', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'segment', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'segment-old', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'show_in_events', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'show_notifications', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'sms', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'sms_email_communication', 'value' => '0'],
            ['user_id' => $user_id, 'setting' => 'sms_on_login', 'value' => '0'],
            ['user_id' => $user_id, 'setting' => 'source_of_funds_activated', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'tc-version', 'value' => '2.10'],
            ['user_id' => $user_id, 'setting' => 'test_account', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'uagent', 'value' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.57'],
            ['user_id' => $user_id, 'setting' => 'verified', 'value' => '1'],
            ['user_id' => $user_id, 'setting' => 'xp-level', 'value' => '20'],
            ['user_id' => $user_id, 'setting' => 'xp-points', 'value' => '19853.1033125'],
        ])
        ->saveShard($user);
}
