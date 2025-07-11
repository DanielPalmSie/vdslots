<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringsForLockGameCategoryPopUp extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'game-category.locked.info',
            'value' => '<h3>Locked Game Category</h3><p>You locked this game category for 24 hours.</p>',
        ],
        [
            'language' => 'sv',
            'alias' => 'game-category.locked.info',
            'value' => '<h3>Kategori för låst spel</h3><p>Du låste den här spelkategorin i 24 timmar.</p>',
        ],
        [
            'language' => 'en',
            'alias' => 'game-category.locked.info',
            'value' => '<h3 class="popup-v2-subtitle">Locked Game Category</h3><div>You locked this game category for 24 hours.</div>',
        ],
        [
            'language' => 'sv',
            'alias' => 'game-category.locked.info',
            'value' => '<h3 class="popup-v2-subtitle">Låst spelkategori</h3><div>Du har låst denna spelkategori i 24 timmar.</div>',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'dbet') {
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
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[2]['alias'])
                ->where('language',$this->data[2]['language'])
                ->update(['value' => $this->data[2]['value']]);
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[3]['alias'])
                ->where('language',$this->data[3]['language'])
                ->update(['value' => $this->data[3]['value']]);
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[0]['alias'])
                ->where('language',$this->data[0]['language'])
                ->update(['value' => 'You locked this game category for 24 hours.']);
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[1]['alias'])
                ->where('language',$this->data[1]['language'])
                ->update(['value' => 'Du låste den här spelkategorin i 24 timmar.']);
        }
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[2]['alias'])
                ->where('language',$this->data[2]['language'])
                ->update(['value' => 'You locked this game category for 24 hours.']);
            $this->connection
                ->table($this->table)
                ->where('alias',$this->data[3]['alias'])
                ->where('language',$this->data[3]['language'])
                ->update(['value' => 'Du har låst denna spelkategori i 24 timmar.']);
        }
    }
}
