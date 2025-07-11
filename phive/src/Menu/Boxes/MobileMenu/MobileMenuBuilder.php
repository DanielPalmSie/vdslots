<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu;

use Videoslots\Menu\Boxes\MobileMenu\Element\ContentData;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\BonusBalanceElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\CurrentBalanceElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\HeadLineElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\LastLoginBalanceElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\MenuElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\MobileBalanceTableElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\PaymentServiceProviderElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\SeparatorElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\SubMenuElementFactory;
use Videoslots\Menu\Boxes\MobileMenu\ElementFactory\TopButtonElementFactory;
use Videoslots\Menu\MenuServiceInterface;

final class MobileMenuBuilder
{
    /**
     * @var array
     */
    private array $elements = [];

    /**
     * @var string
     */
    private string $currency;

    /**
     * @var \Videoslots\Menu\MenuServiceInterface
     */
    private MenuServiceInterface $menuService;

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\MobileMenuElementsMapper
     */
    private MobileMenuElementsMapper $mapper;

    /**
     * @var bool
     */
    private bool $translate;

    /**
     * @var null|string
     */
    private ?string $currentBalance;

    /**
     * @var bool
     */
    private bool $isLogged;

    /**
     * @var string
     */
    private string $menu;

    /**
     * @var string
     */
    private string $menues;

    /**
     * @var bool
     */
    private bool $isApi;

    /**
     * @param \Videoslots\Menu\MenuServiceInterface $menuService
     * @param \Videoslots\Menu\Boxes\MobileMenu\MobileMenuElementsMapper $mapper
     */
    public function __construct(
        MenuServiceInterface $menuService,
        MobileMenuElementsMapper $mapper
    ) {
        $this->menuService = $menuService;
        $this->mapper = $mapper;
    }

    /**
     * @param string $menues
     * @param string $menu
     * @param string $currency
     * @param bool $translate
     * @param bool $isApi
     *
     * @return $this
     */
    public function build(
        string $menues,
        string $menu,
        string $currency,
        bool $translate,
        bool $isApi = true
    ): self {
        $this->currency = $currency;
        $this->isLogged = $this->isLogged();
        $this->translate = $translate;
        $this->currentBalance = $this->getCurrentBalance();
        $this->menu = $menu;
        $this->menues = $menues;
        $this->isApi = $isApi;

        if (! $isApi) {
            $this->addTopButton();
        }

        if (! $this->isLogged) {
            $this->addSubMenu();

            if (! $isApi) {
                $this->addHeadline();
            }

            $this->addMenu();
        } else {
            if (! $isApi) {
                $this->addFastPaymentServiceProvider()
                    ->addBalance()
                    ->addBonusBalance()
                    ->addMobileBalanceTable();
            }

            $this->addSubMenu()
                ->addMenu();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getElements(): array
    {
        return array_filter($this->elements, fn ($item) => ! is_null($item));
    }

    /**
     * @return self
     */
    private function addTopButton(): self
    {
        $this->elements[] = (new TopButtonElementFactory())->create($this->isLogged);

        return $this;
    }

    /**
     * @return self
     */
    private function addSubMenu(): self
    {
        $this->elements[] = (new SubMenuElementFactory($this->menuService, $this->mapper))
            ->create($this->isApi);

        return $this;
    }

    /**
     *
     * @return self
     */
    private function addHeadline(): self
    {
        $content = new ContentData(Helper::DEFAULT_MENU_ALIAS, 'alias');
        $this->elements[] = (new HeadLineElementFactory())->create($content);

        return $this;
    }

    /**
     * @param string $alias
     *
     * @return self
     */
    private function addSeparator(string $alias): self
    {
        $this->elements[] = (new SeparatorElementFactory())->create($alias);

        return $this;
    }

    /**
     * @return self
     */
    private function addMenu(): self
    {
        if (! $this->isLogged) {
            $this->addMenuElement($this->menu, Helper::DEFAULT_MENU_ALIAS, $this->isApi);
        } else {
            $menuAliases = $this->getMenuAliases($this->menu, $this->menues);

            foreach ($menuAliases as $menuAlias) {
                if (! $this->isApi) {
                    $this->addSeparator($menuAlias);
                }

                $menuHeaders = (new SeparatorElementFactory())->create($menuAlias)->getAlias();

                $this->addMenuElement($menuAlias, $menuHeaders, $this->isApi);
            }
        }

        return $this;
    }

    /**
     * @param string $menuAlias
     * @param string $menuHeader
     * @param bool $isApi
     *
     * @return self
     */
    private function addMenuElement(string $menuAlias, string $menuHeader, bool $isApi): self
    {
        $this->elements[] = (new MenuElementFactory($this->menuService, $this->mapper))
            ->create($menuAlias, $this->translate, $menuHeader, $isApi);

        return $this;
    }

    /**
     * @return self
     */
    private function addBonusBalance(): self
    {
        $this->elements[] = (new BonusBalanceElementFactory())->create($this->currency);

        return $this;
    }

    /**
     * @return self
     */
    private function addFastPaymentServiceProvider(): self
    {
        $this->elements[] = (new PaymentServiceProviderElementFactory())->create();

        return $this;
    }

    /**
     * @return self
     */
    private function addMobileBalanceTable(): self
    {
        $this->elements[] = (new MobileBalanceTableElementFactory())->create($this->currency);

        return $this;
    }

    /**
     * Methods adds "Last login balance" if available.
     * Otherwise adds "Current Balance"
     *
     * @return self
     */
    private function addBalance(): self
    {
        $element = (new LastLoginBalanceElementFactory())
            ->create($this->currency, $this->currentBalance);

        if (is_null($element)) {
            $element = (new CurrentBalanceElementFactory())->create($this->currency, $this->currentBalance);
        }

        $this->elements[] = $element;

        return $this;
    }

    /**
     * @return string|null
     */
    private function getCurrentBalance(): ?string
    {
        $balance = "";
        if ($this->isLogged) {
            $balance = phive("QuickFire")->parentBalance(cu()->getId());
        }

        return nf2($balance, true, 1, '.', '');
    }

    /**
     * @return bool
     */
    private function isLogged(): bool
    {
        $user = cu();
        if (! is_null($user) && $user !== false) {
            return isLogged($user->getId());
        }

        return false;
    }

    /**
     * @param string $menu
     * @param string $menues
     *
     * @return array
     */
    private function getMenuAliases(string $menu, string $menues): array
    {
        if (! $this->isLogged) {
            return [$menues];
        }

        return empty($menues)
            ? $this->menuService->getMenuItems($menu, $this->translate)
            : explode(',', $menues);
    }
}
