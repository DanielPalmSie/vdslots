<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForDbetBrand extends Seeder
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
        ['currentValue' => 'D bet', 'newValue' => 'DBET'],
        ['currentValue' => 'Dbet', 'newValue' => 'DBET'],
        ['currentValue' => 'dbet', 'newValue' => 'DBET'],
        ['currentValue' => 'support@videoslots.com', 'newValue' => 'support@dbet.com'],
        ['currentValue' => 'Videoslots.com', 'newValue' => 'dbet.com'],
        ['currentValue' => 'wwwvideoslots.com', 'newValue' => 'www.dbet.com'],
        ['currentValue' => 'videoslots.com', 'newValue' => 'dbet.com'],
        ['currentValue' => '/file_uploads/DBET%20logo.png', 'newValue' => '/file_uploads/dbet%20logo.png'],
        ['currentValue' => '/file_uploads/DBET-logo-de.png', 'newValue' => '/file_uploads/dbet-logo-de.png'],
        ['currentValue' => '/file_uploads/DBET_', 'newValue' => '/file_uploads/dbet_'],
        ['currentValue' => 'DBET.com', 'newValue' => 'dbet.com'],
        ['currentValue' => 'DBET.se', 'newValue' => 'dbet.se'],
        ['currentValue' => 'Videoslots', 'newValue' => 'DBET'],
        ['currentValue' => 'www.facebook.com/pages/DBETcom', 'newValue' => 'www.facebook.com/pages/dbetcom'],
        ['currentValue' => 'www.facebook.com/DBETcom', 'newValue' => 'www.facebook.com/dbetcom'],
        ['currentValue' => 'www.facebook.com/pages/Videoslotscom', 'newValue' => 'www.facebook.com/pages/dbetcom'],
        ['currentValue' => 'www.facebook.com/videoslotscom', 'newValue' => 'www.facebook.com/dbetcom'],
        ['currentValue' => 'support@DBET.com', 'newValue' => 'support@dbet.com'],
    ];

    private array $replacementDomain = [
        ['currentValue' => 'support@videoslots.com', 'newValue' => 'support@dbet.com'],
        ['currentValue' => 'Videoslots.com', 'newValue' => 'dbet.com'],
        ['currentValue' => 'DBET.com', 'newValue' => 'dbet.com'],
        ['currentValue' => 'Videoslots', 'newValue' => 'DBET'],
    ];

    private array $replacementAlias = [
        ['currentValue' => 'Dbet', 'newValue' => 'dbet'],
        ['currentValue' => 'DBET', 'newValue' => 'dbet'],
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
