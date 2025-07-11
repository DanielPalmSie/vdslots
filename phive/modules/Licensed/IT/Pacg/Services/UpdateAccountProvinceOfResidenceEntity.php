<?php
namespace IT\Pacg\Services;

/**
 * Class UpdateAccountProvinceOfResidenceEntity
 * @package IT\Pacg\Services
 */
class UpdateAccountProvinceOfResidenceEntity extends PacgService
{
    public $account_code;
    public $province;

    protected $fillable = [
        'account_code',
        'province',
    ];

    protected $rules = [
        'account_code'   => 'required',
        'province'       => 'required',
    ];

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto"      => $this->account_code,
            "provincia"        => $this->province,
        ];

        return parent::toArray($values);
    }
}