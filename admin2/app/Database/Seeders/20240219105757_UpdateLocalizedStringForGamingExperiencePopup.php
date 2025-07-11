<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForGamingExperiencePopup extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'rg.info.box.top.html',
            'value' => '<p>We hope you will have an exciting experience. Remember to keep track of your gambling.<br />
                          Take a look at our <a href="/sv/?global_redirect=rg">Responsible Gaming</a> section and test the different limits.</p>',
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {

            $this->connection
                ->table($this->table)
                ->where('alias','rg.info.box.top.html')
                ->delete();

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias','rg.info.box.top.html')
                ->where('language', 'en')
                ->delete();
        }
    }
}
