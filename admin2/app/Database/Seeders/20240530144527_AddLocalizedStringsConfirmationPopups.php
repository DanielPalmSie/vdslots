<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsConfirmationPopups extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'responsible.confirm.html',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><p>Are you sure you want to perform this action?</p></div>',
        ],
        [
            'language' => 'da',
            'alias' => 'responsible.confirm.html',
            'value' => '<div><img src="/diamondbet/images/megariches/warning.png"><p>Er du sikker p&aring;, at du &oslash;nsker, at udf&oslash;re denne handling?</p></div>',
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
        if ($this->brand === 'megariches') {

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => $this->data[0]['value']]);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => $this->data[1]['value']]);
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => '<p>Are you sure you want to perform this action?</p>']);

            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => '<p>Er du sikker p&aring;, at du &oslash;nsker, at udf&oslash;re denne handling?</p>']);
        }
    }
}
