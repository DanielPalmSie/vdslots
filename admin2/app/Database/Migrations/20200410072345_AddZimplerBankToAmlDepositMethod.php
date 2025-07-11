<?php


use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddZimplerBankToAmlDepositMethod extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        /**
         * @param \Illuminate\Support\Collection $data
         * @return mixed
         */
        $bulkInsertInMasterAndShards = function ($data) {
            DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
            DB::bulkInsert($this->table, null, $data->toArray());
            return $data;
        };

        \App\Classes\PaymentsHelper::getOptionsCollection()
            ->filter(function($el, $key) {
                return $key === 'zimplerbank';
            })
            ->map(function ($el, $key) {
                return [
                    "name" => $key,
                    "title" => $el['title'],
                    "category" => 'deposit_method',
                    "score" => $el['score'],
                    "section" => "AML"
                ];
            })
            ->values()
            ->tap($bulkInsertInMasterAndShards);

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)->where('name', '=', 'zimplerbank')->delete();
        }, true);
    }
}
