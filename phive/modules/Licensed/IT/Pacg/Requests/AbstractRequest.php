<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Services\AbstractService;

/**
 * Class AbstractRequest
 */
abstract class AbstractRequest
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
     * @var \SoapClient
     */
    private $client;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $msg;


    /**
     * AbstractRequest constructor.
     * @param \SoapClient $client
     * @param array $wss
     * @param string $id_fsc
     * @param string $id_cn
     * @param string $network_id
     */
    public function __construct(\SoapClient $client, array $wss, string $id_fsc, string $id_cn, string $network_id)
    {
        $this->setIdFsc($id_fsc);
        $this->setIdCn($id_cn);
        $this->setNetworkId($network_id);

        $this->client = $client;
        $this->client->addUserToken($wss["username"], $wss["password"] ?? null);
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

    private function checkModelFields(array $model): array
    {
        if (array_key_exists('idCnConto', $model) && empty($model['idCnConto'])) {
            $model['idCnConto'] = $this->getIdCn();
        }

        if (array_key_exists('idReteConto', $model) && empty($model['idReteConto'])) {
            $model['idReteConto'] = $model['idRete'];
        }

        return $model;
    }

    /**
     * @param array $data
     * @return array
     */
    private function setCommonAttributes(array $data): array
    {
        $data = array_merge([
            "idFsc"  => $this->getIdFsc(),
            "idCn"   => $this->getIdCn(),
            "idRete" => $this->getNetworkId()
        ], $data);

        return [
            "requestElements" => $this->checkModelFields($data)
        ];
    }

    /**
     * @param $key
     * @param $entity
     * @param $msg
     * @return bool|string
     * @throws \Exception
     */
    protected function executeEntity($key, $entity, $msg){
        return $this->execute($msg, [$key => $this->setCommonAttributes($entity->toArray())]);
    }

    /**
     * @param $functionName
     * @param $model
     * @return bool|string
     * @throws \Exception
     */
    private function execute(string $functionName, array $model)
    {
        try {
            return $this->client->__soapCall($functionName, $model);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @param AbstractService $entity
     * @return bool|string
     * @throws \Exception
     */
    public function request(AbstractService $entity)
    {
        return $this->executeEntity($this->key, $entity, $this->msg);
    }
}