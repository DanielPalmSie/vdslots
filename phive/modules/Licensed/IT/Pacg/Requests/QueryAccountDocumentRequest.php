<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountDocumentResponse;

/**
 * Class QueryAccountDocumentRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountDocumentRequest extends PacgRequest
{
    protected $key = 'interrogazioneEstremiDocumentoIn';
    protected $message_name = 'interrogazioneEstremiDocumento';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountDocumentResponse::class;
    }
}