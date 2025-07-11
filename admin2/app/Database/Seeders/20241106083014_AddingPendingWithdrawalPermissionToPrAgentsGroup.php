<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;


class AddingPendingWithdrawalPermissionToPrAgentsGroup extends Seeder
{

    protected string $tablePermissionGroups;
    private Connection $connection;

    private array $groups;

    private string $new_permission_tag = 'accounting.section.pending-withdrawals.actions.cancel';
    /**
     * @var string[][]
     */
    private array $permission_tags_items;
    private string $table_permission_tags;

    public function init()
    {
        $this->tablePermissionGroups = 'permission_groups';
        $this->connection = DB::getMasterConnection();

        $this->groups = [
            'Payment and Risk Department',
            'Payment and Risk Department - Head',
            'Payment and Risk Department - Team Leader'
        ];

        $this->table_permission_tags = 'permission_tags';

        $this->permission_tags_items = [
            ['tag' => 'accounting.section.pending-withdrawals.actions.cancel', 'mod_desc' => '(automatically added)'],
            ['tag' => 'accounting.section.pending-withdrawals.actions.pay', 'mod_desc' => '(automatically added)']
        ];
    }

    public function up()
    {

        $this->addPermissionTags();

        foreach ($this->groups as $group_name) {
            $group_id = $this->getGroupID($group_name);

            $result = $this->connection->table($this->tablePermissionGroups)
                ->where('group_id', '=', $group_id)
                ->where('tag', '=', $this->new_permission_tag)
                ->exists();

            if (!$result) {
                $this->connection->table($this->tablePermissionGroups)
                    ->where('group_id', $group_id)
                    ->insert([
                        'group_id' => $group_id,
                        'tag' => $this->new_permission_tag,
                        'permission' => 'grant'
                    ]);
            }
        }
    }

    public function down()
    {

        $this->removePermissionTags();

        foreach ($this->groups as $group_name) {
            $group_id = $this->getGroupID($group_name);

            $result = $this->connection->table($this->tablePermissionGroups)
                ->where('group_id', '=', $group_id)
                ->where('tag', '=', $this->new_permission_tag)
                ->exists();

            if ($result) {
                $this->connection->table($this->tablePermissionGroups)
                    ->where('group_id', '=', $group_id)
                    ->where('tag', '=', $this->new_permission_tag)
                    ->delete();
            }
        }
    }


    private function addPermissionTags() {
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
    }

    private function removePermissionTags() {
        foreach ($this->permission_tags_items as $tag_item) {
            $this->connection
                ->table($this->table_permission_tags)
                ->where('tag', $tag_item['tag'])
                ->delete();
        }
    }

    private function getGroupID($group_name): int
    {
        $group = $this->connection
            ->table('groups')
            ->where('name', '=', $group_name)
            ->first();

        return (int)$group->group_id;
    }
}
