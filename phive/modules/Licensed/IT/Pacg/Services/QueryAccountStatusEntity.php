<?php
namespace IT\Pacg\Services;

/**
 * Class QueryAccountStatusEntity
 * @package IT\AbstractRequest\Services
 */
class QueryAccountStatusEntity extends PacgService
{
    public $account_code;

    protected $fillable = [
        'account_code'
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
            "codiceConto" => $this->account_code,
        ];

        return parent::toArray($values);
    }

}