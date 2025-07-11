<?php

declare(strict_types=1);

namespace Videoslots\MicroGames\Services;

interface BoxHandlerServiceInterface
{
    /**
     * @param string $attributeName
     * @return string
     */
    public function getBoxAttributeByName(string $attributeName): ?string;

    /**
     * @param int $pageId
     * @param string $attributeName
     *
     * @return string|null
     */
    public function getSubTagsFromBoxesAttributes(int $pageId, string $attributeName): ?string;
}
