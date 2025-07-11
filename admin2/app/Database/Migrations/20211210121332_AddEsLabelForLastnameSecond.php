<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddEsLabelForLastnameSecond extends Migration
{

    private string $table = 'localized_strings';

    private array $items = [
        [
            'alias' => 'register.lastname_second.label',
            'language' => 'en',
            'value' => 'Second Surname is mandatory for DNI customers only.'
        ]
    ];


    /**
     * Do the migration
     */

    public function up(){
        foreach ($this->items as $item){
            $exists = DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->first();

            if(!empty($exists)){
                continue;
            }

            DB::getMasterConnection()
                ->table($this->table)
                ->insert([$item]);
        }
    }

    /**
     * Undo the migration
     */

    public function down(){
        foreach ($this->items as $item){
            DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->delete();
        }
    }

}

