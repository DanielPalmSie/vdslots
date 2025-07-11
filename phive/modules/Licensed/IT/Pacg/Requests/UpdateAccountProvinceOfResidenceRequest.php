<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\UpdateAccountProvinceOfResidenceResponse;

/**
 * Class UpdateAccountProvinceOfResidenceRequest
 * @package IT\Pacg\Requests
 */
class UpdateAccountProvinceOfResidenceRequest extends PacgRequest
{
    protected $key = 'modificaProvinciaResidenzaContoIn';
    protected $message_name = 'modificaProvinciaResidenzaConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return UpdateAccountProvinceOfResidenceResponse::class;
    }
}