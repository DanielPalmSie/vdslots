<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddPageAndBoxNeededForRgNetDepositInfoPopUp extends Migration
{
    private Connection $connection;

    private string $pages_table = 'pages';
    private string $boxes_table = 'boxes';

    private stdClass $mobile_page;

    private array $rg_net_deposit_info_page;
    private array $box_data;

    /**
     *
     */
    function init()
    {
        $this->rg_net_deposit_info_page = [
            'alias' => 'rg-net-deposit-info',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/rg-net-deposit-info'
        ];
        $this->box_data = [
            'container' => 'full',
            'box_class' => 'MobileGeneralBox',
            'priority' => 0
        ];
        $this->connection = DB::getMasterConnection();
        $this->mobile_page = $this->getMobilePage();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (empty($this->mobile_page)) {
            return;
        }

        $rg_net_deposit_info_page = $this->getRgActivityPage();

        if (empty($rg_net_deposit_info_page)) {
            $rg_net_deposit_info_page = $this->createRgActivityPage();
        }


        if (!$this->isMobileGeneralBoxAddedToPage($rg_net_deposit_info_page)) {
            $this->addMobileGeneralBoxToPage($rg_net_deposit_info_page);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $rg_net_deposit_info_page = $this->getRgActivityPage();
        if (empty($rg_net_deposit_info_page)) {
            return;
        }

        $this->connection->table($this->boxes_table)
            ->where('box_class', '=', $this->box_data['box_class'])
            ->where('page_id', '=', $rg_net_deposit_info_page->page_id)
            ->delete();

        $this->connection->table($this->pages_table)
            ->where('page_id', '=', $rg_net_deposit_info_page->page_id)
            ->delete();
    }

    /**
     * Insert record for Rg Activity page to pages table
     */
    private function createRgActivityPage()
    {
        $page = $this->rg_net_deposit_info_page;
        $page['parent_id'] = $this->mobile_page->page_id;

        $this->connection->table($this->pages_table)
            ->insert($page);

        return $this->getRgActivityPage();
    }

    /**
     * Get record for rg activity page from db
     *
     * @return mixed
     */
    private function getRgActivityPage()
    {
        return $this->connection->table($this->pages_table)
            ->where('alias', '=', 'rg-net-deposit-info')
            ->where('cached_path', '=', '/mobile/rg-net-deposit-info')
            ->where('parent_id', '=', $this->mobile_page->page_id)
            ->first();
    }

    /**
     * Get parent page (Mobile)
     *
     * @return mixed
     */
    private function getMobilePage()
    {
        return $this->connection
            ->table($this->pages_table)
            ->where('alias', '=', 'mobile')
            ->where('cached_path', '=', '/mobile')
            ->first();
    }

    /**
     * Add a box entry to table with new page created.
     */
    private function addMobileGeneralBoxToPage($rg_net_deposit_info_page)
    {
        $box_data = $this->box_data;
        $box_data['page_id'] = $rg_net_deposit_info_page->page_id;

        $this->connection
            ->table($this->boxes_table)
            ->insert($box_data);
    }

    /**
     * @param $rg_net_deposit_info_page
     * @return bool
     */
    private function isMobileGeneralBoxAddedToPage($rg_net_deposit_info_page): bool
    {
        $box_record = $this->connection->table($this->boxes_table)
            ->where('box_class', '=', $this->box_data['box_class'])
            ->where('page_id', '=', $rg_net_deposit_info_page->page_id)
            ->first();

        return !empty($box_record);
    }
}


