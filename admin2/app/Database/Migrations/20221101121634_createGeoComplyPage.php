<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CreateGeoComplyPage extends Migration
{

    protected $tablePages;
    protected $tableBoxes;
    protected $connection;


    public function init(){
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {

        //registration GeoComply popup - Desktop
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => 0,
            'alias' => 'registration-geocomply',
            'filename' => 'diamondbet/registration.php',
            'cached_path' => '/registration-geocomply',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'GeoComplyBox',
            'priority' => 0,
            'page_id' => $this->getPageID('/registration-geocomply'),
        ]);


        //registration GeoComply popup - Mobile
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => '268',
            'alias' => 'register-geocomply',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/register-geocomply',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'GeoComplyBox',
            'priority' => 0,
            'page_id' => $this->getPageID('/mobile/register-geocomply'),
        ]);

    }

    /**
     * Undo the migration
     */
    public function down()
    {

        //registration GeoComply - Desktop
        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'GeoComplyBox')
            ->where('page_id', '=', $this->getPageID('/registration-geocomply'))
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'registration-geocomply')
            ->where('filename', '=', 'diamondbet/registration.php')
            ->where('cached_path', '=', '/registration-geocomply')
            ->delete();

        //registration GeoComply - Mobile
        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'GeoComplyBox')
            ->where('page_id', '=', $this->getPageID('/mobile/register-geocomply'))
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'register-geocomply')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/register-geocomply')
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
