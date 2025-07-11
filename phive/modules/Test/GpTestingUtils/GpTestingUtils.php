<?php

require_once __DIR__ . '/../../Micro/Gp.php';

class GpTestingUtils
{
    /**
     * @var Gp $gp;
     */
    protected Gp $gp;

    /**
     * @var string
     */
    protected string $walletMethod = '';

    /**
     * @var string
     */
    protected string $gpMethod = '';

    /**
     * @param Gp $gp
     */
    public function __construct(Gp $gp)
    {
        $this->gp = $gp;
    }

    /**
     * @param Gp $gp
     * @return static
     */
    public static function create(Gp $gp) : self
    {
        return new static($gp);
    }

    /**
     * Sets the GP method
     *
     * @param string $gp_method
     * @return GpTestingUtils
     */
    public function whenGpMethod(string $gp_method)
    {
        $this->gpMethod = $gp_method;
        return $this;
    }

    /**
     * Sets the wallet method
     *
     * @param string $wallet_method
     * @return $this
     */
    public function mockWalletMethod(string $wallet_method)
    {
        $this->walletMethod = "{$wallet_method}Mock";
        return $this;
    }

    /**
     * Execute the method from GpTestingUtils::class instead of the same method in Gp::class
     * @return void
     */
    public function execute()
    {
        if (
            !$this->isApiCall() ||
            !$this->isTestingToolEnabled() ||
            !$this->gpMethodIs($this->gpMethod) ||
            !method_exists($this, $this->walletMethod)
        ) {
            return;
        }

        call_user_func([$this, $this->walletMethod]);
    }

    /**
     * @return bool
     */
    private function isApiCall()
    {
        return !!preg_match('/^PostmanRuntime(.*)/i', $_SERVER["HTTP_USER_AGENT"]);
    }

    /**
     * Checks if the API testing is enabled.
     *
     * @return bool
     */
    private function isTestingToolEnabled(): bool
    {
        return (
            $this->gp->getSetting('api_testing_enabled') === true &&
            $this->gp->getSetting('api_testing_game') !== '' &&
            $this->gp->getSetting('api_testing_user') !== ''
        );
    }

    /**
     * Returns true if the method match the current method in the request.
     *
     * @param string $method
     * @return false
     */
    private function gpMethodIs(string $method) {
        if ($method === 'any') {
            return true;
        }

        return method_exists($this->gp, '_isGpMethod') ? $this->gp->_isGpMethod($method) : false;
    }

    /**
     * Returns the request object.
     *
     * @return object
     */
    private function getRequest(): ?object
    {
        return method_exists($this->gp, 'getGpParamsApiTesting') ? $this->gp->getGpParamsApiTesting() : null;
    }

    /**
     * Sets data in the cache system.
     *
     * @param string $key
     * @param $user_id
     * @param string $game_id
     * @return void
     */
    private function toSession(string $key, $user_id, string $game_id): void
    {
        $this->gp->toSession($key, $user_id, $game_id, 'desktop');
    }

    /**
     * Sets the requirements to create a fake user game session for testing purposes.
     *
     * @return void
     */
    public function _getUrlMock() {
        $request = $this->getRequest();
        if (!isset($request->apiTesting)) {
            return;
        }

        $parameters = $request->apiTesting;
        if (is_null(cu($parameters['user_id'])) || $parameters['user_id'] != $this->gp->getSetting('api_testing_user')) {
            return;
        }

        // Data required to start the user game session
        $this->toSession($parameters['token'], $parameters['user_id'], $parameters['game_id']);

        $reality_check_interval = phive('Casino')->startAndGetRealityInterval($parameters['user_id'], "{$this->gp->getGamePrefix()}{$parameters['game_id']}");
        if (!empty($reality_check_interval)) {
            phMsetShard(Gpinterface::PREFIX_MOB_RC_LANG, 'en', $parameters['user_id']);
            phMsetShard(Gpinterface::PREFIX_MOB_RC_TIMEOUT, '1', $parameters['user_id'], $reality_check_interval * 60);
            phMsetShard(Gpinterface::PREFIX_MOB_RC_PLAYTIME, time(), $parameters['user_id']);
        }

        if (!lic('hasGameplayWithSessionBalance', [], $parameters['user_id'])) {
            return;
        }

        // Data required to start the ext game participation
        phMsetShard('ext-game-session-stake', [
            'tab_id' => '',
            'game_limit' => null,
            'set_reminder' => null,
            'real_stake' => 100000,
            'restrict_future_check' => 0,
            'restrict_future_sessions' => 0,
            'token' => "{$parameters['token']}{$parameters['game_id']}",
            'game_ref' => "{$this->gp->getGamePrefix()}{$parameters['game_id']}",
        ], $parameters['user_id'], 50);
    }
}