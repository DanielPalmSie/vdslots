<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class UpdateKungaslottetLogoInLocalizedString extends Migration
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
        ['currentValue' => 'mr%20vegas%20logo.png', 'newValue' => 'kungaslottet_logo_473x302.png'],
        ['currentValue' => 'Mr.Vegas', 'newValue' => 'Kungaslottet'],
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
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
