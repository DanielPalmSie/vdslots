<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdateAccountStatusResponse;

/**
 * Class UpdateAccountStatusRequest
 */
class UpdateAccountStatusRequest extends PacgRequest
{
    protected $key = 'cambioStatoContoIn';
    protected $message_name = 'cambioStatoConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdateAccountStatusResponse::class;
    }
}