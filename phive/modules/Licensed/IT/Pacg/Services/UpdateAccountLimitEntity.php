<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\LimitType;

/**
 * Class UpdateAccountLimitEntity
 * @package IT\Pacg\Services
 */
class UpdateAccountLimitEntity extends PacgService
{
    public $account_code;
    public $limit_management;
    public $limit;

    protected $fillable = [
        'account_code',
        'limit_management',
        'limit'
    ];

    protected $rules = [
        'account_code' => 'required',
        'limit_management' => 'required',
        'limit' => 'required|array',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->limit)) {
            $this->limit = (new LimitType())->fill($this->limit);
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
            "codiceConto" => $this->account_code,
            "gestioneLimite" => $this->limit_management,
            "limite" => $this->limit->toArray(),
        ];

        return parent::toArray($values);
    }
}