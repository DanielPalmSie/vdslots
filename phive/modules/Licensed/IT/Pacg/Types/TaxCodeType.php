<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class TaxCodeType
 * @package IT\Pacg\Types
 */
class TaxCodeType extends AbstractEntity
{
    public $name;
    public $surname;
    public $birthDate;
    public $gender;
    public $registryCode;

    protected $fillable = [
        "name",
        "surname",
        "birthDate",
        "gender",
        "registryCode"
    ];

    protected $rules = [
        "name" => "required|min:1|max:100",
        "surname" => "required|min:1|max:100",
        "birthDate" => 'required|min:10|max:10',
        "gender" => 'required|in:F,M',
        "registryCode" => 'required|min:4|max:4',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "name" => $this->name,
            "surname" => $this->surname,
            "birthDate" => $this->birthDate,
            "gender" => $this->gender,
            "registryCode" => $this->registryCode,
        ];
    }
}