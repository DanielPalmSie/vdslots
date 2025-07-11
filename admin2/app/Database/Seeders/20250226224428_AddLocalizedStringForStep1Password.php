<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForStep1Password extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $connectionsTable;
    private string $alias;
    private string $tag;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
        $this->connectionsTable = 'localized_strings_connections';
        $this->alias = 'password.no.whitespace';
        $this->tag = 'mobile_app_localization_tag';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'register.password.error.message')
            ->where('language', 'en')
            ->update(['value' => 'Password must be at least 8 characters long, include at least one uppercase letter, one lowercase letter, and two numbers. Spaces are not allowed.']);

        $this->connection
            ->table($this->table)
            ->where('alias', 'register.address.error.message')
            ->where('language', 'en')
            ->update(['value' => 'Address must be 3-50 characters. Use letters, numbers, accented characters, commas, periods, hyphens, apostrophes, spaces, slashes, ampersands, and pound signs. Avoid special characters at the beginning.']);

        $this->connection
            ->table($this->table)
            ->insert([
                [
                    'alias' => 'password.no.whitespace',
                    'language' => 'en',
                    'value' => 'Password cannot contain spaces.'
                ],
            ]);

        $isAliasExists = $this->connection
            ->table($this->connectionsTable)
            ->where('target_alias', '=', $this->alias)
            ->where('tag', '=', $this->tag)
            ->exists();

        if (!$isAliasExists) {
            $this->connection
                ->table($this->connectionsTable)
                ->insert([
                    'target_alias' => $this->alias,
                    'tag' => $this->tag,
                ]);
        }
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'register.password.error.message')
            ->where('language', 'en')
            ->update(['value' => 'Password needs to be at least 8 characters long and contain at least 1 lower and 1 upper case letter and 2 numbers.']);

        $this->connection
            ->table($this->table)
            ->where('alias', 'register.address.error.message')
            ->where('language', 'en')
            ->update(['value' => 'Address needs to be 3-100 characters. Use letters, numbers, accented characters, commas, periods, hyphens, apostrophes, spaces, slashes, ampersands, and pound signs. Avoid special characters at the beginning.']);

        $this->connection
            ->table($this->table)
            ->whereIn('alias', [
                'password.no.whitespace',
            ])
            ->delete();

        $this->connection
            ->table($this->connectionsTable)
            ->where('target_alias', '=', $this->alias)
            ->where('tag', '=', $this->tag)
            ->delete();
    }
}