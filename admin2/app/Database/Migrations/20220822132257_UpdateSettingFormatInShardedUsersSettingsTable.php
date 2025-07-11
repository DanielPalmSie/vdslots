<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateSettingFormatInShardedUsersSettingsTable extends Migration
{
    private const AWARD_ID_INDEX_ADD = 0;
    private const AWARD_TYPE_INDEX_ADD = 1;
    private const AWARD_KEY_INDEX_ADD = 2;
    private const AWARD_ID_INDEX_REMOVE = 1;
    private const AWARD_TYPE_INDEX_REMOVE = 2;
    private const AWARD_KEY_INDEX_REMOVE = 0;

    private string $table;
    
    public function init()
    {
        $this->table = 'users_settings';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $user_settings = $this->getUserSettings();
        $settings_format = $this->formatSettings($user_settings, 'add');
        
        DB::loopNodes(function (Connection $connection)  use ($settings_format) {
            foreach ($settings_format as $from => $to) {
                $connection->table($this->table)
                    ->where('setting', $from)
                    ->update(['setting' => $to])
                ;
            }
        }, false);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $user_settings = $this->getUserSettings();
        $settings_format = $this->formatSettings($user_settings, 'remove');
        
        DB::loopNodes(function (Connection $connection)  use ($settings_format) {
            foreach ($settings_format as $from => $to) {
                $connection->table($this->table)
                    ->where('setting', $from)
                    ->update(['setting' => $to])
                ;
            }
        }, false);
    }

    private function getUserSettings(): array
    {
        $searchTypes = ['%-awardexp', '%-spins', 'awardexp-%', 'spins-%'];
        $user_settings = [];
        
        DB::loopNodes(function (Connection $connection) use (&$searchTypes, &$user_settings){
        foreach ($searchTypes as $type) {
                $user_settings[] = $connection
                    ->table($this->table)
                    ->select('setting')
                    ->where('setting', 'LIKE', $type)
                    ->pluck('setting')
                    ->toArray()
                    
                ;
            
            }
        }, false);
        
        return $user_settings;
    }

    private function formatSettings(array $user_settings, string $action): array
    {
        $new_setting_format = [];

        foreach ($user_settings as $items) {
            foreach ($items as $item) {
                $setting_type = explode('-', $item);

                if ($action === 'add') {
                    $new_setting_format[$item] = sprintf('%s-%s-%s',
                        $setting_type[self::AWARD_KEY_INDEX_ADD],
                        $setting_type[self::AWARD_ID_INDEX_ADD],
                        $setting_type[self::AWARD_TYPE_INDEX_ADD]
                    );
                }

                if ($action === 'remove') {
                    $new_setting_format[$item] = sprintf('%s-%s-%s',
                        $setting_type[self::AWARD_ID_INDEX_REMOVE],
                        $setting_type[self::AWARD_TYPE_INDEX_REMOVE],
                        $setting_type[self::AWARD_KEY_INDEX_REMOVE],
                    );
                }
            }
        }

        return $new_setting_format;
    }
}
