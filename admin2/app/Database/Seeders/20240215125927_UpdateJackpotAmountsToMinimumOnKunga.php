<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;


class UpdateJackpotAmountsToMinimumOnKunga extends Seeder
{

    protected string $tableJackpots;
    private Connection $connection;

    private array $jackpotsData;

    private $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->tableJackpots = 'jackpots';


        $this->jackpotsData = [

            [
                'id' => 1,
                'name' => 'Mega Jackpots',
                'amount' => 1816009.396031957000,
                'amount_minimum' => 25000.000000000000
            ],
            [
                'id' => 2,
                'name' => 'Major Jackpots',
                'amount' => 359903.298545698740,
                'amount_minimum' => 5000.000000000000
            ],
            [
                'id' => 3,
                'name' => 'Mini Jackpots',
                'amount' => 2.500000000000,
                'amount_minimum' => 250.000000000000
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | Update jackpots record
        |--------------------------------------------------------------------------
       */

        $this->init();

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        foreach ($this->jackpotsData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableJackpots)
                ->where('id', '=', $data['id'])
                ->where('name', '=', $data['name'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableJackpots)
                    ->where('id', $data['id'])
                    ->update(['amount' => $data['amount_minimum']]);
            }
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | Update to previous jackpots record
        |--------------------------------------------------------------------------
        */

        $this->init();

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        foreach ($this->jackpotsData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableJackpots)
                ->where('id', '=', $data['id'])
                ->where('name', '=', $data['name'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableJackpots)
                    ->where('id', $data['id'])
                    ->update(['amount' => $data['amount']]);
            }
        }

    }
}