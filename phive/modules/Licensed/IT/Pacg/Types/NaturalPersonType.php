<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of a natural person
 * Class NaturalPersonType
 */
class NaturalPersonType extends AbstractEntity
{
    public $tax_code;
    public $surname;
    public $name;
    public $gender;
    public $email;
    public $pseudonym;
    public $birth_data;
    public $residence;
    public $document;

    protected $fillable = [
        'tax_code',
        'surname',
        'name',
        'gender',
        'email',
        'pseudonym',
        'birth_data',
        'residence',
        'document'
    ];
    
    protected $rules = [
        'tax_code'   => 'required|alpha_num|min:16|max:16',
        'surname'    => 'required|min:1|max:100',
        'name'       => 'required|min:1|max:100',
        'gender'     => 'required|in:F,M',
        'email'      => 'required|email|min:1|max:100',
        'pseudonym'  => 'required|min:1|max:100',
        'birth_data' => 'required|array',
        'residence'  => 'required|array',
        'document'   => 'required|array',
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
        if (is_array($this->residence)) {
            $this->residence = (new ResidenceType())->fill($this->residence);
        }
        if (is_array($this->document)) {
            $this->document = (new DocumentType())->fill($this->document);
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
            "codiceFiscale"     => $this->tax_code,
            "cognome"           => $this->surname,
            "nome"              => $this->name,
            "sesso"             => $this->gender,
            "nascita"           => $this->birth_data->toArray(),
            "residenza"         => $this->residence->toArray(),
            "documento"         => $this->document->toArray(),
            "postaElettronica"  => $this->email,
            "pseudonimo"        => $this->pseudonym
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Tax Code: {$this->tax_code}\n".
           "Name: {$this->name}\n".
           "Surname: {$this->surname}\n".
           "Gender: {$this->gender}\n".
           "Date of birth\n{$this->birth_data->tostring()}\n".
           "Residence\n{$this->residence->tostring()}\n".
           "Document\n{$this->document->tostring()}\n".
           "Email: {$this->email}\n".
           "Pseudonym: {$this->pseudonym}";
    }
}