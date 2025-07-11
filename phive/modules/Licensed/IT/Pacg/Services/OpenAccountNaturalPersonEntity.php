<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\LimitListType;
use IT\Pacg\Types\NaturalPersonType;

/**
 * Class OpenAccountNaturalPersonEntity
 * @package IT\Pacg\Services
 */
class OpenAccountNaturalPersonEntity extends PacgService
{
    public $account_code;
    public $account_holder;
    public $limits;

    protected $fillable = [
        'account_code',
        'account_holder',
        'limits',
    ];

    protected $rules = [
        'account_code'       => 'required|alpha_num|min:1|max:20',
        'account_holder'     => 'required|array',
        'limits'             => 'required|array',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->account_holder)) {
            $this->account_holder = (new NaturalPersonType())->fill($this->account_holder);
        }

        if (is_array($this->limits)) {
            $this->limits = (new LimitListType())->fill(['limits' => $this->limits]);
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
            "codiceConto"   => $this->account_code,
            "titolareConto" => $this->account_holder->toArray(),
            "numeroLimiti"  => $this->limits->getNumberOfLimits(),
            "limite"        => $this->limits->toArray(),
        ];

        return parent::toArray($values);
    }
}
