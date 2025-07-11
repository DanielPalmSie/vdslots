<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use Illuminate\Support\Collection;

class UpdateLoginBasedPageVisibilityStatus extends Seeder
{
    private Connection $connection;

    private $menusTable;

    public function init()
    {
        $this->connection = DB::connection();
        $this->menusTable = 'menus';
    }

    public function up()
    {
        /* In current prod when logged_in & logged_out are 0, it's visible */
        $visibleMenuIds = $this->getMenusIds(0);

        /* In current prod when logged_in & logged_out are 1, it's hidden */
        $hiddenMenuIds  = $this->getMenusIds(1);

        // changing from 0 -> 1
        $this->updateMenuStatus($visibleMenuIds, 1);

        // changing from 1 -> 0
        $this->updateMenuStatus($hiddenMenuIds, 0);

    }

    public function down()
    {
        /* After up current prod when logged_in & logged_out are 1, it's visible */
        $visibleMenuIds = $this->getMenusIds(1);

        /* After up current prod when logged_in & logged_out are 0, it's hidden */
        $hiddenMenuIds  = $this->getMenusIds(0);

        // changing from 1 -> 0
        $this->updateMenuStatus($visibleMenuIds, 0);

        // changing from 0 -> 1
        $this->updateMenuStatus($hiddenMenuIds, 1);
    }

    /**
     * @return Collection
     */
    public function getMenusIds($status): Collection
    {
        return $this->connection
            ->table($this->menusTable)
            ->where('logged_in', $status)
            ->where('logged_out', $status)
            ->get()->pluck('menu_id');
    }

    /**
     * @return void
     */
    public function updateMenuStatus($menuIds, $status): void
    {
        $this->connection
            ->table($this->menusTable)
            ->whereIn('menu_id', $menuIds)
            ->update([
                'logged_in' => $status,
                'logged_out' => $status
            ]);
    }
}
