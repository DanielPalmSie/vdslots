<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddChileanPeso extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'countries';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {

        $this->connection
             ->table($this->table)
             ->insert([
		 'country' => 'cl',
		 'name' => 'Chile',
		 'language' => 'cl',
		 'subdomain' => 'cl',
		 'langtag' => 'es-Cl',
		 'setlocale' => 'es_CL.utf8',
		 'currency' => 'CLP'			
             ]);

        $this->connection->statement("INSERT INTO `currencies` (`code`, `multiplier`, `symbol`, `countries`, `mod`, `legacy`) VALUES ('CLP', '911', '\$CL', 'cl', '1000', '0')");
        
    }

    public function down()
    {
        $this->connection->table($this->table)
             ->where('country', '=', 'cl')
             ->delete();

        $this->connection->table('currencies')
             ->where('code', '=', 'CLP')
             ->delete();
    }
}
