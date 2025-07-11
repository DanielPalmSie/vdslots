<?php
namespace IT\Abstractions;

/**
 * Class AbstractResponse
 * @package IT\Pacg\Responses
 */
abstract class AbstractResponse implements InterfaceResponse
{
    /**
     * @var AbstractRequest
     */
    protected $request;

    /**
     * @var object
     */
    protected $return_code;

    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $response;


    /**
     * AbstractResponse constructor.
     * @param AbstractRequest $request
     */
    public function __construct(AbstractRequest $request)
    {
        $this->request = $request;
        $this->return_code = $this->getNewReturnCode();
        $this->extractResponse($request->getResult());
    }

    /**
     * @return array
     */
    private function getResponseArray(): array
    {
        $response_array = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
        ];

        if ($this->isSuccess()) {
            $response_array['response'] = $this->getResponseBody();
        }

        return $response_array;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->getResponseArray();
    }

    /**
     * @return int
     */
    public function getCode(): ?int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getResponseBody(): array
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        $language = phive('Localizer')->getLanguage();
        return empty($language) ? 'en' : $language;
    }

    /**
     * @return object
     */
    abstract protected function getNewReturnCode();

    /**
     * @param \stdClass $response
     * @return void
     */
    abstract protected function extractResponse($response);

    /**
     * @return bool
     */
    abstract public function isSuccess(): bool;
}