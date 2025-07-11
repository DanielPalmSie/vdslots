<?php

require_once __DIR__ . '/BaseResponse.php';

class DeathChangesResponse extends BaseResponse
{
    public string $response_key = 'cambioDefuncion';
    public string $change_date_key = 'fechaCambio';

    /**
     * @inheritDoc
     * @override
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->exists;
    }

    /**
     * Get change date of RGIAJ
     *
     * @return string|null
     */
    public function getChangeDate(): ?string
    {
        return $this->response[$this->change_date_key] ?? null;
    }
}