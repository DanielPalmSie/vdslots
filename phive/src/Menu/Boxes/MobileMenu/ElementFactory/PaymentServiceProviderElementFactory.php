<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\PaymentServiceProviderData;

final class PaymentServiceProviderElementFactory
{
    /**
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\PaymentServiceProviderData|null
     */
    public function create(): ?PaymentServiceProviderData
    {
        $provider = getPaymentServiceProvider();
        if (! is_null($provider)) {
            return new PaymentServiceProviderData($provider, "mobile-menu");
        }

        return null;
    }
}
