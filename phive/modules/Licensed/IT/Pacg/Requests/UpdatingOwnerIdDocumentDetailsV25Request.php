<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdatingOwnerIdDocumentDetailsResponse;

/**
 * Class UpdatingOwnerIdDocumentDetailsRequest
 * @package IT\Pacg\Requests
 */
class UpdatingOwnerIdDocumentDetailsV25Request extends PacgRequest
{
    protected $key = 'aggiornaDatiDocumentoTitolareConto2In';
    protected $message_name = 'aggiornaDatiDocumentoTitolareConto2';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdatingOwnerIdDocumentDetailsResponse::class;
    }
}