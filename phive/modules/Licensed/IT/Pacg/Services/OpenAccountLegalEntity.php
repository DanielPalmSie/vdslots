<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\LegalEntityType;

/**
 * Class OpenAccountLegalEntity
 * @package IT\Services
 */
class OpenAccountLegalEntity extends PacgService
{
    public $account_code;
    public $account_holder;

    protected $fillable = [
        'account_code',
        'account_holder'
    ];

    protected $rules = [
        'account_code' => 'required',
        'account_holder' => 'required|array',
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
        ];

        return parent::toArray($values);
    }

}