<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForPartnershipLogos extends Seeder
{
    private Connection $connection;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'partnership1.html',
            'value' => ''
        ],
        [
            'language' => 'en',
            'alias' => 'partnership2.html',
            'value' => '',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
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

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias',$this->data[0]['alias'])
            ->where('language',$this->data[0]['language'])
            ->update(['value' => '']);

        $this->connection
            ->table($this->table)
            ->where('alias',$this->data[1]['alias'])
            ->where('language',$this->data[1]['language'])
            ->update(['value' => '']);
    }
}