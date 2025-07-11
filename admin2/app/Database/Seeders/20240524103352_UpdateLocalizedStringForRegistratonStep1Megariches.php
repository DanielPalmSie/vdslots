<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForRegistratonStep1Megariches extends Seeder {

    protected array $data = [
        'en' => [
            'register.email.nostar' => 'Email'
        ]
    ];

    private Connection $connection;
    private string $brand;
    private string $table;
    private string $alias;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
        $this->alias = 'register.email.nostar';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', $this->alias)
            ->where('language', 'en')
            ->update([
                'value' => 'Email',
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
                ->where('alias', $this->alias)
                ->where('language', 'en')
                ->update([
                    'value' => 'Your Email',
                ]);
    }
}