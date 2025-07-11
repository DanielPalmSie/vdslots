<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class ProductViewPermissionGroup extends Migration
{

    protected string $tablePermissionGroups;
    private array $permissions;
    private Connection $connection;

    public function init()
    {

        $this->tablePermissionGroups = 'permission_groups';

        $this->connection = DB::getMasterConnection();

        $this->permissions = [

            // access to admin2
            'pager.page.446',
            'admin',
            'admin_top',

            // left section permission
            'accounting.section',
            'fraud.section',
            'rg.section',
            'settings.section',
            'users.section',

            // user section
            'view.account.actions',
            'view.account.permissions',
            'view.account.account-history',
            'view.account.notification-history',
            'view.account.bonuses',
            'view.account.trophies',
            'view.account.documents',
            'view.account.casino-races',
            'view.account.cashbacks',
            'view.account.wheel-of-jackpot-history',
            'view.account.limits',
            'view.account.vouchers',
            'view.account.reward-history',
            'view.account.game-sessions',
            'view.account.sessions',
            'view.account.game-history',
            'view.account.betswins',
            'view.account.xp-history',
            'view.account.game-info',
            'user.battles',
            'user.liability',
            'user.risk-score',
            'users.risk.score.report',
            'user.responsible-gaming-monitoring',
            'fraud.section.fraud-aml-monitoring',
            'user.fraud-grs-report',
            'user.id3global-result',

            // other user view data
            'user.personal-details.show.all.button',
            'users.search.email',
            'users.search.mobile',
            'user.search.show.obfuscated_data',

            // fraud section
            'fraud.section.aml-monitoring',
            'fraud.section.anonymous-methods',
            'fraud.section.anonymous-methods.download.csv',
            'fraud.section.big-depositors',
            'fraud.section.big-losers',
            'fraud.section.big-losers.download.csv',
            'fraud.section.big-winners',
            'fraud.section.big-winners.download.csv',
            'fraud.section.bonus-abusers',
            'fraud.section.daily-gladiators',
            'fraud.section.daily-gladiators.download.csv',
            'fraud.section.failed-deposits',
            'fraud.section.failed-deposits.download.csv',
            'fraud.section.fraud-aml-monitoring',
            'fraud.section.fraud-monitoring',
            'fraud.section.goaml',
            'fraud.section.high-depositors',
            'fraud.section.high-depositors.download.csv',
            'fraud.section.min-fraud',
            'fraud.fraud.section.min-fraud',
            'fraud.section.multi-method-transactions',
            'fraud.section.multi-method-transactions.download.csv',
            'fraud.section.non-turned-over-withdrawals',
            'fraud.section.non-turned-over-withdrawals.download.csv',
            'fraud.section.responsible-gaming-monitoring',
            'fraud.section.similar-account',
            'fraud.fraud.section.similar-account',
            'fraud.fraud.section.check-similarity',
            'fraud.section.user-risk-score',
            'aml.grs.score.report',


            // rg section
            'rg.section.cancellation-of-withdrawals',
            'rg.section.change-deposit-pattern',
            'rg.section.change-playing-pattern',
            'rg.section.change-wager-pattern',
            'rg.section.extended-game-play',
            'rg.section.frequent-account-closing-opening',
            'rg.section.frequent-game-play',
            'rg.section.high-wager-bet-spin',
            'rg.section.interaction-result-report',
            'rg.section.interactions',
            'rg.section.limit-changes',
            'rg.section.monitoring',
            'rg.section.multiple-changes-rg-limits',
            'rg.section.self-exclusion',
            'rg.section.user-risk-score',
            'rg.grs.risk.report',

            // accounting section
            'accounting.section.liability',
            'accounting.section.site-balance',
            'accounting.section.player-balance',
            'accounting.section.transaction-history',
            'accounting.section.pending-withdrawals',
            'accounting.section.consolidation',
            'accounting.section.gaming-revenue',
            'accounting.section.jackpot-logs',
            'accounting.section.open-bets',

            // settings page
            'config.section',
            'view.user.groups',
            'settings.triggers.section',
            'settings.aml-profile.section',
            'settings.rg-profile.section',
            'permission.view.{group_id}',
            'permission.edit.{group_id}',
        ];

    }


    /**
     * Do the migration
     */
    public function up()
    {

        $this->createGroup();

        $group_id = $this->getGroupID();

        foreach ($this->permissions as $permission) {

            $permission = str_replace("{group_id}",$group_id,$permission);

            $result = $this->connection->table($this->tablePermissionGroups)
                ->where('group_id', '=',  $group_id)
                ->where('tag','=',  $permission)
                ->exists();

            if(!$result) {
                $this->connection->table($this->tablePermissionGroups)
                    ->where('group_id', $group_id)
                    ->insert([
                        'group_id' => $group_id,
                        'tag' => $permission,
                        'permission' => 'grant'
                    ]);
            }
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {

        $group_id = $this->getGroupID();
        foreach ($this->permissions as $permission) {
            $permission = str_replace("{group_id}",$group_id,$permission);

            $this->connection->table($this->tablePermissionGroups)
                ->where('group_id', '=',  $group_id)
                ->where('tag','=',  $permission)
                ->delete();
        }

        $this->deleteGroup();

    }

    private function getGroupID(): int
    {
        $group = $this->connection
            ->table('groups')
            ->where('name', '=', 'Product Team - view')
            ->first();

        return (int)$group->group_id;
    }

    private function createGroup(): void
    {
        $group = $this->getGroupID();
        if( !$group) {
            $this->connection
                ->table('groups')
                ->insert([
                    'name' => 'Product Team - view'
                ]);
        }
    }

    private function deleteGroup() {
        $this->connection
            ->table('groups')
            ->where('name', '=', 'Product Team - view')
            ->delete();
    }

}
