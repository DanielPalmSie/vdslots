<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CreateSeederForKungaslottet extends Migration
{
    protected $table;
    /**
     * @var App\Extensions\Database\Connection\MySqlConnection
     */
    protected $connection;
    private $brand;

    const BRAND = 'kungaslottet';

    public function init()
    {
        $this->table = 'page_settings';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    private array $replacementList = [
        ['currentValue' => 'MV-BG.jpg', 'newValue' => 'main-background.jpg'],
    ];


    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand !== self::BRAND) {
            return;
        }
        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['currentValue']}', '${replacement['newValue']}')");
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand !== self::BRAND) {
            return;
        }
        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }
    }
}
