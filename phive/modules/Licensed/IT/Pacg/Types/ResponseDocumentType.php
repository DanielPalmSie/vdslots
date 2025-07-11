<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Structure for the representation of a document
 * Class ResponseDocumentType
 */
class ResponseDocumentType extends AbstractEntity
{
    protected $fillable = [
        'tipo',
        'numero',
        'dataRilascio',
        'autoritaRilascio',
        'localitaRilascio'
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        if (is_array($this->dataRilascio)) {
            $this->dataRilascio = (new ResponseDateType())->fill($this->dataRilascio);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "document_type"     => $this->tipo,
            "document_number"   => $this->numero,
            "date_of_issue"     => $this->dataRilascio->getDate(),
            "issuing_authority" => $this->autoritaRilascio,
            "where_issued"      => $this->localitaRilascio
        ];
    }

}