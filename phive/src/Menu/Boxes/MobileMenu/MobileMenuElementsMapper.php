<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu;

use Videoslots\Menu\Boxes\MobileMenu\Element\ContentData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemDataInterface;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemDepositData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemWithdrawalData;
use Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData;

final class MobileMenuElementsMapper
{
    /**
     * @param array $data
     * @param bool $isApi
     *
     * @return array<\Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData>
     */
    public function mapToMenuItems(array $data, bool $isApi): array
    {
        $menuItems = [];
        foreach ($data as $item) {
            if ($isApi && str_contains('"href="//mobile/?signout=true""', $item['params'])) {
                continue;
            }
            $menuItems[] = $this->mapToMenuItemData($item, $isApi);
        }

        return $menuItems;
    }

    /**
     * @param array $data
     * @param bool $isApi
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemData
     */
    public function mapToMenuItemData(array $data, bool $isApi): MenuItemDataInterface
    {
        $type = Helper::getType($data['txt']);

        $content = new ContentData($data['txt'], $type);
        $href = Helper::parseHref($data['params']);

        if ($href == 'withdraw' && ! $isApi) {
            return new MenuItemWithdrawalData(
                $data['alias'] ?? "",
                $content,
                $this->parseCurrent($data),
                $isApi ? $href : $data['params']
            );
        }

        if ($href == 'deposit' && ! $isApi) {
            return new MenuItemDepositData(
                $data['alias'] ?? "",
                $content,
                $this->parseCurrent($data),
                $isApi ? $href : $data['params']
            );
        }

        return new MenuItemData(
            $data['alias'] ?? "",
            $content,
            $this->parseCurrent($data),
            $isApi ? $href : $data['params'],
            $data['priority']
        );
    }

    /**
     * @param array $data
     * @param bool $isApi
     *
     * @return array<\Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData>
     */
    public function mapToSubmenuItems(array $data, bool $isApi): array
    {
        $menuItems = [];
        foreach ($data as $item) {
            $menuItems[] = $this->mapToSubMenuItemData($item, $isApi);
        }

        return $menuItems;
    }

    /**
     * @param array $data
     * @param bool $isApi
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData
     */
    public function mapToSubMenuItemData(array $data, bool $isApi): SubMenuItemData
    {
        $type = Helper::getType($data['txt']);
        $content = new ContentData($data['txt'], $type);
        $href = Helper::parseHref($data['params']);

        return new SubMenuItemData(
            $data['params'] ?? "",
            $data['icon'] ?? "",
            $this->parseCurrent($data),
            $content,
            $isApi ? $href : $data['params'],
            $data['page_id']
        );
    }

    /**
     * "Transforms" value in "current" to boolean
     * Examples
     * - "True" => true
     * - "true" => true
     * - "false" => false
     * - "FALSE" => false
     * - "ZXC" => false
     * - "123" => false
     *
     * @param array $data
     *
     * @return bool
     */
    private function parseCurrent(array $data): bool
    {
        $input = $data['current'] ?? "";

        $bool = filter_var($input, FILTER_VALIDATE_BOOLEAN);

        return $bool === true;
    }
}
