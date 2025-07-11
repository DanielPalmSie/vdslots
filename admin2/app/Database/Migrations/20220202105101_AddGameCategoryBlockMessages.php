<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddGameCategoryBlockMessages extends Migration
{
    /** @var string */
    private $localized_strings_table;

    /** @var string */
    private $localized_strings_connection_table;

    /** @var Connection */
    private $connection;

    /** @var array */
    private $localized_strings_table_items;

    /** @var array */
    private $localized_strings_connection_table_items;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->localized_strings_connection_table = 'localized_strings_connections';

        $this->connection = DB::getMasterConnection();

        $this->localized_strings_table_items = [
            [
                'alias' => 'game-category-block-indefinite.title',
                'language' => 'en',
                'value' => 'Game Category Block'
            ],
            [
                'alias' => 'game-category-block-indefinite.description',
                'language' => 'en',
                'value' => 'Lock your account for a indefinite period of time for a specific game category.
                    You can for example lock your self from playing any Video Slots game for a indefinite period
                    of time by selecting "Video Slots" and press "Save".'
            ],
            [
                'alias' => 'game-category-block-indefinite.unblock',
                'language' => 'en',
                'value' => 'Unblock'
            ],
            [
                'alias' => 'game-category-block-indefinite.blocked.info',
                'language' => 'en',
                'value' => 'You locked this game category indefinite.'
            ],
            [
                'alias' => 'game-category-block-indefinite.message.info',
                'language' => 'en',
                'value' => 'Locking comes into effect immediately, unlocking has a cool off period of <b>%d days.</b>'
            ]
        ];

        $this->localized_strings_connection_table_items = [
            [
                'target_alias' => 'game-category-block-indefinite.title',
                'bonus_code' => 0,
                'tag' => 'game-category-block'
            ],
            [
                'target_alias' => 'game-category-block-indefinite.description',
                'bonus_code' => 0,
                'tag' => 'game-category-block'
            ],
            [
                'target_alias' => 'game-category-block-indefinite.unblock',
                'bonus_code' => 0,
                'tag' => 'game-category-block'
            ],
            [
                'target_alias' => 'game-category-block-indefinite.blocked.info',
                'bonus_code' => 0,
                'tag' => 'game-category-block'
            ],
            [
                'target_alias' => 'game-category-block-indefinite.message.info',
                'bonus_code' => 0,
                'tag' => 'game-category-block'
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->localized_strings_table_items as $item) {
            $exists = $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $this->connection
                ->table($this->localized_strings_table)
                ->insert([$item]);
        }

        foreach ($this->localized_strings_connection_table_items as $item) {
            $exists = $this->connection
                ->table($this->localized_strings_connection_table)
                ->where('target_alias', $item['target_alias'])
                ->where('tag', $item['tag'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $this->connection
                ->table($this->localized_strings_connection_table)
                ->insert([$item]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->localized_strings_table)
            ->whereIn('alias', ['game-category-block-indefinite.title', 'game-category-block-indefinite.description', 'game-category-block-indefinite.unblock', 'game-category-block-indefinite.blocked.info', 'game-category-block-indefinite.message.info'])
            ->where('language', '=', 'en')
            ->delete();

        $this->connection
            ->table($this->localized_strings_connection_table)
            ->whereIn('target_alias', ['game-category-block-indefinite.title', 'game-category-block-indefinite.description', 'game-category-block-indefinite.unblock', 'game-category-block-indefinite.blocked.info', 'game-category-block-indefinite.message.info'])
            ->where('tag', '=', 'game-category-block')
            ->delete();
    }
}
