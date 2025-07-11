<?php

/*
 * code based in https://github.com/swoole/phpkafka/pull/74
 * it was modified to add the AWS credentials, so we can't simply replace it
 */

declare(strict_types=1);

namespace History\phpkafka;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;
use longlang\phpkafka\Config\CommonConfig;
use longlang\phpkafka\Exception\KafkaErrorException;
use longlang\phpkafka\Sasl\SaslInterface;

/**
 * Adds AWS SASL/IAM authentication to AWS Kafka connections
 * extra params
 * [
 *   'region' => aws region for the user,
 *   'expiration' => optional, key expiration,
 *   'aws_key' => optional, to use this instead of env or shared file credentials
 *   'aws_secret' => optional, to use this instead of env or shared file credentials
 * ]
 */
class VSAwsMskIamSasl implements SaslInterface
{
    protected const SIGN_ACTION = "kafka-cluster:Connect";
    protected const SIGN_SERVICE = "kafka-cluster";
    protected const SIGN_VERSION = "2020_10_22";
    protected const SIGN_ACTION_KEY = "action";
    protected const SIGN_HOST_KEY = "host";
    protected const SIGN_USER_AGENT_KEY = "user-agent";
    protected const SIGN_VERSION_KEY = "version";
    protected const QUERY_ACTION_KEY = "Action";


    /**
     * @var CommonConfig
     */
    protected CommonConfig $config;

    /**
     * @var string
     */
    protected string $host;


    public function __construct(CommonConfig $config)
    {
        $this->config = $config;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * Authorization mode
     */
    public function getName(): string
    {
        return 'AWS_MSK_IAM';
    }

    /**
     * Generated the Signed JSON used by AWS_MSK_IAM as auth string
     * @throws KafkaErrorException
     */
    public function getAuthBytes(): string
    {
        $sasl = $this->config->getSasl();
        //we might need to override the AWS credentials, we restore whatever was there later
        if (!empty($sasl['aws_key'])) {
            $old_key = getenv(CredentialProvider::ENV_KEY);
            putenv(CredentialProvider::ENV_KEY.'='.$sasl['aws_key']);
        }
        if (!empty($sasl['aws_secret'])) {
            $old_secret = getenv(CredentialProvider::ENV_SECRET);
            putenv(CredentialProvider::ENV_SECRET.'='.$sasl['aws_secret']);
        }

        $response = $this->realGetAuthBytes();

        if (!empty($sasl['aws_key'])) {
            /** @noinspection PhpUndefinedVariableInspection */
            putenv(CredentialProvider::ENV_KEY.'='.$old_key);
        }

        if (!empty($sasl['aws_secret'])) {
            /** @noinspection PhpUndefinedVariableInspection */
            putenv(CredentialProvider::ENV_SECRET.'='.$old_secret);
        }

        return $response;
    }

    private function realGetAuthBytes(): string
    {
        $config = $this->config->getSasl();
        if (empty($this->host) || empty($config['region'])) {
            throw new KafkaErrorException('AWS MSK config params not found');
        }

        $query = http_build_query(array(
            self::QUERY_ACTION_KEY => self::SIGN_ACTION,
        ));

        if (empty($config['expiration'])) {
            $expiration = "+5 minutes";
        } else {
            $expiration = $config['expiration'];
        }

        $region = $config['region'];

        $url = "kafka://".$this->host."/?".$query;
        $provider = CredentialProvider::defaultProvider();
        // Returns a CredentialsInterface or throws.
        $creds = $provider()->wait();

        $req = new Request('GET', $url);

        $signer = new SignatureV4(self::SIGN_SERVICE, $region);
        $signedReq = $signer->presign($req, $creds, $expiration);

        parse_str($signedReq->getUri()->getQuery(), $params);

        $headers = $signedReq->getHeaders();

        $signedMap = array(
            self::SIGN_VERSION_KEY    => self::SIGN_VERSION,
            self::SIGN_USER_AGENT_KEY => "php-kafka/sasl/aws_msk_iam/".PHP_VERSION,
            self::SIGN_ACTION_KEY     => self::SIGN_ACTION,
            self::SIGN_HOST_KEY       => $this->host,
        );

        foreach ($params as $params_key => $params_value) {
            $signedMap[strtolower($params_key)] = $params_value;
        }

        foreach ($headers as $header_key => $header_value) {
            $header_key = strtolower($header_key);
            if ($header_key !== self::SIGN_HOST_KEY) {
                $signedMap[$header_key] = $header_value;
            }
        }

        return json_encode($signedMap);
    }
}
