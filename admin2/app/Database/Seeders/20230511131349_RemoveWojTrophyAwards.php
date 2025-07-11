<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\Trophy;

class RemoveWojTrophyAwards extends Seeder
{
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    // ./console seed:up 20230511131349
    public function up()
    {
        foreach(['award_id', 'award_id_alt'] as $award_id_column){
            $trophies = $this->connection->select("SELECT * FROM trophies WHERE $award_id_column IN(SELECT id FROM trophy_awards WHERE type = 'wheel-of-jackpots')");
            foreach($trophies as $trophy){
                echo "Setting $award_id_column to 0 from {$trophy->$award_id_column} in trophy {$trophy->id}\n";
                Trophy::shs()
                      ->where('id', $trophy->id)
                      ->update([$award_id_column => 0]);
            }
        }
    }
}
