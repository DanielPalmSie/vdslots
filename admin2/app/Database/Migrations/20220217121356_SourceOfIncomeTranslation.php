<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class SourceOfIncomeTranslation extends Migration
{
    /** @var string */
    private $localized_strings_table;

    /** @var array */
    private $localized_strings_table_items;

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->localized_strings_table = 'localized_strings';
        $this->connection = DB::getMasterConnection();

        $this->localized_strings_table_items = [
            [
                'alias' => 'select.income.types.payslip',
                'language' => 'en',
                'value' => 'Payslip'
            ],
            [
                'alias' => 'select.income.types.pension',
                'language' => 'en',
                'value' => 'Pension'
            ],
            [
                'alias' => 'select.income.types.inheritance',
                'language' => 'en',
                'value' => "Inheritance"
            ],
            [
                'alias' => 'select.income.types.gifts',
                'language' => 'en',
                'value' => "Gifts"
            ],
            [
                'alias' => 'select.income.types.tax_declaration',
                'language' => 'en',
                'value' => "Tax Declaration"
            ],
            [
                'alias' => 'select.income.types.dividends',
                'language' => 'en',
                'value' => "Dividends"
            ],
            [
                'alias' => 'select.income.types.interest',
                'language' => 'en',
                'value' => "Interest"
            ],
            [
                'alias' => 'select.income.types.business_activities',
                'language' => 'en',
                'value' => "Business activities"
            ],
            [
                'alias' => 'select.income.types.divorce_settlements',
                'language' => 'en',
                'value' => "Divorce settlements"
            ],
            [
                'alias' => 'select.income.types.gambling_wins',
                'language' => 'en',
                'value' => "Gambling wins"
            ],
            [
                'alias' => 'select.income.types.sales_of_property',
                'language' => 'en',
                'value' => "Sales of property"
            ],
            [
                'alias' => 'select.income.types.rental_income',
                'language' => 'en',
                'value' => "Rental Income"
            ],
            [
                'alias' => 'select.income.types.capital_gains',
                'language' => 'en',
                'value' => "Capital Gains"
            ],
            [
                'alias' => 'select.income.types.royalty_or_licensing_income',
                'language' => 'en',
                'value' => "Royalty or Licensing Income"
            ],
            [
                'alias' => 'select.income.types.other',
                'language' => 'en',
                'value' => "Other"
            ],
            [
                'alias' => 'sourceofincomepic.section.headline',
                'language' => 'en',
                'value' => "Source of Income"
            ],
            [
                'alias' => 'sourceofincomepic.section.confirm.info',
                'language' => 'en',
                'value' => "Accordingly with regulation regarding affordability, we are required to request documents of how you fund your gambling. Please upload below document, you can upload one or several documents."
            ],
            [
                'alias' => 'select.income.types.options',
                'language' => 'en',
                'value' => "Select an option"
            ],
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
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->localized_strings_table_items as $item) {
            $this->connection
                ->table($this->localized_strings_table)
                ->where('alias', '=', $item['alias'])
                ->where('language', '=', $item['language'])
                ->delete();
        }
    }
}


