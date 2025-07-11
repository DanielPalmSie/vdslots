<?php

declare(strict_types=1);

namespace Videoslots\User\CustomLoginTop\Factory;

use Videoslots\User\CustomLoginTop\CustomLoginTop;

final class CustomLoginTopFactory
{
    /**
     * @param string $context
     *
     * @return \Videoslots\User\CustomLoginTop\CustomLoginTop
     */
    public function create(string $context): CustomLoginTop
    {
        if (! $this->hasCustomLoginTop($context)) {
            return $this->createDefault();
        }

        $custom_login_top = lic('getCustomLoginTop', [$context]);

        if ($custom_login_top === false) {
            return $this->createDefault();
        }

        return $custom_login_top;
    }

    /**
     * @param string $context
     *
     * @return bool
     */
    public function hasCustomLoginTop(string $context): bool
    {
        return $context === "login"
            && lic('methodExists', ['customLoginTop']) === true;
    }

    /**
     * @return \Videoslots\User\CustomLoginTop\CustomLoginTop
     */
    private function createDefault(): CustomLoginTop
    {
        return new class () implements CustomLoginTop {
            public function toArray(): array
            {
                return [];
            }
        };
    }
}
