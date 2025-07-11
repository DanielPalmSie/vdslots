<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\PaymentServiceProviderData;

final class PaymentServiceProviderElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "payment-service";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\PaymentServiceProviderData
     */
    private PaymentServiceProviderData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\PaymentServiceProviderData $data
     */
    public function __construct(PaymentServiceProviderData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        fastDepositIcon($this->data->getType(), false, $this->data->getProvider());
    }

    /**
     * @deprecated Will not be returned on API
     *
     * @return array
     */
    public function toJson(): array
    {
        return [
            'element-type' => self::ELEMENT_TYPE,
            'type' => $this->data->getType(),
            'provider' => $this->data->getProvider(),
        ];
    }
}
