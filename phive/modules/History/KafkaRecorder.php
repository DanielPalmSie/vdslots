<?php

declare(strict_types=1);

namespace History;

use History\phpkafka\SaslClient;
use JsonException;
use longlang\phpkafka\Exception\ConnectionException;
use longlang\phpkafka\Exception\KafkaErrorException;
use longlang\phpkafka\Exception\SocketException;
use longlang\phpkafka\Producer\Producer;
use longlang\phpkafka\Producer\ProducerConfig;
use Videoslots\HistoryMessages\HistoryMessageInterface;

class KafkaRecorder implements HistoryRecorder
{
    protected Producer $producer;
    protected ?int $partition;
    protected array $topic_map;
    protected string $data_format;

    /**
     * Recorder specific fields for config:
     * [
     *  'broker' => string, required, bootstrap server config as "host:domain,..."
     *  'ssl' => array, optional, data required for connections with client/server certificates
     *           [  'open' => true to enable using certificates,
     *              'certFile' => path to cert in pem format,
     *              'keyFile' => path to key in pem format,
     *              'passphrase' => optional string for key password,
     *              'allowSelfSigned' => optional bool for cert validation,
     *              'cafile' => optional path for cert validation,
     *              'capath' => optional path for cert validation,
     *          ]
     *  'sasl' => array, optional, fields needed for SASL
     *          [ 'type' => Class name of the connector to use, like VSAwsMskIamSasl::class
     *              ... any other key needed by the connector
     *          ]
     *  'data_format' => optional, 'JSON' or 'AVRO', for the serialization format
     *  'topic_map' => optional array, to convert topic names
     *  'partition' => optional int for kafka partitioning
     * ]
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        $k_config = new ProducerConfig();
        $k_config->setAcks(-1);
        $k_config->setBootstrapServer($config['broker']);
        $k_config->setUpdateBrokers(true);
        if (isset($config['ssl']) && is_array($config['ssl'])) {
            $k_config->setSsl($config['ssl']);
        }
        if (isset($config['sasl']) && is_array($config['sasl'])) {
            $k_config->setClient(SaslClient::class); //we need this one in case it's not SASL/PLAIN
            $k_config->setSasl($config['sasl']);
        }

        $this->producer = new Producer($k_config);
        $this->partition = isset($config['partition']) ? (int) $config['partition'] : null;
        $this->topic_map = $config['topic_map'] ?? [];
        $this->data_format = $config['data_format'] ?? 'JSON';
    }

    /**
     * @param  string  $topic
     * @param  HistoryMessageInterface  $data
     * @param  string|null  $key
     * @param  array|null  $context
     *
     * @return bool
     * @throws JsonException|SocketException|ConnectionException|KafkaErrorException
     */
    public function addRecord(string $topic, HistoryMessageInterface $data, ?string $key = null, ?array $context = null): bool
    {
        $topic = $this->getTopicName($topic);

        //a transformation can disable a topic by returning ''
        if (!$topic) {
            return true;
        }

        switch ($this->data_format) {
            case 'AVRO':
                $value = $data->toAvro();
                break;
            case 'JSON':
            default:
                $value = $data->toJson();
                break;
        }

        $counter = 0;
        $numberOfTrials = 5;

        if(is_array($context)){
            foreach($context as $i=>$v){
                if(is_array($v)){
                    $v = json_encode($v);
                }else{
                    $v = (string) $v;
                }
                $context[$i] = $v;
            }
        }

        do {
            try {
                $this->producer->send($topic, $value, $key, $context ?? [], $this->partition);
                return true;
            } catch (SocketException | ConnectionException | KafkaErrorException $e) {
                $counter++;

                if ($counter === $numberOfTrials) {
                    throw $e;
                }

                sleep($counter);
            }
        } while ($counter < $numberOfTrials);

        return false;
    }

    /**
     * Map topic names
     * @param  string  $topic
     * @return string
     */
    private function getTopicName(string $topic): string
    {
        $found = $this->topic_map[$topic] ?? $this->topic_map['default'] ?? $topic;

        if (is_callable($found)) {
            $found = $found($topic);
        }

        return $found;
    }
}
