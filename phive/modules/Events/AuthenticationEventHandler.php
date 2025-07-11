<?php

class AuthenticationEventHandler
{
    private DBUserHandler $uh;
    private Arf $arf;
    private Fr $fr;
    private Dmapi $dmapi;
    private Licensed $licensed;
    private Linker $linker;

    public function __construct()
    {
        $this->uh = phive('DBUserHandler');
        $this->arf = phive('Cashier/Arf');
        $this->fr = phive('Cashier/Fr');
        $this->dmapi = phive('Dmapi');
        $this->licensed = phive('Licensed');
        $this->linker = phive('Site/Linker');
    }

    /**
     * @param int $userId
     *
     * @return void
     */
    public function onAuthenticationTestLogEvent(int $userId): void
    {
        phive('Logger')->getLogger('test')->info('onAuthenticationTestLogEvent', ["userId: ".$userId]);
    }

    /**
     * @param int $user_id
     *
     * @return void
     */
    public function onAuthenticationSendOtpCodeEvent(int $user_id): void
    {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->uh->sendOtpCode($user_id);
    }

    /**
     * @param int|array $user_id
     *
     * @return void
     */
    public function onAuthenticationLoginWhenSelfExcludedEvent($user_id): void
    {
        $user = cu($user_id);
        // if user is not found, do nothing
        if (empty($user)) {
            return;
        }
        $this->arf->invoke('onLoginWhenSelfExcluded', $user);
    }

    /**
     * Handles the event triggered when a self-locked user attempts to log in.
     *
     * @param int|array $user_id
     *
     * @return void
     */
    public function onAuthenticationLoginWhenSelfLockedEvent($user_id): void
    {
        $user = cu($user_id);
        // if user is not found, do nothing
        if (empty($user)) {
            return;
        }
        $this->arf->invoke('onLoginWhenSelfLocked', $user);
    }

    /**
     * @param int $user_id
     *
     * @return void
     */
    public function onAuthenticationLoginEvent(int $user_id): void
    {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->arf->invoke('onLogin', $user_id);

        $this->uh->onLoginEvent($user_id);
    }


    public function onAuthenticationLogoutEvent(string $message, string $tag, string $uid): void
    {
        if (empty($message) || empty($tag)) {
            return;
        }

        toWs($message, $tag, $data['uid'] ?? 'na');
    }

    /**
     * @param int $user_id
     * @param string $type
     * @param string $scheme
     * @param string $subtag
     * @param string|int $external_id
     * @param int $actor_id
     * @param array $extra
     * @param string $status
     *
     * @return void
     */
    public function onAuthenticationCreateEmptyDocumentEvent(
        int $user_id,
        string $type,
        string $scheme = '',
        string $subtag = '',
        string $external_id = '',
        int $actor_id = 0,
        array $extra = [],
        string $status = 'requested'
    ): void {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->dmapi->createEmptyDocument($user_id, $type, $scheme, $subtag, $external_id, $actor_id, $extra, $status);
    }

    /**
     * @param int $user_id
     * @param bool $recurrent
     * @param array|null $suppliers
     *
     * @return void
     */
    public function onAuthenticationCheckPEPSanctionsCommonEvent(
        int $user_id,
        bool $recurrent = false,
        array $suppliers = null
    ): void {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->licensed->checkPEPSanctionsCommon($user_id, $recurrent, $suppliers);
    }

    /**
     * @param int $user_id
     * @param bool $recurrent
     *
     * @return void
     */
    public function onAuthenticationCheckKycGeneralEvent(int $user_id, bool $recurrent = false): void
    {
        phive('Logger')->getLogger('registration')->debug("AuthenticationEventHandler::onAuthenticationCheckKycGeneralEvent", [$user_id, $recurrent]);
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->licensed->checkKycGeneral($user_id, $recurrent);
    }

    /**
     * @param int $user_id
     * @param string $check_self_exclusion
     *
     * @return void
     */
    public function onAuthenticationBrandLinkEvent(int $user_id, string $check_self_exclusion = 'no'): void
    {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->linker->brandLink($user_id, $check_self_exclusion);
    }

    /**
     * @param  int|null  $user_id
     *
     * @return void
     */
    public function onAuthenticationRegistrationEvent(?int $user_id = null): void
    {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->arf->invoke('onRegistration', $user_id);
    }

    /**
     * @param int $user_id
     *
     * @return void
     */
    public function onAuthenticationEmailAndPhoneCheckEvent(int $user_id): void
    {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->fr->emailAndPhoneCheck($user_id);
    }

    /**
     * @param int $user_id
     *
     * @return void
     */
    public function onAuthenticationRemoveEmailAndPhoneCheckFlagEvent(int $user_id): void
    {
        // if user is not found, do nothing
        if (empty(cu($user_id))) {
            return;
        }
        $this->fr->removeEmailAndPhoneCheckFlag($user_id);
    }

    public function onAuthenticationSimulated()
    {
        echo "onSimulated\n";
        phive('Logger')->info('onSimulated', []);
    }
}
