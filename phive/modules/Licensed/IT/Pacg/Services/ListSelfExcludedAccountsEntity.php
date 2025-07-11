<?php
namespace IT\Pacg\Services;

/**
 * Class ListSelfExcludedAccountsEntity
 * @package IT\Pacg\Services
 */
class ListSelfExcludedAccountsEntity extends PacgService
{
    public $start;
    public $end;

    protected $fillable = [
        'start',
        'end'
    ];

    protected $rules = [
        'start' => 'required',
        'end' => 'required',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "inizio" => $this->start,
            "fine" => $this->end,
        ];

        return parent::toArray($values);
    }
}