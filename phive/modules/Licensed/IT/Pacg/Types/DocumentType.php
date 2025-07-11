<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

require_once 'DateType.php';

/**
 * Structure for the representation of a document
 * Class DocumentType
 */
class DocumentType extends AbstractEntity
{
    public $document_type;
    public $document_number;
    public $issuing_authority;
    public $where_issued;
    public $date_of_issue;

    protected $fillable = [
        'document_type',    // renamed from typology
        'document_number',
        'issuing_authority',
        'where_issued',
        'date_of_issue'
    ];

    protected $rules = [
        'document_type'     => 'required|document_type',
        'document_number'   => 'required|alpha_num|min:1|max:20',
        'issuing_authority' => 'required|alpha_spaces|min:1|max:100',
        'where_issued'      => 'required|min:1|max:100|regex:/^[\pL\pM\pN\s]+$/u',
        'date_of_issue'     => 'required|array'
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);
        if (is_array($this->date_of_issue)) {
            $this->date_of_issue = (new DateType())->fill($this->date_of_issue);
        }

        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "tipo"             => $this->document_type,
            "numero"           => $this->document_number,
            "dataRilascio"     => $this->date_of_issue->toArray(),
            "autoritaRilascio" => $this->issuing_authority,
            "localitaRilascio" => $this->where_issued
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return
           "Type: {$this->typology}\n" .
           "Date of Issue\n{$this->date_of_issue->toString()}\n" .
           "Number: {$this->document_number}\n" .
           "Issuing Authority: {$this->issuing_authority}\n" .
           "Where issued: {$this->where_issued}\n";
    }
}
