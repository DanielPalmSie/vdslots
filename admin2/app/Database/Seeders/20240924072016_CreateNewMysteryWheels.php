<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Illuminate\Database\Schema\Blueprint;

class CreateNewMysteryWheels extends Seeder
{
    private Connection $connection;
    private string $tableJackpotWheelLog;
    private string $tableJackpotWheelSlices;
    private string $tableJackpotWheel;
    private array $jackpotWheelData;
    private array $jackpotSlicesData;
    private array $wheelIds;
    private $brand;
    protected $schema;

    public function init()
    {
        $this->tableJackpotWheelLog = 'jackpot_wheel_log';
        $this->tableJackpotWheelSlices = 'jackpot_wheel_slices';
        $this->tableJackpotWheel = 'jackpot_wheels';

        // Get master connection
        $this->connection = DB::getMasterConnection();
        $this->schema = $this->get('schema');
        $this->brand = phive('BrandedConfig')->getBrand();

        // Seed data for wheels
        $this->jackpotWheelData  = [
            [
                'id' => 101,
                'name' => 'The Mystery - Cash Wheel',
                'number_of_slices' => 16,
                'cost_per_spin' => 1,
                'active' => 0,
                'deleted' => 0,
                'style' => 'mysterycashwheel',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 102,
                'name' => 'The Mystery - Free Spins Wheel',
                'number_of_slices' => 16,
                'cost_per_spin' => 1,
                'active' => 0,
                'deleted' => 0,
                'style' => 'mysteryfreespinswheel',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->wheelIds = [101, 102];
    }

    public function up()
    {
        $this->init();

        if ($this->brand !== phive('BrandedConfig')::BRAND_MRVEGAS) {
            return;
        }


        foreach ($this->jackpotWheelData as $data) {
            // Check if the wheel data already exists in the database
            $isJackpotDataExists = $this->connection
                ->table($this->tableJackpotWheel)
                ->where('id', '=', $data['id'])
                ->where('name', '=', $data['name'])
                ->where('style', '=', $data['style'])
                ->exists();

            // If the data does not exist, insert it
            if (!$isJackpotDataExists) {
                $this->connection->table($this->tableJackpotWheel)->insert([
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'number_of_slices' => $data['number_of_slices'],
                    'cost_per_spin' => $data['cost_per_spin'],
                    'active' => $data['active'],
                    'deleted' => $data['deleted'],
                    'style' => $data['style'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at']
                ]);
            }
        }


        foreach ($this->wheelIds as $wheelId) {
        $wheelSlices = [];
        $sortOrder = range(1, 16);
        shuffle($sortOrder);

        for ($i = 0; $i < 16; $i++) {
            $wheelSlices[] = [
                'wheel_id' => $wheelId,
                'sort_order' => $sortOrder[$i],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        // Insert the slices into the wheel_slices table
        $this->connection->table($this->tableJackpotWheelSlices)->insert($wheelSlices);
    }
    }

    public function down()
    {
        $this->init();

        if ($this->brand !== phive('BrandedConfig')::BRAND_MRVEGAS) {
            return;
        }

         // Remove all slices associated with the wheel IDs from jackpot_wheel_slices
        $this->connection->table($this->tableJackpotWheelSlices)
            ->whereIn('wheel_id', $this->wheelIds)
            ->delete();

        // Remove the inserted wheels from jackpot_wheels
        $this->connection->table($this->tableJackpotWheel)
            ->whereIn('id', $this->wheelIds)
            ->delete();
    }

}
