<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class FixPromotionHtmlTranslationContent extends Migration
{

    protected $table;
    protected $connection;
    protected $replacement_alias;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
        $this->replacement_alias = ['current_value' => 'promotion.term.condition', 'new_value' => 'promotion.term.condition.html',];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->update("UPDATE `{$this->table}` SET alias = '{$this->replacement_alias['new_value']}' WHERE alias = '{$this->replacement_alias['current_value']}'");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->update("UPDATE `{$this->table}` SET alias = '{$this->replacement_alias['current_value']}' WHERE alias = '{$this->replacement_alias['new_value']}'");
    }
}
