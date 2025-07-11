<?php

namespace App\Validator\Rules;

use App\Models\RiskProfileRating;
use App\Repositories\RiskProfileRatingRepository;

class CriminalRecordStatus
{
    private ?string $section;
    private ?string $jurisdiction;

    public function __construct($section, $jurisdiction)
    {
        $this->section = $section;
        $this->jurisdiction = $jurisdiction;
    }

    public function __invoke($field, $value, array $params, array $fields): bool
    {
        $criminal_record_options = RiskProfileRatingRepository::getCategorySettings(
            RiskProfileRating::CRIMINAL_RECORD_PARENT_CATEGORY,
            $this->getJurisdiction(),
            $this->getSection()
        );

        $has_matched_option = array_filter($criminal_record_options, function ($option) use ($value) {
            return json_decode($option['data'], true)['slug'] == $value;
        });

        return (bool)count($has_matched_option);
    }

    /**
     * @return string
     */
    private function getSection(): string
    {
        return (string) $this->section;
    }

    /**
     * @return string
     */
    private function getJurisdiction(): string
    {
        return (string) $this->jurisdiction;
    }
}
