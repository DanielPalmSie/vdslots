<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddDgojGameManuallyClosedPopupTranslationKeys extends Seeder
{
    private $connection;

    private string $table = 'localized_strings';
    
    private array $data = [
        ['alias' => 'game.session.ended', 'language' => 'en', 'value' => 'Game Session Ended'],
        ['alias' => 'time.played', 'language' => 'en', 'value' => 'Time Played']
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach($this->data as $data) {
            $this->connection
                ->table($this->table)
                ->insert($data);
        }
    }

    public function down()
    {
        foreach($this->data as $data) {
            $this->connection
                ->table($this->table)
                ->where('alias', $data['alias'])
                ->delete();
        }
    }
}
