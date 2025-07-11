<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddBrLocale extends Seeder
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
		 'country' => 'br',
		 'name' => 'Brazil',
		 'language' => 'br',
		 'subdomain' => 'br',
		 'langtag' => 'pt-Br',
		 'setlocale' => 'pt_BR.utf8',
		 'currency' => 'BRL'			
             ]);

        $this->connection->table('languages')->where('language', '=', 'pt')->update(['language' => 'br']);
        $this->connection->table('localized_strings')->where('language', '=', 'pt')->update(['language' => 'br']);
        $this->connection->table('permission_tags')->where('tag', '=', 'translate.pt')->update(['tag' => 'translate.br']);
        $this->connection->table('permission_groups')->where('tag', '=', 'translate.pt')->update(['tag' => 'translate.br']);        
        DB::getMasterConnection()->statement("update users set preferred_lang = 'br' where preferred_lang = 'pt'");
        DB::loopNodes(function ($connection) {
            $connection->table('users')
                ->where('preferred_lang', '=', 'pt')->update(['preferred_lang' => 'br']);
        }, true);
        
    }

    public function down()
    {
        $this->connection->table($this->table)
             ->where('country', '=', 'br')
             ->delete();

        $this->connection->table('languages')->where('language', '=', 'br')->update(['language' => 'pt']);
        $this->connection->table('localized_strings')->where('language', '=', 'br')->update(['language' => 'pt']);
        $this->connection->table('permission_tags')->where('tag', '=', 'translate.br')->update(['tag' => 'translate.pt']);
        $this->connection->table('permission_groups')->where('tag', '=', 'translate.br')->update(['tag' => 'translate.pt']);        
        DB::getMasterConnection()->statement("update users set preferred_lang = 'pt' where preferred_lang = 'br'");
        DB::loopNodes(function ($connection) {
            $connection->table('users')
                ->where('preferred_lang', '=', 'br')->update(['preferred_lang' => 'pt']);
        }, true);
    }
}
