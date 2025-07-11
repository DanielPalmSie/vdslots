<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdatingOwnerIdDocumentDetailsResponse;

/**
 * Class UpdatingOwnerIdDocumentDetailsRequest
 * @package IT\Pacg\Requests
 */
class UpdatingOwnerIdDocumentDetailsRequest extends PacgRequest
{
    protected $key = 'aggiornaDatiDocumentoTitolareContoIn';
    protected $message_name = 'aggiornaDatiDocumentoTitolareConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdatingOwnerIdDocumentDetailsResponse::class;
    }
}