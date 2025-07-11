<?php
namespace IT\Pacg\Types;
use IT\Abstractions\AbstractEntity;

/**
 * Class SelfExcludedSubjectDetailType
 * @package IT\Pacg\Types
 */
class SelfExcludedSubjectDetailType extends AbstractEntity
{
    public $type_of_self_exclusion;
    public $start_date;
    public $end_date;

    protected $fillable = [
        'type_of_self_exclusion',
        'start_date',
        'end_date',
    ];

    protected $rules = [
        'type_of_self_exclusion' => 'required|self_exclusion_type',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [

        ];
    }
}
