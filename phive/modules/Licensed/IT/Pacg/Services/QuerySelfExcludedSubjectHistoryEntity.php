<?php
namespace IT\Pacg\Services;

/**
 * Class QuerySelfExcludedSubjectHistoryEntity
 * @package IT\Pacg\Services
 */
class QuerySelfExcludedSubjectHistoryEntity extends PacgService
{
    public $tax_code;

    protected $fillable = [
        'tax_code'
    ];

    protected $rules = [
        'tax_code' => 'required',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "codiceFiscale" => $this->tax_code
        ];

        return parent::toArray($values);
    }
}