<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of a document
 * Class ResponseAccountType
 */
class ResponseAccountType extends AbstractEntity
{
    /**
     * codiceConto AKA user_id
     *
     * @var string
     */
    public $codiceConto;

    /**
     * @var integer
     */
    public $importoSaldo;

    /**
     * @var integer
     */
    public $tipoAutoesclusione;

    /**
     * @var ResponseDateType
     */
    public $dataInizio;

    /**
     * @var ResponseDateType
     */
    public $dataFine;

    /**
     * @var array
     */
    protected $fillable = [
        'codiceConto',
        'importoSaldo',
        'tipoAutoesclusione',
        'dataInizio',
        'dataFine'
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        if (is_array($this->dataInizio)) {
            $this->dataInizio = (new ResponseDateTimeType())->fill($this->dataInizio);
        }
        if (is_array($this->dataFine)) {
            $this->dataFine = (new ResponseDateTimeType())->fill($this->dataFine);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $array = [
            'user_id' => $this->codiceConto,
        ];

        if(!empty($this->importoSaldo)) {
            $array['balance'] = $this->importoSaldo;
        }
        if(!empty($this->tipoAutoesclusione)) {
            $array['type_of_self_exclusion'] = $this->tipoAutoesclusione;
        }
        if(!empty($this->dataInizio)) {
            $array['start_date'] = $this->dataInizio->getDateTime();
        }
        if(!empty($this->dataFine)) {
            $array['end_date'] = $this->dataFine->getDateTime();
        }

        return $array;
    }

}