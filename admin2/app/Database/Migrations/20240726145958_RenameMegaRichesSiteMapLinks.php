<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class RenameMegaRichesSiteMapLinks extends Migration
{

    protected $table;
    protected $tableSettings;

    protected $connection;
    private $brand;

    private array $aliases = [
        ['value' => 'dga-dansk-vilkar-og-betingelser', 'alias' => '#page.dga-dansk-vilkar-og-betingelser.title'],
        ['value' => 'mga-games-specific', 'alias' => '#page.title.terms.lga-games-specific'],
    ];
    private array $renameAlias = ['value' => 'terms-and-conditions-complaints', 'newValue' => 'terms-and-Conditions-Complaints'];
    private array $deletePages = ['sports', 'prematch', 'live'];

    public function init()
    {
        $this->table = 'pages';
        $this->tableSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }
    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand == 'megariches'){
          $this->addTitle('add');
          $this->renameAlias('add');
          $this->addDomain('add');
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand == 'megariches') {
            $this->addTitle('remove');
            $this->renameAlias('remove');
            $this->addDomain('remove');
        }
    }

    public function addTitle($action)
    {
        foreach ($this->aliases as $alias) {
            $pageIds = $this->connection->table($this->table)
                ->where('alias', $alias['value'])
                ->pluck('page_id');

            if ($action === 'add') {
                $newSettings = $pageIds->map(fn($pageId) => [
                    'page_id' => $pageId,
                    'name' => 'title',
                    'value' => $alias['alias'],
                ])->toArray();

                $this->connection->table($this->tableSettings)->insert($newSettings);
            } else{
                $this->connection->table($this->tableSettings)
                    ->whereIn('page_id', $pageIds)
                    ->where('name', 'title')
                    ->where('value', $alias['alias'])
                    ->delete();
            }
        }

    }

    public function renameAlias($action)
    {
        $currentAlias = $action === 'add' ? $this->renameAlias['value'] : $this->renameAlias['newValue'];
        $newAlias = $action === 'add' ? $this->renameAlias['newValue'] : $this->renameAlias['value'];

        $this->connection->table($this->table)
            ->where('alias', $currentAlias)
            ->update(['alias' => $newAlias]);
    }
    public function addDomain($action)
    {
        $pageIds =  $this->connection->table($this->table)->whereIn('alias', $this->deletePages)->pluck('page_id');
        if ($action == 'add') {
            $newSettings = $pageIds->map(function ($pageId) {
                return [
                    'page_id' => $pageId,
                    'name' => 'domain',
                    'value' => 'none',
                ];
            })->toArray();
            $this->connection->table( $this->tableSettings)->insert($newSettings);
        }else {
            $this->connection->table($this->tableSettings)->whereIn('page_id', $pageIds)->where('name', 'domain')->where('value', 'none')->delete();
        }

    }
}
