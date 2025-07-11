<?php
namespace IT\Pacg\Requests;

use IT\Abstractions\AbstractRequest;
use IT\Pacg\Client\PacgClient;
use IT\Pacg\Types\DateTimeType;

/**
 * Class PacgRequest
 */
abstract class PacgRequest extends AbstractRequest
{
    /**
     * @var string
     */
    private $id_cn;

    /**
     * @var string
     */
    private $id_fsc;

    /**
     * @var string
     */
    private $network_id;

    /**
     * @var string
     */
    private $account_network_id;

    /**
     * @var string
     */
    private $account_cn_id;

    /**
     * @var PacgClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $message_name;

    /**
     * @var array
     */
    protected $request_return;

    /**
     *
     * @var boolean
     */
    protected $encryption_needed = false;

    /**
     *
     * @var boolean
     */
    protected $signature_verification_needed = false;

    /**
     * @var bool
     */
    protected $set_account_information = true;
    /**
     * @var bool
     */
    protected $set_transaction_datetime = false;

    /**
     * @var string
     */
    protected $time_zone = 'Europe/Rome';

    /**
     * @param array $settings
     */
    protected function setSetting(array $settings = [], $request_code = 0)
    {
        $this->setIdFsc($settings['id_fsc']);
        $this->setIdCn($settings['id_cn']);
        $this->setNetworkId($settings['network_id']);
        $this->setAccountNetworkId($settings['network_id']);
        $this->setAccountCnId($settings['id_cn']);
        $this->client->setDataForUserToken($settings['pacg']['wss']['username'], $settings['pacg']['wss']['password'] ?? null);
        $this->client->encryption_needed = $this->encryption_needed;
        $this->client->signature_verification_needed = $this->signature_verification_needed;
        $this->client->setConfigurations(['functionName' => $this->message_name]);
    }

    /**
     * @return string
     */
    public function getAccountNetworkId(): string
    {
        return $this->account_network_id;
    }

    /**
     * @return string
     */
    public function getAccountCnId(): string
    {
        return $this->account_cn_id;
    }

    /**
     * @param string $account_network_id
     */
    public function setAccountNetworkId(string $account_network_id)
    {
        $this->account_network_id = $account_network_id;
    }

    /**
     * @param string $account_cn_id
     */
    public function setAccountCnId(string $account_cn_id)
    {
        $this->account_cn_id = $account_cn_id;
    }

    /**
     * @return string
     */
    public function getNetworkId(): string
    {
        return $this->network_id;
    }

    /**
     * @param string $network_id
     * @return void
     */
    public function setNetworkId(string $network_id)
    {
        $this->network_id = $network_id;
    }

    /**
     * @return string
     */
    public function getIdCn(): string
    {
        return $this->id_cn;
    }

    /**
     * @param string $id_cn
     * @return void
     */
    public function setIdCn(string $id_cn)
    {
        $this->id_cn = $id_cn;
    }

    /**
     * @return string
     */
    public function getIdFsc(): string
    {
        return $this->id_fsc;
    }

    /**
     * @param string $id_fsc
     * @return void
     */
    public function setIdFsc(string $id_fsc)
    {
        $this->id_fsc = $id_fsc;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    private function getCurrentDateTime(): \DateTime
    {
        $time_zone = new \DateTimeZone($this->time_zone);
        return new \DateTime('now', $time_zone);
    }

    /**
     * @param array $datetime_context
     * @return DateTimeType
     * @throws \Rakit\Validation\RuleQuashException
     */
    private function getDateTimeType(array $datetime_context): DateTimeType
    {
        $datetime_type = new DateTimeType();
        $datetime_type->fill($datetime_context);

        return $datetime_type;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getTransactionDateTime(): array
    {
        $current_datetime = $this->getCurrentDateTime();
        $datetime_context = [
            'date' => [
                'day' => $current_datetime->format('d'),
                'month' => $current_datetime->format('m'),
                'year' => $current_datetime->format('Y'),
            ],
            'time' => [
                'hours' => $current_datetime->format('h'),
                'minutes' => $current_datetime->format('i'),
                'seconds' => $current_datetime->format('s'),
            ]
        ];
        return $this->getDateTimeType($datetime_context)->toArray();
    }

    /**
     * @param array $model
     * @return array
     * @throws \Exception
     */
    protected function setAccountFields(array $model): array
    {
        if ($this->set_account_information) {
            $model['idCnConto'] = $this->getAccountCnId();
            $model['idReteConto'] = $this->getAccountNetworkId();
        }

        if ($this->set_transaction_datetime) {
            $model['dataOraSaldo'] = $this->getTransactionDateTime();
        }

        return $model;
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function setCommonAttributes(array $data): array
    {
        $data = array_merge([
            "idFsc"  => $this->getIdFsc(),
            "idCn"   => $this->getIdCn(),
            "idRete" => $this->getNetworkId()
        ], $data);


        return [
            "requestElements" => $this->setAccountFields($data)
        ];
    }

    /**
     * @return string
     */
    public function responseName(): string
    {
        return $this->response();
    }

    /**
     * Request package name
     * @return string
     */
    public function requestName(): string
    {
        return 'Pacg';
    }

    /**
     * @return string
     */
    abstract public function response(): string;
}