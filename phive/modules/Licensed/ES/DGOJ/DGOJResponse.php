<?php

require_once __DIR__ . '/Response/IdentityResponse.php';
require_once __DIR__ . '/Response/RGIAJResponse.php';
require_once __DIR__ . '/Response/ServiceResponse.php';
require_once __DIR__ . '/Response/RGIAJChangesResponse.php';

class DGOJResponse
{
    /** @var array $request */
    private array $request;
    /** @var array $response */
    public array $response;
    /** @var bool $internal_validation */
    public bool $internal_validation = false;

    /**
     * DGOJResponse constructor.
     * @param $request
     * @param $response
     * @param false $internal_validation
     */
    public function __construct($request, $response, $internal_validation = false)
    {
        $this->response = $response;
        $this->request = $request;
        $this->internal_validation = $internal_validation;
    }

    /**
     * Return the request data
     * @return array
     */
    public function getOriginalRequestData(): array
    {
        return $this->request;
    }


    /**
     * Return the internal validation error message
     * @return mixed|null
     */
    public function getValidationError()
    {
        if (!$this->internal_validation) {
            return null;
        }
        return $this->response[0];
    }

    /**
     * Return the service error in case it is down or throws exception
     * @return string|null
     */
    public function getServiceError(): ?string
    {
        $response = new ServiceResponse($this->response);

        if ($response->isSuccess()) {
            return null;
        }

        return $response->getDescription();
    }

    /**
     * Helper to get both service and validation errors
     * @return mixed|string|null
     */
    public function getCommonValidationError()
    {
        $service_error = $this->getServiceError();
        if (!empty($service_error)) {
            return $service_error;
        }

        $validation_error = $this->getValidationError();
        if (!empty($validation_error)) {
            return $validation_error;
        }

        return null;
    }

    /**
     * Detect if current user is registered in "General Register of Gambling Access Bans"
     * @return bool|null
     */
    public function isBlacklisted(): ?bool
    {
        $response = new RGIAJResponse($this->response);

        if (!$response->exists) {
            return null;
        }

        return !$response->isSuccess();
    }

    /**
     * Detect if current user provided valid information
     * @return bool|null
     */
    public function isValidIdentity(): ?bool
    {
        $response = new IdentityResponse($this->response);

        if (!$response->exists) {
            return null;
        }

        return $response->isSuccess();
    }

    public function getChangeReason(): ?string
    {
        $response = new RGIAJChangesResponse(['cambioRGIAJ' => $this->response]);

        if (!$response->exists) {
            return null;
        }

        return $response->getChangeReason();
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }
}
