<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of residential address
 * Class ResidenceType
 */
class ResidenceType extends AbstractEntity
{
    const PROVINCE_NO_RESIDENTS = 'EE';
    const CITY_NO_RESIDENTS = 'Estero';
    const ITALY = 'it';

    public $country;
    public $residential_address;
    public $municipality_of_residence;
    public $residential_province_acronym;
    public $residential_post_code;

    protected $fillable = [
        'country',
        'residential_address',
        'municipality_of_residence',
        'residential_province_acronym',
        'residential_post_code',
    ];

    protected $rules = [
        'country'                      => 'required',
        'residential_address'          => 'required|min:1|max:250',
        'municipality_of_residence'    => 'required|min:1|max:100',
        'residential_province_acronym' => 'required|alpha|min:2|max:2',
        'residential_post_code'        => 'required|alpha_num|min:5|max:5',
    ];

    /**
     * @return bool
     */
    public function isResidenceInItaly(): bool
    {
        return self::ITALY === strtolower($this->country);
    }

    /**
     * @return string
     */
    public function getResidentialProvinceAcronym(): string
    {
        return $this->isResidenceInItaly() ? $this->residential_province_acronym : self::PROVINCE_NO_RESIDENTS;
    }

    /**
     * @return string
     */
    public function getMunicipalityOfResidence(): string
    {
        return $this->isResidenceInItaly() ? $this->municipality_of_residence : self::CITY_NO_RESIDENTS;
    }

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        $this->municipality_of_residence = $this->getMunicipalityOfResidence();
        $this->residential_province_acronym = $this->getResidentialProvinceAcronym();

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "indirizzo" => $this->residential_address,
            "comune"    => $this->municipality_of_residence,
            "provincia" => $this->residential_province_acronym,
            "cap"       => $this->residential_post_code
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Address: {$this->residential_address}\n" .
           "Municipality: {$this->municipality_of_residence}\n" .
           "Province Acr: {$this->residential_province_acronym}\n" .
           "Postal Code: {$this->residential_post_code}";
    }
}