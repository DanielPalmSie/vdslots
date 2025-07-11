<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/ExtModule.php';

use Laraphive\Domain\User\Models\User;

/**
 * Module for AdaChat. Generates token with specific abilities to access API endpoints
 */
class AdaChat extends ExtModule {
    /**
     *
     */
    const PREFIX = 'ADA';

    /**
     * @var User
     */
    private User $userModel;
    /**
     * @var array
     */
    private array $abilities;

    public function __construct(){
        parent::__construct();

        $this->abilities = $this->getSetting('abilities');
        $this->userModel = new User();
    }

    /**
     * @param string $userId
     * @return string
     */
    public function getAuthToken(string $userId): string {
        $this->userModel->id = $userId;
        $tokenData = $this->userModel->createToken(self::PREFIX, $this->abilities);
        return $tokenData->getToken();
    }

}
