<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of a natural person
 * Class NaturalPersonSimplifiedType
 */
class NaturalPersonSimplifiedType extends AbstractEntity
{
    public $tax_code;
    public $surname;
    public $name;
    public $gender;
    public $birth_data;
    public $residential_province_acronym;

    protected $fillable = [
        'tax_code',
        'surname',
        'name',
        'gender',
        'birth_data',
        'residential_province_acronym',
    ];

    protected $rules = [
        'tax_code'                     => 'required|min:16|max:16',
        'surname'                      => 'required|min:1|max:100',
        'name'                         => 'required|min:1|max:100',
        'gender'                       => 'required|in:F,M',
        'birth_data'                   => 'required|array',
        'residential_province_acronym' => 'required|min:2|max:2',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        if (is_array($this->birth_data)) {
            $this->birth_data = (new BirthDataType())->fill($this->birth_data);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "codiceFiscale" => $this->tax_code,
            "cognome"=> $this->surname,
            "nome" => $this->name,
            "sesso" => $this->gender,
            "nascita" => $this->birth_data->toArray(),
            "provResid" => $this->residential_province_acronym
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Tax Code: {$this->tax_code}\n".
           "Name: {$this->surname}\n".
           "Surname: {$this->name}\n".
           "Gender: {$this->gender}\n".
           "Date of Birth: {$this->birth_data->toString()}\n".
           "Prov: {$this->residential_province_acronym}";
    }
}