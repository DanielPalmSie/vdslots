<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddPageAndBoxNeededForRgInfoPopup extends Migration
{
    private Connection $connection;

    private string $pages_table = 'pages';
    private string $boxes_table = 'boxes';

    private stdClass $mobile_page;

    private array $rg_activity_page = [
        'alias' => 'rg-activity',
        'filename' => 'diamondbet/mobile.php',
        'cached_path' => '/mobile/rg-activity'
    ];

    private array $box_data = [
        'container' => 'full',
        'box_class' => 'MobileGeneralBox',
        'priority' => 0
    ];

    /**
     *
     */
    function init()
    {
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

        $rg_activity_page = $this->getRgActivityPage();

        if (empty($rg_activity_page)) {
            $rg_activity_page = $this->createRgActivityPage();
        }

        if (!$this->isMobileGeneralBoxAddedToPage($rg_activity_page)) {
            $this->addMobileGeneralBoxToPage($rg_activity_page);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        // Do nothing for this specific scenario
    }

    /**
     * Insert record for Rg Activity page to pages table
     */
    private function createRgActivityPage()
    {
        $page = $this->rg_activity_page;
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
            ->where('alias', '=', 'rg-activity')
            ->where('cached_path', '=', '/mobile/rg-activity')
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
    private function addMobileGeneralBoxToPage($rg_activity_page)
    {
        $box_data = $this->box_data;
        $box_data['page_id'] = $rg_activity_page->page_id;

        $this->connection
            ->table($this->boxes_table)
            ->insert($box_data);
    }

    /**
     * @param $rg_activity_page
     * @return bool
     */
    private function isMobileGeneralBoxAddedToPage($rg_activity_page): bool
    {
        $box_record = $this->connection->table($this->boxes_table)
            ->where('box_class', '=', $this->box_data['box_class'])
            ->where('page_id', '=', $rg_activity_page->page_id)
            ->first();

        return !empty($box_record);
    }
}
