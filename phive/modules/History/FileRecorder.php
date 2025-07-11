<?php

declare(strict_types=1);

namespace History;

use Videoslots\HistoryMessages\HistoryMessageInterface;

/**
 * This recorder is intended mainly for quick debug of messages, or where the network is not available
 * The format could be parsed to import in another recorder like Kafka
 */
class FileRecorder implements HistoryRecorder
{

    /**
     * @var false|resource
     */
    private $recorder;

    /**
     * @param  array  $config  ["filename": string, dir path must exist]
     */
    public function __construct(array $config)
    {
        //keep open the file avoid to extra cost in case of multiple messages per request
        $this->recorder = fopen($config['filename'], 'ab');
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    public function addRecord(string $topic, HistoryMessageInterface $data, ?string $key = null, ?array $context = null): bool
    {
        $message = date('[Y-m-d H:i:s] ');
        $message .= get_class($data).':'.$data->toJson();
        if ($key) {
            $message .= "|@|key:({$key})";
        }
        if (!is_null($context)) {
            $message .= '|@|headers:'.json_encode($context, JSON_THROW_ON_ERROR);
        }
        return (bool) fwrite($this->recorder, $message.PHP_EOL);
    }
}
