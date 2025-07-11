<?php
namespace IT\Abstractions;
use Monolog\Logger as MonologLogger;

/**
 * Class AbstractRequest
 */
abstract class AbstractRequest
{
    /**
     * @var InterfaceClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $request_return;

    /**
     * @var Logger $logger
     */
    protected $logger;


    /**
     * key name that contain sensitive PII data and must unset before send log
     * @var string[]
     */
    protected $sensitive_key;
    /**
     * AbstractRequest constructor.
     * @param InterfaceClient $client
     * @param array $settings
     */
    public function __construct(InterfaceClient $client, array $settings = [])
    {
        $this->client = $client;
        $this->setSetting($settings, $this->request_code);

        // initialize the logger
        $this->logger = phive('Logger')->getLogger($this->client->logger_name);
        $this->sensitive_key = licSetting('sensitive_key');
    }

    /**
     * @return \stdClass|array|string
     */
    public function getResult()
    {
        return $this->request_return;
    }

    /**
     * @param array $model
     * @return AbstractResponse
     * @throws \Exception
     */
    public function execute(array $model): AbstractResponse
    {
        try {
            $start_time = microtime(true);
            $this->request_return = $this->client->exec($model);
            $response = $this->getResponse($this);
            $this->saveLog($this->client->getPayloadRequest(), $response->getResponseBody(), $response->getCode(), $start_time);
            return $response;
        } catch (\Exception $exception) {
            $this->saveLog(
                $this->client->getPayloadRequest(),
                [$exception->getMessage()],
                $exception->getCode(),
                $start_time,
                $exception->getTrace(),
                MonologLogger::ERROR
            );

            throw $exception;
        }
    }

    /**
     * Saves logs to Monolog, for now we are saving logs in two levels,
     * DEBUG for normal/success use-cases
     * ERROR for Exceptions
     * @param array $payload
     * @param array $response_body
     * @param int|null $response_code
     * @param float $start_time
     * @param array $log_trace
     * @param int $level
     * @return void
     */
    protected function saveLog(
        array $payload,
        array $response_body,
        ?int $response_code,
        float $start_time,
        array $log_trace = [],
        $level = 0
    ): void {
        switch ($this->client->logger_name){
            case "pacg_adm":
                $loggerName = "Pacg";
                break;
            case "pgda_adm":
                $loggerName = "Pgda";
                break;
            default:
                $loggerName = "sogei";
                break;
        }
        //db logging
        $user_id = 0;
        array_walk_recursive($payload, function($item, $key) use (&$user_id) {
            if ($key == 'codiceConto') {
                $user_id = $item;
            }
        });
        $reqId=phive()->uuid();

        phive()->externalAuditTbl(
            $loggerName,
            json_encode($payload),
            json_encode($response_body),
            (microtime(true) - $start_time),
            $response_code,
            $reqId,
            0,
            $user_id
        );
        //file logging
        $this->logger->log(
            $this->requestName(),
            [
                'request'       => $this->piiFinder($payload),
                'request_id'    => $reqId,
                'user_id'       => $user_id,
            ],
            $level
        );

        $this->logger->log(
            $this->responseName(),
            [
                'response'      => $response_body,
                'response_time' => (microtime(true) - $start_time),
                'status_code'   => $response_code,
                'request_id'    => $reqId,
                'user_id'       => $user_id,
                'stack_trace'   => $log_trace
            ],
            $level
        );
    }

    /**
     * @param AbstractRequest $response_return
     * @return AbstractResponse
     */
    protected function getResponse(AbstractRequest $response_return): AbstractResponse
    {
        $response_name = $this->responseName();
        return new $response_name($response_return);
    }

    /**
     * @param AbstractEntity $entity
     * @return AbstractResponse
     * @throws \Exception
     */
    public function request(AbstractEntity $entity): AbstractResponse
    {
        return $this->execute($this->setCommonAttributes($entity->toArray()));
    }

    /**
     * Search request and response to find Sensitive data
     * @param $msgArray
     * @return array
     */
    function piiFinder(&$msgArray)
    {
        foreach ($this->sensitive_key as $sk){
            $full_path=explode('.',$sk);
            $temp = &$msgArray;
            foreach($full_path as $path){
                $temp = &$temp[$path];
            }
            $temp =null;
        }

        return $msgArray;
    }

    /**
     * @return string
     */
    abstract public function responseName(): string;

    /**
     * @param array $settings
     * @return void
     */
    abstract protected function setSetting(array $settings = [], $request_code = 0);

    /**
     * @param array $data
     * @return array
     */
    abstract protected function setCommonAttributes(array $data): array;

    /**
     * Request package name
     * @return string
     */
    abstract public function requestName(): string;
}
