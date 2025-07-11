<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\SubregistrationResponse;

/**
 * Class SubregistrationRequest
 * @package IT\Pacg\Requests
 */
class SubregistrationRequest extends PacgRequest
{
    protected $key = 'subregistrazione2In';
    protected $message_name = 'subregistrazione2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return SubregistrationResponse::class;
    }
}