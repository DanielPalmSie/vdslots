<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuData;
use Videoslots\Menu\Boxes\MobileMenu\MobileMenuElementsMapper;
use Videoslots\Menu\MenuServiceInterface;

final class SubMenuElementFactory
{
    /**
     * @var string
     */
    public const MENU_ID_MOBILE_SECONDARY_TOP_MENU = 'mobile-secondary-top-menu';

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
     * @param bool $isApi
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuData|null
     */
    public function create(bool $isApi): ?SubMenuData
    {
        $data = $this->menuService->getSecondaryMenuItems(self::MENU_ID_MOBILE_SECONDARY_TOP_MENU);
        if (empty($data)) {
            return null;
        }

        $menuItems = $this->mapper->mapToSubmenuItems($data, $isApi);

        return new SubMenuData($menuItems);
    }
}
