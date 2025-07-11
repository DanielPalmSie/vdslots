<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of the date of birth
 * Class BirthDataType
 */
class BirthDataType extends AbstractEntity
{
    const PROVINCE_NO_RESIDENTS = 'EE';
    const CITY_NO_RESIDENTS = 'Estero';
    const ITALY = 'it';

    public $country;
    public $birthplace;
    public $birthplace_province_acronym;
    public $date_of_birth;

    protected $fillable = [
        'country',
        'birthplace',
        'birthplace_province_acronym',
        'date_of_birth'
    ];

    protected $rules = [
        'country' => 'required',
        'birthplace' => 'required|alpha_spaces|min:1|max:100',
        'birthplace_province_acronym' => 'required|alpha_num|min:2|max:2',
        'date_of_birth' => 'required|array',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        if (is_array($this->date_of_birth)) {
            $this->date_of_birth = (new DateType())->fill($this->date_of_birth);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isResidenceInItaly()
    {
        return self::ITALY === strtolower($this->country);
    }

    /**
     * @return string
     */
    public function getBirthplaceProvinceAcronym(): string
    {
        return $this->isResidenceInItaly() ? $this->birthplace_province_acronym : self::PROVINCE_NO_RESIDENTS;
    }

    /**
     * @return string
     */
    public function getBirthplace(): string
    {
        return $this->isResidenceInItaly() ? $this->birthplace : $this->getCountryName($this->country);
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
                "data" => $this->date_of_birth->toArray(),
                "comune" => $this->getBirthplace(),
                "provincia" => $this->getBirthplaceProvinceAcronym()
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Date\n{$this->date_of_birth->toString()}\n".
           "BirthPlace: {$this->birthplace}\n".
           "Birthplace Province Acr: {$this->birthplace_province_acronym}";
    }
}