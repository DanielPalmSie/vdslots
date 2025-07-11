<?php

namespace Licensed;

class LicenseFactory
{
    public static function licenseByJurisdiction($jurisdiction): \Licensed
    {
        $jurisdiction_country_map = array_flip(
            phive('Licensed')->getSetting('country_by_jurisdiction_map')
        );

        if ($jurisdiction === 'AGCO') {
            return phive("Licensed/CA/CAON");
        }

        if ($jurisdiction === 'MGA') {
            return phive("Licensed/MT/MT");
        }

        $country = $jurisdiction_country_map[$jurisdiction];

        return phive("Licensed/{$country}/{$country}");
    }
}