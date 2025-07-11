<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddingLocalizedStringForSpendingAmountPopup extends Seeder
{
    private string $table;

    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'rg.spending.popup.job-title.label',
            'value' => 'Job Title',
        ],
        [
            'language' => 'en',
            'alias' => 'rg.spending.popup.industry.label',
            'value' => 'Industry',
        ],
        [
            'language' => 'en',
            'alias' => 'rg.spending.popup.job-title.input',
            'value' => 'Job Title',
        ],
        [
            'language' => 'en',
            'alias' => 'occupational.popup.top.message',
            'value' => 'As part of out regulatory requirements, we ask you to read and fill the information below.',
        ],
        [
            'language' => 'en',
            'alias' => 'occupational.popup.title',
            'value' => 'Occupation',
        ],
        [
            'language' => 'en',
            'alias' => 'occupational.popup.job-title.input',
            'value' => 'Job Title',
        ],
        [
            'language' => 'en',
            'alias' => 'occupational.form.validation.emptyJobTitle',
            'value' => 'Please select a Job title',
        ],
        [
            'language' => 'en',
            'alias' => 'occupational.form.validation.validJobTitle',
            'value' => 'Please enter a valid Job Title',
        ]
    ];

    public function init()
    {
        $this->table = 'localized_strings';
    }

    public function up()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        DB::getMasterConnection()
            ->table($this->table)
            ->whereIn('alias', array_column($this->data, 'alias'))
            ->where('language', '=', 'en')
            ->delete();
    }
}
