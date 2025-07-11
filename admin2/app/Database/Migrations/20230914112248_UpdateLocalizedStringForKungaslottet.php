<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateLocalizedStringForKungaslottet extends Migration
{

    protected $table;

    protected $connection;
    private $brand;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    private array $replacementList = [
        ['currentValue' => 'Mr Vegas', 'newValue' => 'Kungaslottet'],
        ['currentValue' => 'MrVegas', 'newValue' => 'Kungaslottet'],
        ['currentValue' => 'mrvegas.om', 'newValue' => 'Kungaslottet.se'],
        ['currentValue' => 'mrvegas.omc', 'newValue' => 'Kungaslottet.se'],
        ['currentValue' => 'MR VEGAS', 'newValue' => 'Kungaslottet'],
        ['currentValue' => 'Mr vegas', 'newValue' => 'Kungaslottet'],
    ];

    private array $replacementDomain = [
        ['currentValue' => 'mrvegas.com', 'newValue' => 'kungaslottet.se'],
        ['currentValue' => 'mrvegas.dk', 'newValue' => 'kungaslottet.se'],
        ['currentValue' => 'mrvegas.ca', 'newValue' => 'kungaslottet.se'],
        ['currentValue' => 'mrvegas.de', 'newValue' => 'kungaslottet.se'],
    ];

    private array $replacementAlias = [
        ['currentValue' => 'mrvegas', 'newValue' => 'kungaslottet'],
    ];


    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }
        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['currentValue']}', '${replacement['newValue']}')");
        }

        foreach ($this->replacementDomain as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['currentValue']}', '${replacement['newValue']}') where language in ('en','sv')");
        }

        foreach ($this->replacementAlias as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET alias = REPLACE(alias, '${replacement['currentValue']}', '${replacement['newValue']}')");
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }
        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }

        foreach ($this->replacementDomain as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}') where language in ('en','sv')");
        }

        foreach ($this->replacementAlias as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET alias = REPLACE(alias, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }
    }
}
