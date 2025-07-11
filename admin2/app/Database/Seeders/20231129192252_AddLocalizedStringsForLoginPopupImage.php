<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForLoginPopupImage extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;
    protected array $data = [];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $translation = [
            'language' => 'en',
            'alias' => 'login.popup.top.html',
            'value' => '<div></div>'
        ];

        if ($this->brand === 'kungaslottet') {
            $translation['value'] = '<img src="/diamondbet/images/kungaslottet/login-king.png" class="login-popup__image">';
        }

        $this->data[] = $translation;

        $this->connection
            ->table($this->table)
            ->upsert($this->data, ['language', 'alias']);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias',
                [
                    'login.popup.top.html',
                ]
            )
            ->where('language', 'en')
            ->delete();
    }
}
