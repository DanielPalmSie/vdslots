<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringsForKungaslottetRgLockCategoryPopups extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $aliases = ['rg.lock-category-popup.html', 'game-category.locked.info', 'game-category-block-indefinite.blocked.info'];

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'rg.lock-category-popup.html',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/responsible-gaming/lock-game-category.png"><h6 class="popup-v2-subtitle">Lock Game Category</h6><div>Are you sure you want to perform this action?</div></div>',
        ],
        [
            'language' => 'en',
            'alias' => 'game-category.locked.info',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/responsible-gaming/locked-game-category.png"><h6 class="popup-v2-subtitle">Locked Game Category</h6><div>You locked this game category for 24 hours.</div></div>',
        ],
        [
            'language' => 'en',
            'alias' => 'game-category-block-indefinite.blocked.info',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/kungaslottet/responsible-gaming/locked-game-category.png"><h6 class="popup-v2-subtitle">Locked Game Category</h6><div>You locked this game category indefinite.</div></div>',
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
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias', $this->aliases)
                ->where('language', 'en')
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
                ->whereIn('alias', $this->aliases)
                ->where('language', 'en')
                ->delete();
        }
    }
}
