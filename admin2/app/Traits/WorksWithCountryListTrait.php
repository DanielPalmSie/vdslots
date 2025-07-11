<?php

namespace App\Traits;

trait WorksWithCountryListTrait
{
    /**
     * Will build value for excluded_countries field
     *
     * Action can be added or removed
     *
     * With 'add', country code` will be added to string
     * With 'remove' country code will be removed from string
     *
     * @param array $countries
     * @param string $action
     * @param string $country_code
     *
     * @return string
     */
    private function buildCountriesValue(array $countries, string $action, string $country_code): string
    {
        if($action === 'add') {
            $countries[] = $country_code;
        }

        if($action === 'remove') {
            unset($countries[array_search($country_code, $countries)]);
        }

        return implode(' ', $countries);
    }

    /**
     * @param $item
     * @param string $countries_field
     *
     * @return array
     */
    private function getCountriesArray($item, string $countries_field): array
    {
        return array_filter(explode(' ', $item->{$countries_field}));
    }
}