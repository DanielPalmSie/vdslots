<?php

declare(strict_types=1);

namespace Videoslots\Menu;

use Menuer;

final class MenuService implements MenuServiceInterface
{
    /**
     * @var \Menuer
     */
    private Menuer $menuer;

    public function __construct()
    {
        $this->menuer = phive('Menuer');
    }

    /**
     * @param string $menuId
     *
     * @return array
     */
    public function getSecondaryMenuItems(string $menuId): array
    {
        if ($this->menuer->getSetting('secondary_nav', false) === false) {
            return [];
        }

        $menuItems = $this->menuer->forRender($menuId);
        if (count($menuItems) <= 2) {
            return [];
        }

        return $menuItems;
    }

    /**
     * @param string $menuAlias
     * @param bool $translate
     *
     * @return array
     */
    public function getMenuItems(string $menuAlias, bool $translate): array
    {
        $user = cu();
        $uid = "";
        if ($user !== null && $user !== false) {
            $uid = $user->getId();
        }

        return $this->menuer->forRender(
            $menuAlias,
            '',
            true,
            $uid,
            '',
            true,
            "/",
            '',
            $user,
            $translate
        );
    }
}
