<?php
namespace IT\Pacg\Services;

/**
 * Class UpdateAccountPseudonymEntity
 * @package IT\Pacg\Services
 */
class UpdateAccountPseudonymEntity extends PacgService
{
    public $account_code;
    public $pseudonym;

    protected $fillable = [
        'account_code',
        'pseudonym'
    ];

    protected $rules = [
        'account_code' => 'required',
        'pseudonym' => 'required',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto" => $this->account_code,
            "pseudonimo" => $this->pseudonym
        ];

        return parent::toArray($values);
    }
}