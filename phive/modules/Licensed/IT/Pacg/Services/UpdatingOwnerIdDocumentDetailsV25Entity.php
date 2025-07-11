<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Tables\PersonalDataOriginType;
use IT\Pacg\Types\DocumentType;

/**
 * Class UpdatingOwnerIdDocumentDetailsEntity
 * @package IT\Pacg\Services
 */
class UpdatingOwnerIdDocumentDetailsV25Entity extends PacgService
{
    public $account_code;
    public $document;
    public $personal_data_origin_type;


    protected $fillable = [
        'account_code',
        'document',
        'personal_data_origin_type',
    ];

    protected $rules = [
        'account_code' => 'required',
        'document' => 'required|array',
        'personal_data_origin_type' => 'required|integer',
    ];


    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        if (empty($this->personal_data_origin_type)) {
            $this->personal_data_origin_type = PersonalDataOriginType::$manual;
        }

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
            "documento" => $this->document->toArray(),
            "tipoFornituraDatiPersonali" => $this->personal_data_origin_type,
        ];

        return parent::toArray($values);
    }
}