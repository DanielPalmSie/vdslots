<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\ContentData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuData;
use Videoslots\Menu\Boxes\MobileMenu\Helper;
use Videoslots\Menu\Boxes\MobileMenu\MobileMenuElementsMapper;
use Videoslots\Menu\MenuServiceInterface;

final class MenuElementFactory
{
    /**
     * @var \Videoslots\Menu\MenuServiceInterface
     */
    private MenuServiceInterface $menuService;

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\MobileMenuElementsMapper
     */
    private MobileMenuElementsMapper $mapper;

    /**
     * @param \Videoslots\Menu\MenuServiceInterface $menuService
     * @param \Videoslots\Menu\Boxes\MobileMenu\MobileMenuElementsMapper $mapper
     */
    public function __construct(MenuServiceInterface $menuService, MobileMenuElementsMapper $mapper)
    {
        $this->menuService = $menuService;
        $this->mapper = $mapper;
    }

    /**
     * @param string $menuAlias
     * @param bool $translate
     * @param string $menuHeader
     * @param bool $isApi
     *
     * @return null|\Videoslots\Menu\Boxes\MobileMenu\Element\MenuData
     */
    public function create(string $menuAlias, bool $translate, string $menuHeader, bool $isApi): ?MenuData
    {
        $data = $this->menuService->getMenuItems($menuAlias, $translate);
        $menuItems = $this->mapper->mapToMenuItems($data, $isApi);

        if ($menuAlias === Helper::MENU_MOBILE_MAIN) {
            $chatItem = (new ChatItemFactory())->create($isApi);

            foreach ($menuItems as $index => $menuItem) {
                if($chatItem->getPriority() < $menuItem->getPriority()) {
                    array_splice($menuItems, $index, 0, [$chatItem]);
                    break;
                }
            }

            if($chatItem->getPriority() > $menuItems[$index]->getPriority()) {
                $menuItems[] = $chatItem;
            }
        }

        if (empty($menuItems)) {
            return null;
        }

        $type = ($menuHeader === Helper::DEFAULT_MENU_ALIAS) ? 'alias' : Helper::getType($menuAlias);
        $content = new ContentData($menuHeader, $type);

        return new MenuData($menuItems, $content);
    }
}
