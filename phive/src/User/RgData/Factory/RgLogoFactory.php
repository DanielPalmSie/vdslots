<?php

declare(strict_types=1);

namespace Videoslots\User\RgData\Factory;

use Videoslots\User\RgData\RgLogo;

final class RgLogoFactory
{
    /**
     * @return \Videoslots\User\RgData\RgLogo
     */
    public function create(): RgLogo
    {
        if (! $this->hasRgLogo()) {
            return $this->createDefault();
        }

        $type = lic('getRgLogosMobileType') ?? "white";

        return lic('getRgLogoData', [$type]);
    }

    /**
     * @return bool
     */
    private function hasRgLogo(): bool
    {
        return lic('hasRgLogo') === true;
    }

    /**
     * @return \Videoslots\User\RgData\RgLogo
     */
    private function createDefault(): RgLogo
    {
        return new class () implements RgLogo {
            public function toArray(): array
            {
                return [];
            }
        };
    }
}
