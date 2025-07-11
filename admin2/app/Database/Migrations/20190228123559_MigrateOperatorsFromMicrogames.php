<?php

use App\Extensions\Database\FManager as DB;
use App\Models\Game;
use App\Models\Operator;
use Phpmig\Migration\Migration;

class MigrateOperatorsFromMicrogames extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'operators';
        $this->schema = $this->get('schema');
    }


    /**
     * @throws Exception
     */
    public function up()
    {
        $current_operators = Operator::query()->select(['name', 'network'])->get();
        $mg_operators = Game::query()
            ->groupBy('operator', 'network')
            ->get()
            ->map(function ($mg) {
                $branded = !empty($mg['branded']);
                $jackpot = strpos("jackpot", $mg['tag']) !== false;
                $operator = [
                    "name" => $mg['operator'],
                    "network" => $mg['network'],
                    "branded_op_fee" => $branded ? $mg['op_fee'] : 0,
                    "non_branded_op_fee" => !$branded ? $mg['op_fee'] : 0,
                    "blocked_countries" => "",
                    "blocked_countries_jackpot" => "",
                    "blocked_countries_non_branded" => ""
                ];

                if ($jackpot) {
                    $operator['blocked_countries_jackpot'] = $mg['blocked_countries'];
                } elseif ($branded) {
                    $operator['blocked_countries'] = $mg['blocked_countries'];
                } else {
                    $operator['blocked_countries_non_branded'] = $mg['blocked_countries'];
                }

                return $operator;
            })
            ->filter(function ($operator) use ($current_operators) {
                if (empty($operator['name'])) {
                    return false;
                }

                $c = $current_operators
                    ->where('name', $operator['name'])
                    ->where('network', $operator['network'])
                    ->first();

                return empty($c);
            })
            ->toArray();

        DB::bulkInsert('operators', null, $mg_operators);
    }

    /**
     * @throws Exception
     */
    public function down()
    {

    }
}
