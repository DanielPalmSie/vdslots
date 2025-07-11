<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
class AddPrivacyDashboardLinkAlias extends Seeder
{
    private Connection $connection;

    private string $table = 'localized_strings';


    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'help.start.privacy.adjustment.headline' => 'Privacy Adjustment',
            'help.start.privacy.adjustment.descr'    => 'Click here to adjust your marketing preferences',
        ]
    ];

    protected array $brandData = [
        'megariches' => [
            'en' => [
                'site.info.html' => '<p><span class="brand_name">MegaRiches Ltd</span><br />The Space, Level 2 &amp; 3<br />Alfred Craig Street, Pieta,&nbsp;Malta</p>'
            ]
        ],
        'dbet' => [
            'en' => [
                'site.info.html' => '<p><span class="brand_name">DBET Ltd</span><br />The Space, Level 2 &amp; 3<br />Alfred Craig Street, Pieta,&nbsp;Malta</p>'
            ]
        ],
        'videoslots' => [
            'en' => [
                'site.info.html' => '<p><span class="brand_name">Videoslots Ltd</span><br />The Space, Level 2 &amp; 3<br />Alfred Craig Street, Pieta<br />PTA 1320, Malta</p>',
            ]
        ],
        'mrvegas' => [
            'en' => [
                'site.info.html' => '<p><span class="brand_name">Mr Vegas Ltd</span><br />The Space, Level 2 &amp; 3<br />Alfred Craig Street, Pieta,&nbsp;Malta</p>'
            ]
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        // update localized_string
        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection->table($this->table)->upsert([
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language,
                ], ['alias', 'language']);
            }
        }

        foreach ($this->brandData[$this->brand] as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection->table($this->table)->upsert([
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language,
                ], ['alias', 'language']);
            }
        }
    }
    public function down() {
        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection->table($this->table)->where('alias', $alias)->where('language', $language)->delete();
            }
        }

    }
}
