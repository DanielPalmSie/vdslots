<?php

declare(strict_types=1);

namespace History;

use Videoslots\HistoryMessages\HistoryMessageInterface;

interface HistoryRecorder
{
    public function __construct(array $config);

    /**
     * @param string $topic
     * @param HistoryMessageInterface $data
     * @param string|null $key
     * @param array|null $context
     *
     * @return bool
     */
    public function addRecord(string $topic, HistoryMessageInterface $data, ?string $key = null, ?array $context = null): bool;
}
