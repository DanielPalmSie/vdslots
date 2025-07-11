<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\LegalEntityType;

/**
 * Class OpenAccountLegalV26Entity
 * @package IT\Services
 */
class OpenAccountLegalV26Entity extends PacgService
{
    public $account_code;
    public $account_holder;
    public $account_type;

    protected $fillable = [
        'account_code',
        'account_holder',
        'account_type'
    ];

    protected $rules = [
        'account_code' => 'required',
        'account_holder' => 'required|array',
        'account_type' => 'required|legal_entity_account_type',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->account_holder)) {
            $this->account_holder = (new LegalEntityType())->fill($this->account_holder);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "titolareConto" => $this->account_holder->toArray(),
            "codiceConto" => $this->account_code,
            "tipoContoPersonaGiuridica" => $this->account_type,
        ];

        return parent::toArray($values);
    }

}
