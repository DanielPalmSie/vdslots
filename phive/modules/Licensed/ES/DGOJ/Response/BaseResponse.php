<?php

class BaseResponse
{
    /** @var array $response_code Contains all possible response codes */
    public array $response_code = [];
    /** @var string $response_key Used to find response in xml */
    public string $response_key = '';
    /** @var string $key_code Used to find response code */
    public string $key_code = 'codigo';
    /** @var string $key_description Used to find response description */
    public string $key_description = 'descripcion';
    /** @var string $success_code When this code is provided we consider a success case */
    public string $success_code = '';
    /** @var string|mixed $code Response code */
    private string $code = '';
    /** @var string|mixed $description Response description */
    private string $description = '';
    /** @var bool $exists Detect if current response type was found in raw $response */
    public bool $exists = false;
    /** @var array|null */
    protected $response;

    /**
     * BaseResponse constructor.
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        if (!empty($this->response_key)) {
            $this->response = $response[$this->response_key] ?? [];
        }

        if (!empty($this->response)) {
            $this->exists = true;
            $this->code = $this->response[$this->key_code] ?? '';
            $this->description = $this->response[$this->key_description] ?? '';
        }
    }


    /**
     * Return code
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Return description
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Detect if we got a success response
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->code === $this->success_code;
    }

    /**
     * Get response
     *
     * @return array|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}