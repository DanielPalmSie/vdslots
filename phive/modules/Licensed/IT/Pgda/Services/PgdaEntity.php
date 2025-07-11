<?php
namespace IT\Pgda\Services;

use IT\Abstractions\AbstractEntity;

class PgdaEntity extends AbstractEntity
{
    protected $header = [];
    protected $format = "";

    /**
     * @return array
     */
    private function getRequestInformation(): array
    {
        return [
            'Cod_gioco' => $this->game_code,
            'Cod_tipo_gioco' => $this->game_type
        ];
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        $this->header = $this->getRequestInformation();
        return [
            "header" => $this->header,
            "body" => $array,
            "format" => $this->format
        ];
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }
}