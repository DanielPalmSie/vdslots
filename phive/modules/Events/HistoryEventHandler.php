<?php

use longlang\phpkafka\Exception\ConnectionException;
use longlang\phpkafka\Exception\KafkaErrorException;
use longlang\phpkafka\Exception\SocketException;

class HistoryEventHandler
{
    private History $history;
    private Logger $logger;

    public function __construct()
    {
        $this->history = phive('History');
        $this->logger = phive('Logger')->getLogger('history_message');
    }

    /**
     * Send a message to history events server.
     *
     * @param string $topic
     * @param array $data
     * @param string $history_class
     * @param string|null $key
     * @param array|null $context
     * @return bool
     */
    public function onHistoryAddRecordToHistory(string $topic, array $data, string $history_class, ?string $key = null, ?array $context = null): bool
    {
        $counter = 0;
        $numberOfTrials = 5;

        do {
            try {
                $dataObject = new $history_class($data);
                $this->history->addRecord($topic, $dataObject, $key, $context);

                return true;
            } catch (SocketException | ConnectionException | KafkaErrorException $e) {
                $counter++;
                if ($counter === $numberOfTrials) {
                    $this->logger->error(
                        __METHOD__ . ' ' . $e->getMessage(),
                        [
                            'topic' => $topic,
                            'data' => $data,
                            'key' => $key,
                            'context' => $context,
                            'exception' => get_class($e),
                        ]
                    );
                    $this->history->storeFailedMessage($topic, $data, $context, $history_class);

                    return false;
                }

                sleep($counter);
            } catch (\Exception $e) {
                $this->logger->error(
                    __METHOD__ . ' ' . $e->getMessage(),
                    [
                        'topic' => $topic,
                        'data' => $data,
                        'key' => $key,
                        'context' => $context,
                        'exception' => get_class($e),
                    ]
                );
                $this->history->storeFailedMessage($topic, $data, $context, $history_class);

                return false;
            }
        } while ($counter < $numberOfTrials);

        return true;
    }
}
