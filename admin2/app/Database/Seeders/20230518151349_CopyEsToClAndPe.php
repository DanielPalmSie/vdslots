<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\LocalizedStrings;

class CopyEsToClAndPe extends Seeder
{
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    // ./console seed:up 20230518151349
    public function up()
    {
        $strings = $this->connection->select("SELECT * FROM localized_strings WHERE language LIKE 'es'");
        foreach($strings as $insert){
            $insert = (array)$insert;
            foreach(['cl', 'pe'] as $to_lang){
                $insert['language'] = $to_lang;
                echo "Moving {$insert['alias']} from es to $to_lang\n";
                LocalizedStrings::updateOrCreate(['language' => $to_lang, 'alias' => $insert['alias']], $insert);
            }
        }
    }
}
