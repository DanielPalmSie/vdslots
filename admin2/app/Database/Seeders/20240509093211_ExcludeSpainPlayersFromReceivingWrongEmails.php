<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ExcludeSpainPlayersFromReceivingWrongEmails extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    private string $config_tag;
    private array $mail_triggers;

    public function init()
    {
        $this->table = 'config';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->config_tag = 'exclude-countries';
        $this->mail_triggers = ['welcome.mrvegas', 'no-deposit-weekly'];
    }

    public function up()
    {
        if ($this->brand === 'videoslots') {
            foreach ($this->mail_triggers as $mail_trigger) {
                $config = $this->connection
                    ->table($this->table)
                    ->where('config_name', $mail_trigger)
                    ->where('config_tag', $this->config_tag)
                    ->first();
                if (!empty($config)) {
                    $this->connection
                        ->table($this->table)
                        ->where('config_name', $mail_trigger)
                        ->where('config_tag', $this->config_tag)
                        ->update(
                            [
                                'config_value' => $config->config_value . ' ES'
                            ]
                        );
                } else {
                    $this->connection
                        ->table($this->table)
                        ->insert(
                            [
                                'config_name' => $mail_trigger,
                                'config_tag' => $this->config_tag,
                                'config_value' => 'ES',
                                'config_type' => '{"type":"ISO2", "delimiter":" "}'
                            ]
                        );
                }
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'videoslots') {
            foreach ($this->mail_triggers as $mail_trigger) {
                $config = $this->connection
                    ->table($this->table)
                    ->where('config_name', $mail_trigger)
                    ->where('config_tag', $this->config_tag)
                    ->first();
                $updated_value = array_diff(explode(' ', $config->config_value), ['ES']);

                $this->connection
                    ->table($this->table)
                    ->where('config_name', $mail_trigger)
                    ->where('config_tag', $this->config_tag)
                    ->update(
                        [
                            'config_value' => implode(' ', $updated_value)
                        ]
                    );
            }
        }
    }
}
