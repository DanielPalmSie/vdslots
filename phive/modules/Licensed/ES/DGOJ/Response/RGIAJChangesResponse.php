<?php

require_once __DIR__ . '/BaseResponse.php';

class RGIAJChangesResponse extends BaseResponse
{
    public string $response_key = 'cambioRGIAJ';
    public string $change_reason_key = 'motivoCambio';
    public string $change_date_key = 'fechaCambio';

    public const CHANGE_REASON_ADDED = 'A';
    public const CHANGE_REASON_REMOVED = 'B';

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
     * Get change reason of RGIAJ
     *
     * @return string|null
     */
    public function getChangeReason(): ?string
    {
        return $this->response[$this->change_reason_key] ?? null;
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