<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;

class TurnOffActivityFeedBoxForNL extends Seeder
{
    use WorksWithCountryListTrait;

    private Connection $connection;
    private const COUNTRY = 'NL';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $page = $this->connection
            ->table('pages')
            ->where('cached_path', '=', '/.')
            ->first();

        if (empty($page)) {
            return;
        }

        $box = $this->connection
            ->table('boxes')
            ->where('box_class', '=', 'ActivityFeedBox')
            ->where('page_id', '=', $page->page_id)
            ->first();

        if (empty($box)) {
            return;
        }

        $box_attribute = $this->connection
            ->table('boxes_attributes')
            ->where('box_id', '=', $box->box_id)
            ->where('attribute_name', '=', 'excluded_countries')
            ->first();

        if (empty($box_attribute)) {
            $this->connection
                ->table('boxes_attributes')
                ->insert([
                    'box_id' => $box->box_id,
                    'attribute_name' => 'excluded_countries',
                    'attribute_value' => self::COUNTRY,
                ]);
        } else {
            $countries = $this->getCountriesArray($box_attribute, 'attribute_value');

            if (in_array(self::COUNTRY, $countries)) {
                return;
            }

            $this->connection
                ->table('boxes_attributes')
                ->where('box_id', '=', $box->box_id)
                ->where('attribute_name', '=', 'excluded_countries')
                ->update(['attribute_value' => $this->buildCountriesValue($countries,'add', self::COUNTRY)]);
        }
    }
}