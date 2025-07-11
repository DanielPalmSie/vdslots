<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CreateSwishPaymentPageMegariches extends Migration
{
    protected $tablePages;
    protected $tableBoxes;
    protected $connection;
    private string $brand;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand === 'megariches') {
            $pageExists = $this->connection
                ->table($this->tablePages)
                ->where('cached_path', '=', '/payment-swish')
                ->exists();

            $boxExists = $this->connection
                ->table($this->tableBoxes)
                ->where('box_class', '=', 'PaymentSwishBox')
                ->exists();


            if (!$pageExists) {
                $this->connection->table($this->tablePages)->insert([
                    'parent_id' => 0,
                    'alias' => 'payment-swish',
                    'filename' => 'diamondbet/registration.php',
                    'cached_path' => '/payment-swish',
                ]);
            }

            if (!$boxExists) {
                $this->connection->table($this->tableBoxes)->insert([
                    'container' => 'full',
                    'box_class' => 'PaymentSwishBox',
                    'priority' => 0,
                    'page_id' => $this->getPageID('/payment-swish'),
                ]);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->tableBoxes)
                ->where('container', '=', 'full')
                ->where('box_class', '=', 'PaymentSwishBox')
                ->where('page_id', '=', $this->getPageID('/payment-swish'))
                ->delete();

            $this->connection
                ->table($this->tablePages)
                ->where('alias', '=', 'payment-swish')
                ->where('filename', '=', 'diamondbet/registration.php')
                ->where('cached_path', '=', '/payment-swish')
                ->delete();
        }
    }

    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int) $page->page_id;
    }
}
