<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringForRegistratonStep2Megariches extends Seeder
{

    private Connection $connection;
    private string $table;
    private string $alias;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
        $this->alias = 'register.birthdate.nostar';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', $this->alias)
            ->where('language', 'en')
            ->update([
                'value' => 'Date of Birth',
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', $this->alias)
            ->where('language', 'en')
            ->update([
                'value' => 'Birth date',
            ]);
    }
}
