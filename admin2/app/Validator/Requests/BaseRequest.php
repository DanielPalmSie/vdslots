<?php

namespace App\Validator\Requests;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Valitron\Validator;

class BaseRequest implements ValidateInterface
{
    private Validator $validator;
    private ?Request $request;

    /**
     * Properties that are not allowed to be overridden
     * @var array|string[]
     */
    private array $protected = [
        'validator',
        'rules',
        'autoValidate',
        'request',
    ];
    /**
     * Rules are set/overridden by child class
     *
     * @var array
     */
    protected array $rules = [];
    protected bool $autoValidate = false;

    /**
     * @param Request|null $request
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request;
        $this->validator = new Validator($this->getRequest()->request->all());
        $this->populate();

        if ($this->autoValidateRequest()) {
            $this->validate();
        }
    }

    /**
     * @return array|bool|void
     */
    public function validate()
    {
        $this->validator->mapFieldsRules($this->rules());
        $isValid = $this->validator->validate();

        if ($isValid) {
            return true;
        }

        // handle validation errors manually
        if ($this->request) {
            return $this->validator->errors();
        }

        $message['success'] = false;
        $message['errors'] = $this->validator->errors();

        //send validation errors
        if ($this->getRequest()->isXmlHttpRequest()) {
            $response = new JsonResponse($message, Response::HTTP_UNPROCESSABLE_ENTITY);
        } else {
            $response = new Response($message, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response->send();
        exit;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request ?? Request::createFromGlobals();
    }

    /**
     * Validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return void
     */
    protected function populate(): void
    {
        foreach ($this->getRequest()->request->all() as $property => $value) {
            if (!in_array($property, $this->protectedProperties()) && property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * Should validate upon instantiation or not
     * @return bool
     */
    protected function autoValidateRequest(): bool
    {
        return $this->autoValidate;
    }

    /**
     * @return array|string[]
     */
    private function protectedProperties(): array
    {
        return $this->protected;
    }
}