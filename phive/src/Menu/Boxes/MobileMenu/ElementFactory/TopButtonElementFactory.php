<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\TopButtonData;

final class TopButtonElementFactory
{
    /**
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\TopButtonData
     * @param bool $isLogged
     */
    public function create(bool $isLogged): TopButtonData
    {
        [$alias, $url] = $this->getBigYellowButton($isLogged);

        return new TopButtonData($alias, $url);
    }

    /**
     * @param bool $isLogged
     *
     * @return array
     */
    private function getBigYellowButton(bool $isLogged): array
    {
        return $isLogged
            ? ['deposit', llink('/mobile/cashier/deposit/')]
            : [
                'create.account',
                llink('/mobile/register/'),
            ];
    }
}
