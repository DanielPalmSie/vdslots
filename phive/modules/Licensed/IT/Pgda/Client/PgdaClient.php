<?php
namespace IT\Pgda\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleRetry\GuzzleRetryMiddleware;
use IT\Abstractions\InterfaceClient;
use IT\Traits\BinaryTrait;
use IT\Traits\PKCSTrait;
use Monolog\Logger;
use IT\Services\Traits\InteractWithMail;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PgdaClient
 * @package IT\Pgda\PgdaClient
 */
class PgdaClient extends GuzzleClient implements InterfaceClient
{
    use PKCSTrait;
    use InteractWithMail;

    /**
     * The PGDA Protocol version currently in use
     */
    private const DEFAULT_PROTOCOL_VERSION = 2;
    
    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $certificate_path;

    /**
     * @var string
     */
    private $key_path;

    /**
     * @var bool
     */
    private $sign_message;

    /**
     * @var string
     */
    private $header_format = 'CN4CA4A16N';

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var array
     */
    private array $payload;

    /**
     * @var string
     */
    public string $logger_name = 'pgda_adm';

    /**
     * @param $config
     * @link https://packagist.org/packages/caseyamcl/guzzle_retry_middleware Retry middleware documentation
     * @return array
     */
    private function setHandler($config): array
    {
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());
        $retry_config = $this->config['pgda']['retry_request_options'];

        if($config['pgda']['debug']){
            $stack->push(
                Middleware::log(
                    new Logger('Logger'),
                    new MessageFormatter('{request} - {response}')
                )
            );
        }

        $on_retry_callback = function(int $attemptNumber, float $delay, RequestInterface &$request, array &$options, ?ResponseInterface $response) {
            $log_output = sprintf(
                "ADM: Retrying request to %s. Server responded with %s. Will wait %s seconds. This is attempt #%s.",
                $request->getUri()->getPath(),
                $response instanceof ResponseInterface ? $response->getStatusCode() : 'connection timeout',
                number_format($delay, 2),
                $attemptNumber
            );

            phive('Logger')->getLogger('pgda_adm')->warning("PGDA client exec() retry request", [
                'message' => $log_output,
                'request' => json_encode($this->headers)
            ]);
        };

        $default_retry_multiplier = function($numRequests, ?ResponseInterface $response): float {
            return (float) rand(2, 4);
        };

        $should_retry_callback = function (array $options, ?ResponseInterface $response = null): bool {
            // Response will be NULL in the event of a connection timeout, so your callback function
            // will need to be able to handle that case
            if (! $response) {
                return $options['retry_on_timeout'];
            }

            $is_allowed_http_status_code = in_array($response->getStatusCode(), $options['retry_on_status']);
            $is_allowed_request_code = empty($options['retry_on_adm_request_code']) || in_array($this->headers['Cod_tipo_messaggio'], $options['retry_on_adm_request_code']);

            return $is_allowed_http_status_code && $is_allowed_request_code;
        };

        $config['handler'] = $stack;
        $config['on_retry_callback'] = $on_retry_callback;
        $config['default_retry_multiplier'] = $default_retry_multiplier;
        $config['should_retry_callback'] = $should_retry_callback;
        $config['retry_enabled'] = $retry_config['retry_enabled'];
        $config['retry_on_timeout'] = $retry_config['retry_on_timeout'];
        $config['max_retry_attempts'] = isCli() ? $retry_config['max_retry_attempts_cli'] : $retry_config['max_retry_attempts_web'];
        $config['retry_on_status'] = $retry_config['retry_on_http_status'];
        $config['max_allowable_timeout_secs'] = $retry_config['max_allowable_timeout_secs'];
        $config['give_up_after_secs'] = $retry_config['give_up_after_secs'];
        $config['retry_on_adm_request_code'] = $retry_config['retry_on_adm_request_code'];

