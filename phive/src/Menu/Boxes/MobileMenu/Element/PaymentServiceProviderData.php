<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class PaymentServiceProviderData
{
    /**
     * @var string
     */
    private string $provider;

    /**
     * @var string
     */
    private string $type;

    /**
     * @param string $provider
     * @param string $type
     */
    public function __construct(string $provider, string $type)
    {
        $this->provider = $provider;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
