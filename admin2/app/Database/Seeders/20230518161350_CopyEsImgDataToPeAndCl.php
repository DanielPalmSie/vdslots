<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\LocalizedStrings;

class CopyEsImgDataToPeAndCl extends Seeder
{
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    // ./console seed:up 20230518161350
    public function up()
    {
        $img_data = $this->connection->select("SELECT * FROM image_data WHERE lang LIKE 'es'");
        foreach($img_data as $insert){
            $insert = (array)$insert;
            unset($insert['id']);
            foreach(['cl', 'pe'] as $to_lang){
                $insert['lang'] = $to_lang;
                echo "Copying {$insert['filename']} from es to $to_lang\n";
                $this->connection->table('image_data')->insertOrIgnore($insert);
            }
        }
    }
}
