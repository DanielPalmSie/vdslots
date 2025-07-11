<?php
namespace IT\Pacg\Services;

/**
 * Class UpdateEmailAccountEntity
 * @package IT\Pacg\Services
 */
class UpdateEmailAccountEntity extends PacgService
{
    public $account_code;
    public $email;

    protected $fillable = [
        'account_code',
        'email',
    ];

    protected $rules = [
        'account_code' => 'required',
        'email' => 'required|email',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto"      => $this->account_code,
            "postaElettronica" => $this->email
        ];

        return parent::toArray($values);
    }
}