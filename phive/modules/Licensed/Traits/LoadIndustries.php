<?php

namespace Licensed\Traits;

trait LoadIndustries
{
    /**
     * @param $country
     * @param string $configTag
     */
    private function loadIndustries($country, string $configTag = 'industries'): array
    {
        return  $this->loadIndustriesFromDataBase($country, $configTag);
    }

    /**
     * @param $country
     * @param string $configTag
     * @return array
     */
    private function loadIndustriesFromDataBase($country, string $configTag = 'industries'): array
    {
        return lic('getByTags', [$configTag, true, $country], $this->user)[$configTag] ?? [];
    }
}
