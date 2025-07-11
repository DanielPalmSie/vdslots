<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class ActiveBonusForfeitMessages extends Seeder
{

    private const MESSAGE_ALIAS = 'forfeit.deposit.blocked.error';
    private const POPUP_ALIAS   = 'forfeit.deposit.blocked.popup.error';
    private const FORFEIT_ERROR = 'forfeit.deposit.error';
    private const DEFAULT_VALUES = [
        self::MESSAGE_ALIAS => 'You need to forfeit your active bonuses before proceeding to deposit.',
        self::POPUP_ALIAS   => 'If you make a Deposit while there is an active bonus in place, your remaining bonus balance and and winnings accrued during this period will be forfeited.',
        self::FORFEIT_ERROR => 'Bonuses could not be forfeited'
    ];

    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach (self::DEFAULT_VALUES as $alias => $value) {
            $this->connection
                ->table($this->table)
                ->insert([
                    'alias'      => $alias,
                    'language'   => 'en',
                    'value'     => $value,
                ]);
        }
    }

    public function down()
    {
        foreach (self::DEFAULT_VALUES as $alias => $value) {
            $this->connection->table($this->table)->where('alias', '=', $alias)->delete();
        }
    }
}
