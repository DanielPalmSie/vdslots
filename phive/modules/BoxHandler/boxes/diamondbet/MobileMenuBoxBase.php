<?php

use Videoslots\Menu\Boxes\MobileMenu\MobileMenuFactory;

require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class MobileMenuBoxBase extends DiamondBox
{
    /**
     * @return void
     */
    public function init(): void
    {
        $this->handlePost(['menu', 'menues']);
    }

    /**
     * @return array
     */
    public function getJson(): array
    {
        return $this->processMenuElements(ciso(), false, "toJson", true);
    }

    /**
     * @return void
     */
    public function printHTML(): void
    {
        ?>

        <div class="acc-left-mobile-menu">

        <?php

        $this->processMenuElements(cs(), true, "toHtml");

        ?>

        </div>

        <?php
    }

    /**
     * Common method used for menu rendering.
     * - it can be used on website by passing `toHtml` format and allowing it to just print every element as html.
     * - it can be used on api, gathering all menu elements as array, ready for return in request.
     *
     * @param string $currency
     * @param bool $translate
     * @param string $formatMethod
     * @param bool $isApi
     *
     * @return array
     */
    public function processMenuElements(
        string $currency,
        bool $translate,
        string $formatMethod,
        bool $isApi = false
    ): array
    {
        $result = [];

        $elements = $this->getElements($currency, $translate, $isApi);

        $mobileMenuFormatter = MobileMenuFactory::createMenuFormatter();

        foreach ($elements as $element) {
            $elementClass = get_class($element);
            if ($mobileMenuFormatter->canFormat($elementClass)) {
                $result[] = $mobileMenuFormatter->createFormatter($elementClass, $element)
                    ->$formatMethod();
            }
        }

        return $result;
    }


    /**
     * @return void
     */
    public function printExtra(): void
    {
        ?>

        <p>
            Show one menu when logged out (alias):
            <?php dbInput('menu', $this->menu) ?>
        </p>
        <p>
            Show several menues when logged in (alias1,alias2 ... ):
            <?php dbInput('menues', $this->menues) ?>
        </p>

        <?php
    }

    /**
     * @param string $currency
     * @param bool $translate
     * @param bool $isApi
     *
     * @return array
     */
    private function getElements(string $currency, bool $translate, bool $isApi = false): array
    {
        $mobileMenuBuilder = MobileMenuFactory::createMobileMenuBuilder();

        $builder = $mobileMenuBuilder->build(
            $this->menues ?? "",
            $this->menu ?? "",
            $currency,
            $translate,
            $isApi
        );

        return $builder->getElements();
    }
}
