<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForDbet extends Seeder
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
        ['currentValue' => 'https://www.facebook.com/pages/MegaRichescom', 'newValue' => 'https://www.facebook.com/pages/Dbetcom'],
        ['currentValue' => 'https://www.facebook.com/megarichescom', 'newValue' => 'https://www.facebook.com/dbetcom'],
        ['currentValue' => 'support@megariches.com', 'newValue' => 'support@dbet.com'],
        ['currentValue' => '/file_uploads/megariches%20logo.png', 'newValue' => '/file_uploads/dbet%20logo.png'],
        ['currentValue' => '/file_uploads/megariches-logo-de.png', 'newValue' => '/file_uploads/dbet-logo-de.png'],
        ['currentValue' => '/file_uploads/megariches_', 'newValue' => '/file_uploads/dbet_'],
        ['currentValue' => 'megariches.com', 'newValue' => 'dbet.com'],
        ['currentValue' => 'Megariches.com', 'newValue' => 'Dbet.com'],
        ['currentValue' => 'Mega Riches', 'newValue' => 'D bet'],
        ['currentValue' => 'Mega riches', 'newValue' => 'D bet'],
        ['currentValue' => 'MegaRiches', 'newValue' => 'Dbet'],
        ['currentValue' => 'Megariches', 'newValue' => 'Dbet'],
        ['currentValue' => 'megariches', 'newValue' => 'dbet'],
        ['currentValue' => 'megariches.se', 'newValue' => 'dbet.se'],
        ['currentValue' => 'Megariches.se', 'newValue' => 'Dbet.se'],
        ['currentValue' => 'megariche', 'newValue' => 'dbet'],
    ];

    private array $replacementDomain = [
        ['currentValue' => 'megariches.com', 'newValue' => 'dbet.com'],
        ['currentValue' => 'Megariches.com', 'newValue' => 'Dbet.com'],
    ];

    private array $replacementAlias = [
        ['currentValue' => 'Megariches', 'newValue' => 'Dbet'],
        ['currentValue' => 'megariches', 'newValue' => 'dbet'],
        ['currentValue' => 'MegaRiches', 'newValue' => 'Dbet'],
        ['currentValue' => 'megariche', 'newValue' => 'dbet'],
    ];


    public function up()
    {
        if ($this->brand !== 'dbet') {
            return;
        }
        foreach ($this->replacementDomain as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['currentValue']}', '${replacement['newValue']}') where language in ('en','sv')");
        }

        foreach ($this->replacementList as $replacement) {
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
        if ($this->brand !== 'dbet') {
            return;
        }
        foreach ($this->replacementDomain as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}') where language in ('en','sv')");
        }

        foreach ($this->replacementList as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }

        foreach ($this->replacementAlias as $replacement) {
            $this->connection->update("UPDATE `{$this->table}` SET alias = REPLACE(alias, '${replacement['newValue']}', '${replacement['currentValue']}')");
        }
    }
}
