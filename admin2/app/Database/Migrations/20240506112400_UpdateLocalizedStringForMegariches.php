<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForMegariches extends Migration
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
        ['currentValue' => 'https://www.facebook.com/pages/Mr Vegascom', 'newValue' => 'https://www.facebook.com/pages/MegaRichescom'],
        ['currentValue' => 'https://www.facebook.com/mrvegascom', 'newValue' => 'https://www.facebook.com/megarichescom'],
        ['currentValue' => 'support@mrvegas.com', 'newValue' => 'support@megariches.com'],
        ['currentValue' => 'support@mr vegas.com', 'newValue' => 'support@megariches.com'],
        ['currentValue' => '/file_uploads/mr%20vegas%20logo.png', 'newValue' => '/file_uploads/megariches%20logo.png'],
        ['currentValue' => '/file_uploads/mrvegas-logo-de.png', 'newValue' => '/file_uploads/megariches-logo-de.png'],
        ['currentValue' => '/file_uploads/newsletters/Newsletter_mrvegas.jpg', 'newValue' => '/file_uploads/newsletters/Newsletter_megariches.jpg'],
        ['currentValue' => '/file_uploads/mrvegas_', 'newValue' => '/file_uploads/megariches_'],
        ['currentValue' => 'mrvegas.omc', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas.om', 'newValue' => 'megariches.com'],
        ['currentValue' => 'Mr Vegas', 'newValue' => 'MegaRiches'],
        ['currentValue' => 'MR Vegas', 'newValue' => 'MegaRiches'],
        ['currentValue' => 'MrVegas', 'newValue' => 'MegaRiches'],
        ['currentValue' => 'MR VEGAS', 'newValue' => 'MegaRiches'],
        ['currentValue' => 'Mr vegas', 'newValue' => 'MegaRiches'],
        ['currentValue' => 'Kungaslottet', 'newValue' => 'MegaRiches'],
        ['currentValue' => 'Kungaslottet.se', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas', 'newValue' => 'megariches'],
    ];

    private array $replacementDomain = [
        ['currentValue' => 'mrvegas.com', 'newValue' => 'megariches.com'],
        ['currentValue' => 'Mrvegas.com', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas.ca', 'newValue' => 'megariches.com'],
        ['currentValue' => 'MrVegas.ca', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas.es', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas.dk', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas.de', 'newValue' => 'megariches.com'],
        ['currentValue' => 'mrvegas.it', 'newValue' => 'megariches.com'],
    ];

    private array $replacementAlias = [
        ['currentValue' => 'mrvegas', 'newValue' => 'megariches'],
        ['currentValue' => 'kungaslottet', 'newValue' => 'megariches'],
    ];


    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }
        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['currentValue']}', '${replacement['newValue']}')");
        }

        foreach ($this->replacementDomain as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['currentValue']}', '${replacement['newValue']}')");
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
        if ($this->brand !== 'megariches') {
            return;
        }
        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }

        foreach ($this->replacementDomain as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }

        foreach ($this->replacementAlias as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET alias = REPLACE(alias, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }
    }

}
