<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForOccupationForm extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'occupational.form.validation.validJobTitle')
            ->where('language', 'en')
            ->update(['value' => 'Please select a job title from the dropdown list']);

        $this->connection
            ->table($this->table)
            ->insert([
                [
                    'alias' => 'occupational.form.loading.jobTitles',
                    'language' => 'en',
                    'value' => 'Loading job titles...'
                ],
                [
                    'alias' => 'occupational.form.error.loading',
                    'language' => 'en',
                    'value' => 'Error loading job titles'
                ],
                [
                    'alias' => 'occupational.form.error.loading.tryAgain',
                    'language' => 'en',
                    'value' => 'Unable to load job titles. Please try again.'
                ]
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'occupational.form.validation.validJobTitle')
            ->where('language', 'en')
            ->update(['value' => 'Please enter a valid Job Title']);

        $this->connection
            ->table($this->table)
            ->whereIn('alias', [
                'occupational.form.loading.jobTitles',
                'occupational.form.error.loading',
                'occupational.form.error.loading.tryAgain'
            ])
            ->delete();
    }
}
