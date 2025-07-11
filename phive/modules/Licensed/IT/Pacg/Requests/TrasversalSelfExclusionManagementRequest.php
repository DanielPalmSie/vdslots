<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\TrasversalSelfExclusionManagementResponse;

/**
 * Class TrasversalSelfExclusionManagementRequest
 * @package IT\Pacg\Requests
 */
class TrasversalSelfExclusionManagementRequest extends PacgRequest
{
    protected $key = 'gestioneAutoesclusioneTrasversaleIn';
    protected $message_name = 'gestioneAutoesclusioneTrasversale';
    protected $set_account_information = false;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return TrasversalSelfExclusionManagementResponse::class;
    }
}