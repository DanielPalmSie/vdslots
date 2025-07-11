<?php
namespace IT\Pacg\Responses;

/**
 * Class QueryAccountStatusResponse
 * @package IT\Pacg\Responses
 */
class QueryAccountStatusResponse extends PacgResponse
{
    /**
     * @var string
     */
    public $status;

    /**
     * @var
     */
    public $status_reason;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->status = $response_array['responseElements']['stato'];
        $this->status_reason = $response_array['responseElements']['causale'];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'status'        => $this->status,
            'status_reason' => $this->status_reason
        ];

        return parent::toArray($values);
    }
}