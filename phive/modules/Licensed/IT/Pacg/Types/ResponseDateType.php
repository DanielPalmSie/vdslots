<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class ResponseDateType
 * @package IT\Pacg\Types
 */
class ResponseDateType extends AbstractEntity
{
    protected $fillable = [
        'giorno',
        'mese',
        'anno',
    ];

    protected $rules = [
        'giorno' => 'required|date:d',
        'mese'   => 'required|date:m',
        'anno'   => 'required|date:Y',
    ];

    /**
     * @return string
     */
    public function getDate()
    {
        return "{$this->anno}-{$this->mese}-{$this->giorno}";
    }

    /**
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return [
            "date"   => $this->getDate()
        ];
    }

}