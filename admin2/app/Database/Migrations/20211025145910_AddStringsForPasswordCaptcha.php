<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddStringsForPasswordCaptcha extends Migration
{
    private const ALIAS = 'forgot.password.captcha';
    private const LANGUAGE = 'en';

    private string $table;
    private Connection $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $localized_string = $this->connection->table($this->table)
            ->where('alias', '=', self::ALIAS)
            ->where('language', '=', self::LANGUAGE)
            ->first();

        if (!$localized_string) {
            $this->connection->table($this->table)->insert([
                'alias' => self::ALIAS,
                'language' => self::LANGUAGE,
                'value' => 'Insert the code',
                'requested' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)
            ->where('alias', '=', self::ALIAS)
            ->where('language', '=', self::LANGUAGE)
            ->delete();
    }
}