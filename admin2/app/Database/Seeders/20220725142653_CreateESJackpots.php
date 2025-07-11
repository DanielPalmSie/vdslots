<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;


class CreateESJackpots extends Seeder
{

    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'jackpots';
        $this->connection = DB::getMasterConnection();
        loadPhive();
    }

    public function up()
    {
        $jps = $this->connection
            ->table($this->table)
            ->select()
            ->get()
            ->all();

        $this->connection
            ->table($this->table)
            ->update(['excluded_countries' => 'ES']);

        foreach ($jps as $jp) {
            unset($jp->id);
            $jp->contribution_next_jp = 0;
            $jp->amount_minimum = 0;
            $jp->amount = 0;
            $jp->name = str_replace('Jackpots', 'Botes', $jp->name);
            $jp->included_countries = 'ES';
            $jp->excluded_countries = '';
            $this->connection->table($this->table)->insert((array)$jp);

        }
        $this->clearJpCache();
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where(['included_countries' => 'ES'])
            ->delete();
        $this->connection
            ->table($this->table)
            ->where(['excluded_countries' => 'ES'])
            ->update(['excluded_countries' => '']);
        $this->clearJpCache();
    }

    private function clearJpCache()
    {
        $wheel = phive('DBUserHandler/JpWheel');
        phive()->miscCache('jp-values', [], true);
        $wheel->updateJpValues();

    }
}