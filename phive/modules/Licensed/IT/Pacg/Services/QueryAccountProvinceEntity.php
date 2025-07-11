<?php
namespace IT\Pacg\Services;

/**
 * Class QueryAccountProvinceEntity
 * @package IT\Pacg\Services
 */
class QueryAccountProvinceEntity extends PacgService
{
    public $account_code;

    protected $fillable = [
        'account_code',
    ];

    protected $rules = [
        'account_code' => 'required',
    ];

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto"      => $this->account_code,
        ];

        return parent::toArray($values);
    }

}