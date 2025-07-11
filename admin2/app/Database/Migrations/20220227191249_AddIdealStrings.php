<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddIdealStrings extends Migration
{
    protected $table;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    private $template = [
        'alias' => 'deposit.start.{{supplier}}.html',
        'language' => 'en',
        'value' => '<p>Deposit with {{supplierDisplayName}}, your funds are immediately available. Withdrawals are processed within 5 minutes around the clock.</p>'
    ];

    private $suppliers = [
        ['supplier' => 'ideal', 'supplierDisplayName' => 'iDEAL'],
        ['supplier' => 'abn-amro', 'supplierDisplayName' => 'ABN Amro'],
        ['supplier' => 'asn-bank', 'supplierDisplayName' => 'ASN Bank'],
        ['supplier' => 'bunq', 'supplierDisplayName' => 'Bunq'],
        ['supplier' => 'ing', 'supplierDisplayName' => 'ING'],
        ['supplier' => 'knab', 'supplierDisplayName' => 'Knab'],
        ['supplier' => 'moneyou', 'supplierDisplayName' => 'Moneyou'],
        ['supplier' => 'rabobank', 'supplierDisplayName' => 'Rabobank'],
        ['supplier' => 'regiobank', 'supplierDisplayName' => 'RegioBank'],
        ['supplier' => 'revolut', 'supplierDisplayName' => 'Revolut'],
        ['supplier' => 'sns', 'supplierDisplayName' => 'SNS'],
        ['supplier' => 'triodosbank', 'supplierDisplayName' => 'Triodos Bank'],
        ['supplier' => 'vanlanschot', 'supplierDisplayName' => 'Van Lanschot'],
    ];

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->suppliers as $supplier) {
            $alias = str_replace('{{supplier}}', $supplier['supplier'], $this->template['alias']);
            $value = str_replace('{{supplierDisplayName}}', $supplier['supplierDisplayName'], $this->template['value']);
            $language = $this->template['language'];

            $old_string = $this->connection
                ->table($this->table)
                ->where('alias', '=', $alias)
                ->where('language', '=', $language)
                ->first();

            if (empty($old_string)) {
                $this->connection->table($this->table)->insert([
                    'alias' => $alias,
                    'language' => $language,
                    'value' => $value
                ]);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->suppliers as $supplier) {
            $alias = str_replace('{{supplier}}', $supplier['supplier'], $this->template['alias']);
            $language = $this->template['language'];

            $this->connection
                ->table($this->table)
                ->where('alias', '=', $alias)
                ->where('language', '=', $language)
                ->delete();
        }
    }
}
