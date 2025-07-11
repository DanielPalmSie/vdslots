<?php
namespace IT\Abstractions;

use IT\Services\ErrorFormatterService;

/**
 * Class AbstractAction
 * @package IT\Abstractions
 */
abstract class AbstractAction
{
    /**
     * @var InterfaceClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $settings;

    /**
     * AbstractAction constructor.
     * @param InterfaceClient $client
     * @param array $settings
     * @throws \Exception
     */
    public function __construct(InterfaceClient $client, array $settings)
    {
        $this->client = $client;
        $this->settings = $settings;
    }

    /**
     * @return AbstractRequest
     */

    public function getRequest(): AbstractRequest
    {
        $request_name = $this->request();
        return new $request_name($this->client, $this->settings);
    }

    /**
     * @return AbstractEntity
     */
    public function getEntity(): AbstractEntity
    {
        $entity_name = $this->entity();
        return new $entity_name();
    }

    /**
     * @return ErrorFormatterService
     */
    protected function getNewErrorFormatter(): ErrorFormatterService
    {
        return new ErrorFormatterService();
    }

    /**
     * @param array $errors_data
     * @return \Exception
     */
    protected function getNewException(array $errors_data): \Exception
    {
        return new \Exception($this->getNewErrorFormatter()->format($errors_data));
    }

    /**
     * @param $data
     * @return AbstractEntity
     * @throws \Exception
     */
    protected function loadPayloadData(array $data): AbstractEntity
    {
        $entity = $this->getEntity();
        $entity->fill($data);

        return $entity;
    }

    /**
     * @param array $data
     * @return AbstractResponse
     * @throws \Exception
     */
    public function execute(array $data): AbstractResponse
    {
        $request = $this->getRequest();
        $entity = $this->loadPayloadData($data);

        $this->collectValidationErrors($entity);

        if (! empty($entity->errors)) {
            throw $this->getNewException($entity->errors);
        }

        return $request->request($entity);
    }

    /**
     * @param AbstractEntity $entity
     */
    public function collectValidationErrors(AbstractEntity $entity)
    {
        foreach($entity->getFillables() as $property) {
            if ($entity->$property instanceof AbstractEntity && ! empty($entity->$property->errors)) {
                $this->collectValidationErrors($entity->$property);
                $entity->errors = array_merge($entity->errors, $entity->$property->errors);
            }
        }
    }

    /**
     * @return string
     */
    abstract public function request(): string;

    /**
     * @return string
     */
    abstract public function entity(): string;
}