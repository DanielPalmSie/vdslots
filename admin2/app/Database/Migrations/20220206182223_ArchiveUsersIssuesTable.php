<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class ArchiveUsersIssuesTable extends Migration
{
    private string $table_users_issues = 'users_issues';
    private string $table_users_issues_archived = 'users_issues_archived';

    protected MysqlBuilder $schema;

    /**
     * Do the migration
     */
    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->rename($this->table_users_issues, $this->table_users_issues_archived);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->rename($this->table_users_issues_archived, $this->table_users_issues);
    }
}
