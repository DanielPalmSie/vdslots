<?php

trait LoadProvinces
{
    /**
     * @param $country
     */
    private function loadProvinces($country): array
    {
        if (empty($this->provinces_data)) {
            $this->provinces_data = $this->loadProvincesFromDataBase($country);
        }
        return $this->provinces_data;
    }

    /**
     * @param $country
     * @return array
     */
    private function loadProvincesFromDataBase($country): array
    {
        return lic('getByTags', ['provinces', true, $country], $this->user)['provinces'] ?? [];
    }
}