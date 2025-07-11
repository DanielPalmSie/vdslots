<?php

trait LoadOccupations
{
    /**
     * @param $country
     */
    private function loadOccupations($country, $industry = ''): array
    {
        return $this->loadOccupationsFromDataBase($country, $industry);
    }

    /**
     * @param $country
     * @return array
     */
    private function loadOccupationsFromDataBase($country, $industry): array
    {
        return lic('getByTagsAndConfigName', ['occupations', $industry, true, $country], $this->user)['occupations'] ?? [];
    }
}
