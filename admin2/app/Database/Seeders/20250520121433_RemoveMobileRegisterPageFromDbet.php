<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveMobileRegisterPageFromDbet extends Seeder
{
    protected Connection $connection;
    private string $brand;
    private array $aliases;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->aliases = [
            'register',
            'login',
        ];
    }

    public function up()
    {
        if ($this->brand === 'dbet') {
            foreach ($this->aliases as $alias) {
                $this->connection
                    ->table('pages')
                    ->where('alias', $alias)
                    ->delete();
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet') {
            $this->connection->table('pages')
                ->insert([
                    [
                        'page_id' => 269,
                        'parent_id' => 268,
                        'alias' => 'register',
                        'filename' => 'diamondbet/mobile.php',
                        'cached_path' => '/mobile/register',
                    ],
                    [
                        'page_id' => 766,
                        'parent_id' => 268,
                        'alias' => 'login',
                        'filename' => 'diamondbet/mobile.php',
                        'cached_path' => '/mobile/login',
                    ],
                ]);
        }
    }
}
