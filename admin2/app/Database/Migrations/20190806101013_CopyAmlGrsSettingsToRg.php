<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CopyAmlGrsSettingsToRg extends Migration
{
    protected $table;

    protected $schema;

    private $items = [
        'countries',
        'deposit_vs_wager',
        'ngr_last_12_months',
        'deposited_last_12_months',
        'wagered_last_12_months'
    ];

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }

    /**
     * @throws Exception
     */
    public function up()
    {
        return DB::loopNodes(function ($connection) {
            $data = $connection->table($this->table)
                ->where('section', '=', 'AML')
                ->where(function ($q) {
                    return $q->whereIn('name', $this->items)
                        ->orWhereIn('category', $this->items);
                })
                ->get()
                ->map(function ($el) {
                    $el = (array)$el;
                    $el['section'] = 'RG';
                    return $el;
                })
                ->toArray();

            $connection
                ->table($this->table)
                ->where('section', '=', 'RG')
                ->where(function ($q) {
                    return $q->where('name', '=', 'ngr_loss')
                        ->orWhere('category', '=', 'ngr_loss');
                })
                ->delete();

            return DB::bulkInsert($this->table, null, $data, $connection);
        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('section', '=', 'RG')
                ->where(function ($q) {
                    return $q->whereIn('name', $this->items)
                        ->orWhereIn('category', $this->items);
                })
                ->delete();

            $ngr_loss = "ngr_loss";

            $data = collect([
                ["€0 - €9,999", "0,9999", 0],
                ["€10,000 - €19,999", "10000,19999", 7],
                ["€20,000+", "20000", 10]
            ])->map(function ($el) use ($ngr_loss) {
                list($title, $name, $score) = $el;
                return [
                    "name" => $name,
                    "title" => $title,
                    "score" => $score,
                    "category" => $ngr_loss,
                    "type" => null,
                    "section" => "RG",
                    "data" => ""
                ];
            })->push([
                "name" => $ngr_loss,
                "title" => "NGR Loss",
                "score" => 0,
                "category" => null,
                "type" => "option",
                "section" => "RG",
                "data" => ""
            ])->toArray();

            return DB::bulkInsert($this->table, null, $data, $connection);
        }, true);
    }
}
