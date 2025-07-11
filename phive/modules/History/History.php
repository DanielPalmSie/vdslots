<?php //phpcs:ignore PSR1.Files.SideEffects -- import on line 7

declare(strict_types=1);

use History\HistoryRecorder;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\HistoryMessageInterface;

require_once __DIR__ . '/../../api/PhModule.php';

/**
 * @noinspection PhpIllegalPsrClassPathInspection
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration
class History extends PhModule
{
    protected bool $enabled;

    /**
     * @var HistoryRecorder[]
     */
    protected array $recorders = [];

    /**
     * Check if module is enabled
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (! isset($this->enabled)) {
            $this->enabled = $this->getSetting('enabled', false);
        }

        return $this->enabled;
    }

    /**
     * @param string $topic
     * @param HistoryMessageInterface $data
     * @param string|null $key
     * @param array|null $context
     *
     * @return bool
     */
    public function addRecord(string $topic, HistoryMessageInterface $data, ?string $key = null, ?array $context = null): bool
    {
        if ($this->isEnabled()) {
            $logger = phive('Logger')->getLogger('history_message');
            foreach ($this->getRecorders($topic, $context) as $recorder) {
                try {
                    $recorder->addRecord($topic, $data, $key, $context);
                } catch (\Exception $e) {
                    $dataArr = $data->toArray();
                    $logger->error($e->getMessage(), [
                        'topic'     => $topic,
                        'data'      => $dataArr,
                        'key'       => $key,
                        'context'   => $context,
                        'exception' => get_class($e),
                    ]);
                    $this->storeFailedMessage($topic, $dataArr, $context, get_class($data));
                }
            }
        }

        return true;
    }

    /**
     * @param string $topic
     * @param array|null $headers
     * @return array
     */
    public function getRecorders(string $topic, ?array $headers = null): array
    {
        $result = [];

        $settings = $this->getSetting('recorders', []);

        if (isset($settings[$topic])) {
            $result += $this->processConfig($settings[$topic], $result, $headers);
        }

        //an always recorder
        if (isset($settings['*'])) {
            $result += $this->processConfig($settings['*'], $result, $headers);
        }

        //a fallback catch-all
        if (! $result && isset($settings['default'])) {
            $result += $this->processConfig($settings['default'], $result, $headers);
        }

        return $result;
    }

    protected function processConfig(array $config_list, array $result, ?array $headers = null): array
    {
        foreach ($config_list as $config) {
            $hash = sha1(json_encode($config));
            if (isset($result[$hash])) {
                continue;
            }

            if ($this->isApplicable($config, $headers)) {
                $result[$hash] = $this->getRecorder($config);
            }
        }

        return $result;
    }

    /**
     * Checks if the headers pass the applicability rules of certain Recorder config
     * @param array $config
     * @param array|null $headers
     * @return bool
     */
    protected function isApplicable(array $config, ?array $headers = null): bool
    {
        if (empty($config['_only'])) {
            return true;
        }

        foreach ($config['_only'] as $header => $rules) {
            if (is_string($rules) && $rules !== ($headers[$header] ?? null)) {
                return false;
            }
            if (is_array($rules) && ! in_array($headers[$header] ?? null, $rules)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $config
     * @return HistoryRecorder
     */
    protected function getRecorder(array $config): HistoryRecorder
    {
        $hash = sha1(json_encode($config));
        if (! $this->recorders[$hash] && class_exists($config['_class'])) {
            $recorder = new $config['_class']($config);
            $this->recorders[$hash] = $recorder;
        }

        return $this->recorders[$hash];
    }

    /**
     * @param string $topic
     * @param array $message
     * @param array $context
     * @param string $class
     * @return bool
     */
    public function storeFailedMessage(string $topic, array $message, array $context, string $class): bool
    {
        return (bool) phive('SQL')->insertArray('failed_messages', [
            'topic'      => $topic,
            'message'    => json_encode($message, JSON_THROW_ON_ERROR),
            'context'    => json_encode($context, JSON_THROW_ON_ERROR),
            'class'      => $class,
            'updated_at' => phive()->hisNow(),
            'created_at' => phive()->hisNow(),
        ]);
    }

    /**
     * Republish all of the messages from failed_messages table. After a message have been republished it is deleted
     * from the table. If it fails again, it will be added to the table by addRecord method.
     *
     * @throws JsonException
     */
    public function republishFailedMessages(): void
    {
        $db = phive('SQL');
        $failedMessages = $db->loadArray(
            'SELECT * FROM failed_messages'
        );

        foreach ($failedMessages as $failedMessage) {
            $message = json_decode($failedMessage['message'], true, 512, JSON_THROW_ON_ERROR);
            $user = cu($message['user_id'] ?? '');
            $context = json_decode($failedMessage['context'], true, 512, JSON_THROW_ON_ERROR);

            try {
                lic('addRecordToHistory',
                    [
                        $failedMessage['topic'],
                        new $failedMessage['class']($message),
                        $failedMessage['topic'],
                        $context,
                    ],
                    $user
                );
            } catch (InvalidMessageDataException $e) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Invalid message data on republishFailedMessages", [
                        'report_type' => $failedMessage['topic'],
                        'args' => $message,
                        'validation_errors' => $e->getErrors(),
                        'user_id' => $message['user_id']
                    ]);

            }

            $db->delete('failed_messages', ['id' => $failedMessage['id']]);
        }

    }
}
