<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\DocumentType;

/**
 * Class UpdatingOwnerIdDocumentDetailsEntity
 * @package IT\Pacg\Services
 */
class UpdatingOwnerIdDocumentDetailsEntity extends PacgService
{
    public $account_code;
    public $document;

    protected $fillable = [
        'account_code',
        'document',
    ];

    protected $rules = [
        'account_code' => 'required',
        'document' => 'required|array',
    ];


    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->document)) {
            $this->document = (new DocumentType())->fill($this->document);
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
            "codiceConto" => $this->account_code,
            "documento" => $this->document->toArray()
        ];

        return parent::toArray($values);
    }
}