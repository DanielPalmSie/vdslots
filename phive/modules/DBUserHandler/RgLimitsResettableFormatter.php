<?php

use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableData;

class RgLimitsResettableFormatter
{
    /**
     * @var \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableData
     */
    private UpdateRgLimitsResettableData $data;

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableData $data
     */
    public function __construct(UpdateRgLimitsResettableData $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getLimitsRequest(): array
    {
        $type = $this->data->getType();

        return [
            $this->formatLimitRequest($this->data->getDay(), $type, 'day'),
            $this->formatLimitRequest($this->data->getWeek(), $type, 'week'),
            $this->formatLimitRequest($this->data->getMonth(), $type, 'month'),
        ];
    }

    /**
     * @param float $limit
     * @param string $type
     * @param string $timeSpan
     *
     * @return array
     */
    private function formatLimitRequest(float $limit, string $type, string $timeSpan): array
    {
        return [
            'limit' => $limit,
            'type' => $type,
            'time_span' => $timeSpan
        ];
    }
}
