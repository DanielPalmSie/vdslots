<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountDocumentResponse;

/**
 * Class QueryAccountDocumentRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountDocumentv25Request extends PacgRequest
{
    protected $key = 'interrogazioneEstremiDocumento2In';
    protected $message_name = 'interrogazioneEstremiDocumento2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountDocumentResponse::class;
    }
}