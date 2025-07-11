<?php

namespace Videoslots\Menu;

interface MenuServiceInterface
{
    /**
     * @param string $menuId
     *
     * @return array
     */
    public function getSecondaryMenuItems(string $menuId): array;

    /**
     * @param string $menuAlias
     * @param bool $translate
     *
     * @return array
     */
    public function getMenuItems(string $menuAlias, bool $translate): array;
}
