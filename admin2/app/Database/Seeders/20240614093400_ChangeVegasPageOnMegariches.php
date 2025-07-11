<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class ChangeVegasPageOnMegariches extends Seeder
{
    private string $table = 'pages';
    private array $current_aliases = ['the-wheel-of-vegas-info', 'the-wheel-of-jackpots-info'];
    private string $new_alias = 'the-wheel-of-riches-info';
    private string $brand;
    private Connection $connection;

    public function init()
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            foreach ($this->current_aliases as $current_alias) {
                $this->connection->table($this->table)
                    ->where('alias', $current_alias)
                    ->where('cached_path', '/mobile/'.$current_alias)
                    ->update(
                        [
                            'alias' => $this->new_alias,
                            'cached_path' => '/mobile/'.$this->new_alias,
                        ]
                    );
                $this->connection->table($this->table)
                    ->where('alias', $current_alias)
                    ->where('cached_path', '/'.$current_alias)
                    ->update(
                        [
                            'alias' => $this->new_alias,
                            'cached_path' => '/'.$this->new_alias,
                        ]
                    );
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            foreach ($this->current_aliases as $current_alias) {
                $this->connection->table($this->table)
                    ->where('alias', $this->new_alias)
                    ->where('cached_path', '/mobile/'.$this->new_alias)
                    ->update(
                        [
                            'alias' => $current_alias,
                            'cached_path' => '/mobile/'.$current_alias,
                        ]
                    );

                $this->connection->table($this->table)
                    ->where('alias', $this->new_alias)
                    ->where('cached_path', '/'.$this->new_alias)
                    ->update(
                        [
                            'alias' => $current_alias,
                            'cached_path' => '/'.$current_alias,
                        ]
                    );
            }
        }
    }
}
