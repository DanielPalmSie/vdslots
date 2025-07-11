<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateTriggersDescription extends Seeder
{

    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        $update = [
            [
                'name' => 'AML17',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => 'AML Risk Profile Rating with High Risk',
                ],
            ],
            [
                'name' => 'AML19',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => 'AML Risk Profile Rating with High Risk',
                ],
            ],
            [
                'name' => 'AML23',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => 'AML Risk Profile Rating with High Risk',
                ],
            ],
            [
                'name' => 'AML43',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => 'AML Risk Profile Rating of people with a Deposited amount last 12 months score of High Risk',
                ],
            ],
            [
                'name' => 'AML50',
                'data' => [
                    'indicator_name' => 'AML High Risk',
                    'description' => 'It will flag everytime a customer with a score lower than High Risk goes up to a High Risk. Will not flag customer with High Risk',
                ],
            ],
            [
                'name' => 'RG27',
                'data' => [
                    'indicator_name' => 'Deposit from a player with High Risk',
                    'description' => 'Only flag customers with RG Risk Profile rating between x - y in score.',
                ],
            ],
            [
                'name' => 'RG37',
                'data' => [
                    'indicator_name' => 'High RG GRS Rating',
                    'description' => 'Player\'s GRS Score has increased to High Risk',
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
                'name' => 'AML17',
                'data' => [
                    'indicator_name' => 'AML risk of 8',
                    'description' => 'AML Risk Profile Rating with Global score of 8',
                ],
            ],
            [
                'name' => 'AML19',
                'data' => [
                    'indicator_name' => 'AML risk of 9',
                    'description' => 'AML Risk Profile Rating with Global score of 9',
                ],
            ],
            [
                'name' => 'AML23',
                'data' => [
                    'indicator_name' => 'AML risk of 10',
                    'description' => 'AML Risk Profile Rating with Global score of 10',
                ],
            ],
            [
                'name' => 'AML43',
                'data' => [
                    'indicator_name' => 'AML deposit risk of 10',
                    'description' => 'AML Risk Profile Rating of people with a Deposited amount last 12 months score of 10',
                ],
            ],
            [
                'name' => 'AML50',
                'data' => [
                    'indicator_name' => 'AML Global Risk score 8',
                    'description' => 'It will flag everytime a customer with a score lower than 8 goes up to a score of 8 or higher. Will not flag customer above 8',
                ],
            ],
            [
                'name' => 'RG27',
                'data' => [
                    'indicator_name' => 'Deposit from a player with high',
                    'description' => 'Only flag customers with RG Risk Profile rating between x - y in score.',
                ],
            ],
            [
                'name' => 'RG37',
                'data' => [
                    'indicator_name' => 'High RG GRS Rating',
                    'description' => 'Player\'s GRS Score has increased to 80+',
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