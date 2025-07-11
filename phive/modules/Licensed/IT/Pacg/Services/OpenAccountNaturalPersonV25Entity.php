<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Tables\PersonalDataOriginType;
use IT\Pacg\Types\LimitListType;
use IT\Pacg\Types\NaturalPersonType;

/**
 * Class OpenAccountNaturalPersonEntity
 * @package IT\Pacg\Services
 */
class OpenAccountNaturalPersonV25Entity extends PacgService
{
    /**
     * Allowed Personal Data Origin limit type
     */
    public $account_code;
    public $account_holder;
    public $limits;
    public $personal_data_origin_type;


    protected $fillable = [
        'account_code',
        'account_holder',
        'personal_data_origin_type',
        'limits',
    ];

    protected $rules = [
        'account_code'       => 'required|alpha_num|min:1|max:20',
        'account_holder'     => 'required|array',
        'personal_data_origin_type' => 'required|integer',
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

        if (empty($this->personal_data_origin_type)) {
            $this->personal_data_origin_type = PersonalDataOriginType::$manual;
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
            "tipoFornituraDatiPersonali" => $this->personal_data_origin_type,
            "numeroLimiti"  => $this->limits->getNumberOfLimits(),
            "limite"        => $this->limits->toArray(),
        ];

        return parent::toArray($values);
    }
}
