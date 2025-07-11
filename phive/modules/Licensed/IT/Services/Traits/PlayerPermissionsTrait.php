<?php
namespace IT\Services\Traits;
/**
 * Trait PlayerPermissionsTrait
 * 1. Set Permission Array on License as
 * $permissions = [
 *                       0      1     2     <== Status
 *      'withdraw' => [false, false, true]
 *      .....
 * ];
 * 2. Override 'getPlayerStatus' logic in License file to return the current player status
 */
trait PlayerPermissionsTrait
{

    private array $permissions;
    private int $playerPermissionStatus;

    /**
     * Gets if the current player has some permission
     *
     * @param $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission($user, string $permission)
    {
        if (empty($this->playerPermissionStatus) || empty($this->permissions)) {
            $this->playerPermissionStatus = $this->getPlayerStatus($user);
            $this->permissions = $this->getPlayerPermissions();
        }
        return $this->permissions[$permission][$this->playerPermissionStatus] ?? false;
    }

    public function getPlayerStatus($user)
    {
        return 0;
    }

    public function getPlayerPermissions()
    {
        return [
            /*'login' =>  [
                NO_VERIFIED_MONTH_1 => true,
                NO_VERIFIED_MONTH_2 => true,
                NO_VERIFIED_LONGER => false,
                VERIFIED => true
            ]*/
        ];
    }


}