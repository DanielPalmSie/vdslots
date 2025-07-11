<?php

declare(strict_types=1);

namespace Videoslots\User\Services;

use DBUser;
use Laraphive\Support\Settings\RedirectActionSettings;

class LoginRedirectsService
{
    /**
     * @var \Videoslots\User\Services\LoginRedirectsValidatorService
     */
    private LoginRedirectsValidatorService $validatorService;

    /**
     * @param \Videoslots\User\Services\LoginRedirectsValidatorService $validatorService
     */
    public function __construct(LoginRedirectsValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getLoginRedirectActions($data): array
    {
        $actions = [];

        if (is_string($data)) {
            $actions[] = $data;
        } elseif ($data instanceof DBUser) {
            $actionTypes = RedirectActionSettings::REDIRECT_ACTION_TYPES;
            foreach ($actionTypes as $action => $type) {
                if ($this->validatorService->validate($action, $data) === true) {
                    $actions[] = $action;
                }
            }
        }

        return $actions;
    }
}
