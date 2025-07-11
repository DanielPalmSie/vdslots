<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddPermissionTagForWithdrawalBlock extends Seeder
{

    private Connection $connection;
    private string $groupsTable;
    private string $withdrawalBlockPermission;
    /**
     * @var array|string[]
     */
    private array $groupNames;
    private string $permissionGroupsTable;

    public function init()
    {
        $this->groupNames = [
            'Chief Executive Officer (CEO)',
            'Chief Operations Officer (COO)',
            'Customer Service Department - Head',
            'Customer Service Department - Team Leader',
            'MLRO - Deputy',
            'Payment and Risk Department',
            'Payment and Risk Department - Head',
            'Payment and Risk Department - Team Leader',
            'Payments and Risk – Due Diligence',
            'Responsible Gaming Department',
            'Responsible Gaming Department - Head',
            'Responsible Gaming Department - Team Leader'
        ];
        $this->groupsTable = 'groups';
        $this->permissionGroupsTable = 'permission_groups';
        $this->withdrawalBlockPermission = 'user.withdraw.block';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $groupIds = $this->connection
            ->table($this->groupsTable)
            ->whereIn('name', $this->groupNames)
            ->pluck('group_id');

        $permissionGroups = $groupIds->map(function($id) {
            return [
                'group_id' => $id,
                'tag' => $this->withdrawalBlockPermission,
                'mod_value' => '',
                'permission' => 'grant',
            ];
        });

        $this->connection
            ->table($this->permissionGroupsTable)
            ->insert($permissionGroups->toArray());
    }

    public function down()
    {
        $this->connection
            ->table($this->permissionGroupsTable)
            ->where('tag', $this->withdrawalBlockPermission)
            ->delete();
    }
}