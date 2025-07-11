<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AdjustLocalizedStringsForAddNationalityPopup extends Seeder
{
    private $connection;

    private string $table = 'localized_strings';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'nationalityandpob.saved.success')
            ->where('language', 'en')
            ->update(['value' => 'Nationality and Place of Birth were saved successfully.']);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'nationalityandpob.saved.success')
            ->where('language', 'en')
            ->update(['value' => 'Nationality and Country of Birth were saved successfully.']);
    }
}
