<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class TranslateInvalidCountry extends Migration
{
    private string $table = 'localized_strings';
    private array $data = [
        [
            'alias'    => 'register.err.country',
            'language' => 'en',
            'value'    => 'Invalid country selected.'
        ],
    ];

    public function up()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->whereIn('alias', array_column($this->data, 'alias'))
            ->where('language', '=', 'en')
            ->delete();
    }
}
