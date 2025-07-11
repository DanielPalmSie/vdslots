<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class TagEditProfileAliasesForApp extends Seeder
{
    private Connection $connection;
    private string $connectionsTable;
    private string $tag;
    private array $aliases;

    public function init()
    {
        $this->aliases = [
            'msg.title',
            'edit-profile.validation-code.placeholder',
            'edit-profile.validation-code.submit-btn',
            'edit-profile.validation-code.resend-btn'
        ];
        $this->connectionsTable = 'localized_strings_connections';
        $this->tag = 'mobile_app_localization_tag';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->aliases as $alias)
        {
            $this->connection
                ->table($this->connectionsTable)
                ->updateOrInsert([
                    'target_alias' => $alias,
                    'bonus_code' => 0,
                    'tag' => $this->tag
                ]);
        }
    }

    public function down()
    {
        foreach ($this->aliases as $alias)
        {
            $this->connection
                ->table($this->connectionsTable)
                ->where('target_alias', $alias)
                ->where('tag', $this->tag)
                ->delete();
        }
    }
}
