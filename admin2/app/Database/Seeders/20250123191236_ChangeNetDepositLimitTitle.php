<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class ChangeNetDepositLimitTitle extends Seeder
{
    private Connection $connection;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'paynplay.error.monthly-net-deposit-limit-reached.title',
            'value' => 'Monthly Net Deposit Threshold',
        ],
        [
            'language' => 'en',
            'alias' => 'net.deposit.limit.info.month.header',
            'value' => 'Monthly Net Deposit Threshold'
        ],
        [
            'language' => 'en',
            'alias' => 'api.net.deposit.limit.not.reached.error',
            'value' => 'Net deposit threshold has not been reached'
        ],
        [
            'language' => 'en',
            'alias' => 'paynplay.error.monthly-net-deposit-limit-reached.description',
            'value' => '<p>Because your safety is important to us, you cannot deposit at the moment because you have reached your Casino Net Deposit Threshold. This limit will reset at the end of the month at 00:00 GMT.</p><p> If you wish to increase this limit, click the ‘Request limit increase’ button below and a support agent will be in contact with you.</p>',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        foreach ($this->data as $item) {
            $this->connection
                ->table($this->table)
                ->where('alias',$item['alias'])
                ->where('language',$item['language'])
                ->update(['value' => $item['value']]);
        }
    }
}