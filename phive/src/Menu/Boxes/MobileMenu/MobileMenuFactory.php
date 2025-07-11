<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu;

use Videoslots\Menu\Boxes\MobileMenu\Element\BonusBalanceData;
use Videoslots\Menu\Boxes\MobileMenu\Element\CurrentBalanceData;
use Videoslots\Menu\Boxes\MobileMenu\Element\HeadLineData;
use Videoslots\Menu\Boxes\MobileMenu\Element\LastLoginBalanceData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemDepositData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MenuItemWithdrawalData;
use Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData;
use Videoslots\Menu\Boxes\MobileMenu\Element\PaymentServiceProviderData;
use Videoslots\Menu\Boxes\MobileMenu\Element\SeparatorData;
use Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuData;
use Videoslots\Menu\Boxes\MobileMenu\Element\TopButtonData;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\BonusBalanceElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\CurrentBalanceElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\HeadLineElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\LastLoginElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\MenuElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\MenuItemElementDepositFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\MenuItemElementWithdrawalFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\MobileBalanceTableElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\PaymentServiceProviderElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\SeparatorElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\SubMenuElementFormatter;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\TopButtonElementFormatter;
use Videoslots\Menu\MenuService;

final class MobileMenuFactory
{
    /**
     * @return \Videoslots\Menu\Boxes\MobileMenu\MobileMenuBuilder
     */
    public static function createMobileMenuBuilder(): MobileMenuBuilder
    {
        return new MobileMenuBuilder(
            new MenuService(),
            new MobileMenuElementsMapper(),
        );
    }

    /**
     * @return \Videoslots\Menu\Boxes\MobileMenu\MobileMenuFormatter
     */
    public static function createMenuFormatter(): MobileMenuFormatter
    {
        $renderers = [
            TopButtonData::class => TopButtonElementFormatter::class,
            PaymentServiceProviderData::class => PaymentServiceProviderElementFormatter::class,
            LastLoginBalanceData::class => LastLoginElementFormatter::class,
            CurrentBalanceData::class => CurrentBalanceElementFormatter::class,
            BonusBalanceData::class => BonusBalanceElementFormatter::class,
            MobileBalanceTableData::class => MobileBalanceTableElementFormatter::class,
            HeadLineData::class => HeadLineElementFormatter::class,
            SubMenuData::class => SubMenuElementFormatter::class,
            SeparatorData::class => SeparatorElementFormatter::class,
            MenuData::class => MenuElementFormatter::class,
            MenuItemDepositData::class => MenuItemElementDepositFormatter::class,
            MenuItemWithdrawalData::class => MenuItemElementWithdrawalFormatter::class,
        ];

        return new MobileMenuFormatter($renderers);
    }
}
