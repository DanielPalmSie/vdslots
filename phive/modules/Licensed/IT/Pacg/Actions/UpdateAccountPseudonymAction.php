<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\UpdateAccountPseudonymRequest;
use IT\Pacg\Services\UpdateAccountPseudonymEntity;

/**
 * Class UpdateAccountPseudonymAction
 * @package IT\Pacg\Actions
 */
class UpdateAccountPseudonymAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return UpdateAccountPseudonymRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return UpdateAccountPseudonymEntity::class;
    }
}