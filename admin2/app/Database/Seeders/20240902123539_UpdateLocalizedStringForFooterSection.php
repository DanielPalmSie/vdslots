<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForFooterSection extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'language' => 'sv',
            'alias' => 'app.footer.license_logo.json',
            'old_value' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/sv/mobile/responsible-gaming/#spel-for-vuxna"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/Spelinspektionen_logotyp.svg",
            "link": "https://www.spelinspektionen.se/"
            }
            ]',
            'new_value' => '[
            {
            "image": "https://www.mrvegas.com/file_uploads/footer_18+.png",
            "link": "https://www.mrvegas.com/sv/mobile/responsible-gaming/#spel-for-vuxna"
            },
            {
            "image": "https://www.mrvegas.com/file_uploads/spelinspektionen_logo.png",
            "link": "https://www.spelinspektionen.se/"
            }
            ]'
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
        if ($this->brand === 'mrvegas') {
                $this->replace('new');
        }
    }

    public function down()
    {
        if ($this->brand === 'mrvegas') {
                $this->replace('old');
        }
    }

    private function replace($replace_prefix)
    {
        foreach ($this->data as $item) {
            $this->connection
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->update(['value' => $item[$replace_prefix . '_value']]);
        }
    }
}
