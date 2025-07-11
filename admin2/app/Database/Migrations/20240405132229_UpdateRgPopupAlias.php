<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateRgPopupAlias extends Migration
{

    protected string $tableLocalizedStrings;
    private array $localizedStringAliasData;
    private string $postfix = '.html';

    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();

        $this->tableLocalizedStrings = 'localized_strings';
        $this->localizedStringAliasData= [
            'RG6.rg.info.description',
            'RG8.rg.info.description',
            'RG10.rg.info.description',
            'RG11.rg.info.description',
            'RG12.rg.info.description',
            'RG13.rg.info.description',
            'RG14.rg.info.description',
            'RG19.rg.info.description',
            'RG20.rg.info.description',
            'RG21.rg.info.description',
            'RG28.rg.info.description',
            'RG29.rg.info.description',
            'RG30.rg.info.description',
            'RG31.rg.info.description',
            'RG32.rg.info.description',
            'RG33.rg.info.description',
            'RG34.rg.info.description',
            'RG35.rg.info.description',
            'RG38.rg.info.description',
            'RG39.rg.info.description',
            'RG59.rg.info.description'
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->localizedStringAliasData as $alias) {
                $this->connection->table($this->tableLocalizedStrings)
                    ->where('alias', $alias)
                    ->update(['alias' => $alias.$this->postfix]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->localizedStringAliasData as $alias) {
            $this->connection->table($this->tableLocalizedStrings)
                ->where('alias', $alias.$this->postfix)
                ->update(['alias' => $alias]);
        }
    }
}
