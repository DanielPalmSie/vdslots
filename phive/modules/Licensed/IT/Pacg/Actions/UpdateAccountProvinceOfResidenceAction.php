<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\UpdateAccountProvinceOfResidenceRequest;
use IT\Pacg\Services\UpdateAccountProvinceOfResidenceEntity;

/**
 * Class UpdateAccountProvinceOfResidenceAction
 * @package IT\Pacg\Actions
 */
class UpdateAccountProvinceOfResidenceAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return UpdateAccountProvinceOfResidenceRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return UpdateAccountProvinceOfResidenceEntity::class;
    }
}