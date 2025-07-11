<?php
namespace IT\Pacg\Services;

/**
 * Class UpdateAccountStatusEntity
 * @package IT\Pacg\Services
 */
class UpdateAccountStatusEntity extends PacgService
{
    public $account_code;
    public $status;
    public $reason;

    protected $fillable = [
        'account_code',
        'status',
        'reason'
    ];

    protected $rules = [
        'account_code' => 'required',
        'status' => 'required',
        'reason' => 'required',
    ];

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceConto" => $this->account_code,
            "stato" => $this->status,
            "causale" => $this->reason
        ];

        return parent::toArray($values);
    }
}