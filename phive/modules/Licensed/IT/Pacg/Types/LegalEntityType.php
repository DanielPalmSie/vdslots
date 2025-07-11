<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of a legal entity
 * Class LegalEntityType
 */
class LegalEntityType extends AbstractEntity
{
    public $vat_number;
    public $company_name;
    public $email;
    public $company_headquarter;
    public $pseudonym;

    protected $fillable = [
        'vat_number',
        'company_name',
        'email',
        'company_headquarter',
        'pseudonym',
    ];

    protected $rules = [
        'vat_number' => 'required|min:11|max:11',
        'company_name' => 'required|min:1|max:100',
        'email' => 'required|email|min:1|max:100',
        'company_headquarter' => 'required',
        'pseudonym' => 'required|min:1|max:100',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->company_headquarter)) {
            $this->company_headquarter = (new ResidenceType())->fill($this->company_headquarter);
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
            "partitaIva" => $this->vat_number,
            "ragioneSociale" => $this->company_name,
            "sede" => $this->company_headquarter->toArray(),
            "postaElettronica" => $this->email,
            "pseudonimo" => $this->pseudonym,
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Vat Number: {$this->vat_number}\n".
           "Company Name: {$this->company_name}\n".
           "HeadQuarter\n{$this->company_headquarter->toString()}\n".
           "Email: {$this->email}\n".
           "Pseudonym: {$this->pseudonym}";
    }
}