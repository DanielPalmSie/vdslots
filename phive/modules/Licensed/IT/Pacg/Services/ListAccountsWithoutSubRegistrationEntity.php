<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\DateType;

/**
 * Class ListAccountsWithoutSubRegistrationEntity
 * @package IT\Pacg\Services
 */
class ListAccountsWithoutSubRegistrationEntity extends PacgService
{
    public $date_request;
    public $status;
    public $start;
    public $end;

    protected $fillable = [
        'date_request',
        'status',
        'start',
        'end'
    ];

    protected $rules = [
        'date_request' => 'required|array',
        'status' => 'required',
        'start' => 'required',
        'end' => 'required',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->date_request)) {
            $this->date_request = (new DateType())->fill($this->date_request);
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
            "dataRichiesta" => $this->date_request->toArray(),
            "stato"         => $this->status,
            "inizio"        => $this->start,
            "fine"          => $this->end,

        ];

        return parent::toArray($values);
    }
}