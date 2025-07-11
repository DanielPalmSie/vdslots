<?php

declare(strict_types=1);

namespace Videoslots\User\Services;

use DBUser;
use Laraphive\Domain\User\DataTransferObjects\Requests\UpdateProfileAccountRequestData;
use Laraphive\Domain\User\DataTransferObjects\Responses\UpdateProfileAccountResponseData;
use Laraphive\Domain\User\Factories\UpdateProfileAccountResponseFactory;

final class UpdateProfileAccountService implements UpdateProfileAccountServiceInterface
{
    /**
     * @var \DBUser
     */
    private DBUser $user;

    /**
     * @param \DBUser $user
     */
    public function __construct(DBUser $user)
    {
        $this->user = $user;
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\Requests\UpdateProfileAccountRequestData $requestData
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\Responses\UpdateProfileAccountResponseData
     */
    public function updateProfileAccount(
        UpdateProfileAccountRequestData $requestData
    ): UpdateProfileAccountResponseData {
        $preferred_lang = $requestData->getPreferredLang();
        if (! empty($preferred_lang)) {
            $lang = phive('Localizer')->checkLanguageOrGetDefault($preferred_lang);
            $this->user->setAttribute('preferred_lang', $lang);
        }

        if ($requestData->isAcceptTac()) {
            $this->user->setTcVersion();
        }

        if ($requestData->isAcceptBonusTac()) {
            $this->user->setBtcVersion();
        }

        if (lic('isSportsbookEnabled')) {
            $this->user->setSportTcVersion();
        }
        if ($requestData->isAcceptPpr()) {
            $this->user->setPpVersion();
        }

        $this->user->setSetting('realtime_updates', $requestData->isRealtimeUpdates() ? 1 : 0);
        $this->user->setSetting('calls', $requestData->isCalls() ? 1 : 0);
        $this->user->setSetting('show_in_events', $requestData->isShowInEvents() ? 1 : 0);
        $this->user->setSetting('show_notifications', $requestData->isShowNotifications() ? 1 : 0);

        return UpdateProfileAccountResponseFactory::createSuccess();
    }
}
