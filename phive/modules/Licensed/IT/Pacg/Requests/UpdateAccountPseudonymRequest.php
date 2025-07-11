<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdateAccountPseudonymResponse;

/**
 * Class UpdateAccountPseudonymRequest
 * @package IT\Pacg\Requests
 */
class UpdateAccountPseudonymRequest extends PacgRequest
{
    protected $key = 'aggiornaPseudonimoContoIn';
    protected $message_name = 'aggiornaPseudonimoConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdateAccountPseudonymResponse::class;
    }
}