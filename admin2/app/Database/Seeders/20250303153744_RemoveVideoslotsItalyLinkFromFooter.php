<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class RemoveVideoslotsItalyLinkFromFooter extends Seeder
{
    protected Connection $connection;
    protected string $brand;
    protected array $footer_row;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->footer_row = [
            'parent_id' => 89,
            'alias' => 'videoslots-italy',
            'name' => '#menu.bottom.videoslots-italy',
            'priority' => 421,
            'link' => 'https://www.videoslots.it',
            'link_page_id' => 0,
            'new_window' => 0,
            'check_permission' => 0,
            'logged_in' => 0,
            'logged_out' => 0,
        ];
    }

    public function up()
    {
        if ($this->brand === 'videoslots') {
            $this->connection
                ->table('menus')
                ->where('alias', $this->footer_row['alias'])
                ->delete();
        }
    }

    public function down()
    {
        if ($this->brand === 'videoslots') {
            $this->connection
                ->table('menus')
                ->insert($this->footer_row);
        }
    }
}
