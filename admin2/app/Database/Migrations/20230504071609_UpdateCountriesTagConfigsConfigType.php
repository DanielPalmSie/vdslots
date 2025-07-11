<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;


class UpdateCountriesTagConfigsConfigType extends Migration
{

    private Connection $connection;
    private string $table;
    private array $emails;

    public function init()
    {
        $this->table = 'config';
        $this->connection = DB::getMasterConnection();
        $this->emails = [
            'no-deposit-weekly',
            'nodeposit-newbonusoffers-mail-x',
            'deposit-newbonusoffers-mail-x',
            '1w-inactive-x',
            '2w-inactive-x',
            '1w-inactive-100',
            '1w-inactive-200',
            '1w-inactive-500',
            '2w-inactive-100',
            '2w-inactive-200',
            '2w-inactive-500',
            'welcome.mail',
            'welcome.mrvegas',
            'no-deposit-weekly',
            'weekly-bonuslovers',
            'nodeposit-newbonusoffers-mail-1',
            'nodeposit-newbonusoffers-mail-2',
            'nodeposit-newbonusoffers-mail-3',
            'nodeposit-newbonusoffers-mail-4',
            'nodeposit-newbonusoffers-mail-5',
            'nodeposit-newbonusoffers-mail-6',
            'nodeposit-newbonusoffers-mail-7',
            'nodeposit-newbonusoffers-mail-8',
            'nodeposit-newbonusoffers-mail-9',
            'nodeposit-newbonusoffers-mail-10',
            'nodeposit-newbonusoffers-mail-11',
            'nodeposit-newbonusoffers-mail-12',
            'nodeposit-newbonusoffers-mail-13',
            'nodeposit-newbonusoffers-mail-14',
            'nodeposit-newbonusoffers-mail-15',
            'deposit-newbonusoffers-mail-1',
            'deposit-newbonusoffers-mail-2',
            'deposit-newbonusoffers-mail-3',
            'deposit-newbonusoffers-mail-4',
            'deposit-newbonusoffers-mail-5',
            'deposit-newbonusoffers-mail-6',
            'deposit-newbonusoffers-mail-7',
            'deposit-newbonusoffers-mail-8',
            'deposit-newbonusoffers-mail-9',
            'deposit-newbonusoffers-mail-10',
            'deposit-newbonusoffers-mail-11',
            'deposit-newbonusoffers-mail-12',
            'deposit-newbonusoffers-mail-13',
            'deposit-newbonusoffers-mail-14',
            'deposit-newbonusoffers-mail-15',
            'monthly-week1',
            'block-monthly-week1'
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('config_name', $this->emails)
            ->where('config_tag', '=', 'countries')
            ->update([
                'config_type' => '{"type":"ISO2", "delimiter":" "}'
            ]);

        // updating specific config
        $this->connection
            ->table($this->table)
            ->where('config_name', '=', 'AML51-frequency')
            ->update([
                'config_type' => '{"type":"text"}'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('config_name', $this->emails)
            ->where('config_tag', '=', 'countries')
            ->update([
                'config_type' => '{"type":"number"}'
            ]);

        // reverting specific config
        $this->connection
            ->table($this->table)
            ->where('config_name', '=', 'AML51-frequency')
            ->update([
                'config_type' => '{"type":"number"}'
            ]);
    }
}
