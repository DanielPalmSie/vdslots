<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountEmailResponse;

/**
 * Class QueryAccountEmailRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountEmailRequest extends PacgRequest
{
    protected $key = 'interrogazionePostaElettronicaContoIn';
    protected $message_name = 'interrogazionePostaElettronicaConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountEmailResponse::class;
    }
}