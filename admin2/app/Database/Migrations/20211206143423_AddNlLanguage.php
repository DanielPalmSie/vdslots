<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddNlLanguage extends Migration
{
    private string $table = 'languages';
    private array $data = [
        'language' => 'nl',
        'light' => 0,
        'selectable' => 0,
    ];

    public function up()
    {
        $exists =  DB::getMasterConnection()
            ->table($this->table)
            ->where('language', $this->data['language'])
            ->first();

        if (!$exists) {
            DB::getMasterConnection()
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->where('language', $this->data['language'])
            ->delete();
    }
}
