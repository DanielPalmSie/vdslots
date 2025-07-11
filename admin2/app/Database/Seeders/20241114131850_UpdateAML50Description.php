<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateAML50Description extends Seeder
{

    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        $update = [
            [
                'name' => 'AML50',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => "Customer with a score lower than High Risk goes up to a High Risk." .
                        "Will not flag customer with High Risk.",
                ],
            ],
        ];
        DB::loopNodes(function ($connection) use ($update) {
            foreach ($update as $item) {
                $connection->table($this->table)->where('name', $item['name'])->update($item['data']);
            }
        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        $update = [
            [
                'name' => 'AML50',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => "It will flag everytime a customer with a score lower than High Risk goes up" .
                        "to a High Risk. Will not flag customer with High Ris",
                ],
            ],
        ];
        DB::loopNodes(function ($connection) use ($update) {
            foreach ($update as $item) {
                $connection->table($this->table)->where('name', $item['name'])->update($item['data']);
            }
        }, true);
    }
}
