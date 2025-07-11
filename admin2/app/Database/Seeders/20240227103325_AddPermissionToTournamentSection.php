<?php 

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddPermissionToTournamentSection extends Seeder
{
    private string $table_permission_tags;
    private array $permission_tags_items;
    private string $table_permission_groups;
    private array $permission_group_items;
    private array $groups;
    private string $table_groups;
    private Connection $connection;

    public function init()
    {
        $this->table_permission_tags = 'permission_tags';

        $this->permission_tags_items = [
            ['tag' => 'tournaments.section', 'mod_desc' => '(automatically added)'],
            ['tag' => 'tournaments.edit', 'mod_desc' => '(automatically added)'],
            ['tag' => 'tournaments.cancel', 'mod_desc' => '(automatically added)'],
            ['tag' => 'tournaments.pause', 'mod_desc' => '(automatically added)'],
            ['tag' => 'tournaments.resume', 'mod_desc' => '(automatically added)'],
            ['tag' => 'tournaments.calc', 'mod_desc' => '(automatically added)'],
            ['tag' => 'tournaments.close', 'mod_desc' => '(automatically added)']
        ];

        $this->table_permission_groups = 'permission_groups';

        $this->permission_group_items = [
            ['tag' => 'tournaments.section', 'permission' => 'grant'],
            ['tag' => 'tournaments.edit', 'permission' => 'grant'],
            ['tag' => 'tournaments.cancel', 'permission' => 'grant'],
            ['tag' => 'tournaments.pause', 'permission' => 'grant'],
            ['tag' => 'tournaments.resume', 'permission' => 'grant'],
            ['tag' => 'tournaments.calc', 'permission' => 'grant'],
            ['tag' => 'tournaments.close', 'permission' => 'grant']
        ];

        /*
         *Casino Department - Head
         *Casino Department - Games
         *Casino Department - Team Leader
         * 
        */
        $this->groups = ['Casino Department - Head', 'Casino Department - Games', 'Casino Department - Team Leader'];

        $this->table_groups = 'groups';

        $this->connection = DB::getMasterConnection();

    }


    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->permission_tags_items as $tag_item) {
            $tag_exists = $this->connection
                ->table($this->table_permission_tags)
                ->where('tag', $tag_item['tag'])
                ->first();

            if (empty($tag_exists)) {
                $this->connection
                    ->table($this->table_permission_tags)
                    ->insert($tag_item);
            }
        }

        foreach ($this->groups as $group_item) {
            $group = $this->connection
                ->table($this->table_groups)
                ->where('name', $group_item)
                ->first();

            if(!empty($group)){
                foreach ($this->permission_group_items as $permission_item) {
                    $permission_tag_exists = $this->connection
                        ->table($this->table_permission_groups)
                        ->where('group_id', $group->group_id)
                        ->where('tag', $permission_item['tag'])
                        ->first();
                    if (empty($permission_tag_exists)) {
                        $this->connection
                            ->table($this->table_permission_groups)
                            ->insert(['group_id' => $group->group_id, 'tag'=> $permission_item['tag'], 'permission' => $permission_item['permission']]);
                    }
    
                }
            }
        }
        
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->permission_tags_items as $tag_item) {
            $tag_exists = $this->connection
                ->table($this->table_permission_tags)
                ->where('tag', $tag_item['tag'])
                ->first();

            if (!empty($tag_exists)) {
                $this->connection
                    ->table($this->table_permission_tags)
                    ->where('tag', $tag_item['tag'])
                    ->delete();
            }
        }

        foreach ($this->groups as $group_item) {
            $group = $this->connection
                ->table($this->table_groups)
                ->where('name', $group_item)
                ->first();
            if(!empty($group)){
                foreach ($this->permission_group_items as $permission_item) {
                    $permission_tag_exists = $this->connection
                        ->table($this->table_permission_groups)
                        ->where('group_id', $group->group_id)
                        ->where('tag', $permission_item['tag'])
                        ->first();
                    
                    if (!empty($permission_tag_exists)) {
                        $this->connection
                            ->table($this->table_permission_groups)
                            ->where('group_id', $group->group_id)
                            ->where('tag', $permission_item['tag'])
                            ->where('permission', $permission_item['permission'])
                            ->delete();
                    }
    
                }
            }
            
        }
    }

}