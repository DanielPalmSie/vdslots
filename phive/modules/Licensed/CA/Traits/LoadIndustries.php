<?php

trait LoadIndustries
{
    /**
     * @param $country
     */
    private function loadIndustries($country): array
    {
        if (empty($this->industries_data)) {
            $this->industries_data = $this->loadIndustriesFromDataBase($country);
        }
        return $this->industries_data;
    }

    /**
     * @param $country
     * @return array
     */
    private function loadIndustriesFromDataBase($country): array
    {
        return lic('getByTags', ['industries', true, $country], $this->user)['industries'] ?? [];
    }
}
