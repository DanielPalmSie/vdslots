<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\AccountMigrationRequest;
use IT\Pacg\Services\AccountMigrationEntity;

/**
 * Class AccountMigrationAction
 * @package IT\Pacg\Actions
 */
class AccountMigrationAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return AccountMigrationRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AccountMigrationEntity::class;
    }
}