        return $config;
    }

    /**
     * PgdaClient constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        if ($config['pgda']['disable_strict_ssl']) {
            parent::__construct($this->setHandler(['url' => $config['pgda']['base_url'], 'verify' => false]));
        } else {
            parent::__construct($this->setHandler(['url' => $config['pgda']['base_url']]));
        }

        $this->setConfigurations($config);
    }

    /**
     * @param array $configurations
     */
    public function setConfigurations(array $configurations)
    {
        $this->headers = [
            'Cod_vers_prot' => $configurations['pgda']['protocol_version'] ?? 2,
            'Cod_fsc' => $configurations['id_fsc'],
            'Cod_conc_trasmittente' => $configurations['id_cn'],
            'Cod_conc_proponente' => $configurations['id_cn'],
        ];
        $this->sign_message = $configurations['pgda']['sign_message'] ?? false;
        $this->certificate_path = $configurations['pgda']['signing_certificate'];
        $this->key_path = $configurations['pgda']['private_key'];
    }

    /**
     * @param array $payload
     * @return false|string
     */
    private function getBody(array $payload)
    {
        return BinaryTrait::convert($payload['body'], $payload['format']);
    }

    /**
     * @param $payload
     * @param $body
     * @return false|string
     */
    private function getHeader($payload, $body)
    {
        $this->headers = array_merge($this->headers, $payload['header']);
        $this->headers['Id_transazione'] = (string) time();
        $this->headers['Lun_body'] =  mb_strlen($body, '8bit') ?? 0;
        return BinaryTrait::convert($this->headers, $this->header_format);
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * @param $message
     * @return Request
     */
    private function getRequest($message): Request
    {
        $req_headers["Content-Type"] = 'text/plain';
        return new Request('POST', $this->url, $req_headers, $message);
    }

    /**
     * @param array $payload
     * @return string|null
     * @throws \Exception
     */
    private function getMessage(array $payload)
    {
        $body = $this->getBody($payload);
        $headers = $this->getHeader($payload, $body);
        $message = $headers . $body;

        return $this->sign_message
            ? $this->envelope($message, $this->config['pgda']['private_key'], $this->config['pgda']['signing_certificate'])
            : $message;
    }

    /**
     * Return data used to compose the request
     * @return array
     */
    public function getPayloadRequest(): array
    {
        return [
            'header' => $this->headers,
            'body' => $this->payload['body'] ?? [],
        ];
    }

    public function getPayloadURL(): array
    {
        return [
            'url' => $this->url
        ];
    }
    /**
     * @param array $payload
     * @return string
     * @throws RequestException|GuzzleException
     */
    public function exec(array $payload): string
    {
        if (licSetting('mock_adm')) {
            return $this->mockADM($payload['header']["Cod_tipo_messaggio"]);
        }

        try {
            $retry_config = $this->config['pgda']['retry_request_options'];
            $options = [
                'connect_timeout' => isCli() ? $retry_config['cli_connect_timeout'] : $retry_config['web_connect_timeout'],
                'timeout' => isCli() ? $retry_config['cli_timeout'] : $retry_config['web_timeout'],
            ];
            $this->payload = $payload;
            $request = $this->getRequest($this->getMessage($payload));
            $response = $this->send($request, $options);
            $body = $response->getBody();
            return $this->sign_message ? $this->reveal($body) : $body;
        }catch(RequestException $e) {
            phive('Logger')
                ->getLogger('pgda_adm')
                ->error("PGDA client " .__METHOD__. " received Exception.", [
                    'message' => "PGDA client exec() response",
                    'payload' => $payload,
                    'exception' => $e->getResponse(),
                ]);
            $this->notify('ADM Unhandled Error', ['request' => $payload, 'error' => [$e]]);
            throw $e;
        }
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->config['pgda']['protocol_version'] ?? self::DEFAULT_PROTOCOL_VERSION;
    }


    private function generateRandomSession($startWith, $length) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWX';
        $randomString = $startWith;

        for ($i = 0; $i < $length-1 ; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    private function mockADM($requestType)
    {
        $header = '{"header":';
        switch ($requestType) {
            case "400":
                return
                    $header
                    . licSetting('adm_mock_message_400')
                    .',"code":0,"session_id":"'
                    . $this->generateRandomSession('M', 16) . '"}';
            case "420":
                return
                    $header
                    . licSetting('adm_mock_message_420')
                    .',"code":0,"participation_code":"'. $this->generateRandomSession('N', 16)
                    .'","year":' . date("Y") . ',"month":' . date("n") . ',"day":' . date("j") . '}';

            case "430":
                return
                    $header
                    . licSetting('adm_mock_message_430')
                    .'"code":0,"year":'
                    . date("Y") . ',"month":' . date("n") . ',"day":' . date("j") . '}';

            case "500":
                return
                    $header
                    . licSetting('adm_mock_message_500')
                    .'"code":0}';

            case "580":
            case "590":
                return
                    $header
                    . licSetting('adm_mock_message_5x0')
                    .'"code":0}';
            default:
                return "";
        }
    }
}
