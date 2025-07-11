<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddConfigToAllowProcessingWithdrawalCancellationAfterXMinutes extends Seeder
{
    private string $table = 'config';

    private array $config = [
        "config_name" => 'allow-processing-withdrawal-cancellation-after-x-minutes',
        "config_tag" => 'pending-withdrawals',
        "config_value" => 720,
        "config_type" => '{"type":"number"}',
    ];

    public function up()
    {
        parent::up();

        DB::loopNodes(function (Connection $shardConnection) {
            $config = $shardConnection
                ->table($this->table)
                ->where('config_name', $this->config['config_name'])
                ->where('config_tag', $this->config['config_tag'])
                ->first();

            if (!$config) {
                $shardConnection
                    ->table($this->table)
                    ->insert($this->config);
            }
        }, true);
    }

    public function down()
    {
        parent::down();

        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection
                ->table($this->table)
                ->where('config_name', $this->config['config_name'])
                ->where('config_tag', $this->config['config_tag'])
                ->delete();
        }, true);
    }
}
