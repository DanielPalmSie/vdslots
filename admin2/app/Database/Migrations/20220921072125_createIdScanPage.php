<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CreateIdScanPage extends Migration
{
    /** @var string */
    protected $tablePages;
    protected $tableBoxes;
    protected $connection;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';
        $this->connection = DB::getMasterConnection();
    }


    /**
     * Do the migration
     */
    public function up()
    {
        //idscan
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => 0,
            'alias' => 'idscan',
            'filename' => 'diamondbet/generic.php',
            'cached_path' => '/idscan',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'IdScanBox',
            'priority' => 0,
            'page_id' => $this->getPageID('/idscan'),
        ]);

        //registration idscan

        $this->connection->table($this->tablePages)->insert([
            'parent_id' => 0,
            'alias' => 'registration-idscan',
            'filename' => 'diamondbet/registration.php',
            'cached_path' => '/registration-idscan',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'IdScanBox',
            'priority' => 0,
            'page_id' => $this->getPageID('/registration-idscan'),
        ]);


        //mobile idscan
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => '268',
            'alias' => 'idscan',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/idscan',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'IdScanBox',
            'priority' => 0,
            'page_id' => $this->getPageID('/mobile/idscan'),
        ]);

        //mobile register-idscan
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => '268',
            'alias' => 'register-idscan',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/register-idscan',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'IdScanBox',
            'priority' => 0,
            'page_id' => $this->getPageID('/mobile/register-idscan'),
        ]);

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        //idscan
        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'IdScanBox')
            ->where('page_id', '=', $this->getPageID( '/idscan'))
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'idscan')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/idscan')
            ->delete();


        //registration idscan
        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'IdScanBox')
            ->where('page_id', '=', $this->getPageID('/registration-idscan'))
                ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'registration-idscan')
            ->where('filename', '=', 'diamondbet/registration.php')
            ->where('cached_path', '=', '/registration-idscan')
            ->delete();


        //mobile idscan
        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'IdScanBox')
            ->where('page_id', '=', $this->getPageID('/mobile/idscan'))
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'idscan')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/idscan')
            ->delete();

        //mobile register idscan
        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'IdScanBox')
            ->where('page_id', '=', $this->getPageID('/mobile/register-idscan'))
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'register-idscan')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/register-idscan')
            ->delete();

    }

    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }

}
