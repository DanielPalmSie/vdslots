<?php

declare(strict_types=1);

namespace Videoslots\MicroGames\Services;

use BoxHandler;

final class BoxHandlerService implements BoxHandlerServiceInterface
{
    /**
     * @var string
     */
    public const MG_MOBILE_GAME_BOX_CLASS = 'MgMobileGameChooseBox';

    /**
     * @var string
     */
    public const CACHED_PATH_MOBILE = '/mobile';

    /**
     * @var string
     */
    public const TAGS = 'tags';

    /**
     * @var \BoxHandler
     */
    private BoxHandler $boxHandler;

    /**
     * @var string
     */
    private string $boxClass;

    /**
     * @var string
     */
    private string $cachedPath;

    /**
     * @param string $boxClass
     * @param string $cachedPath
     * @param \BoxHandler $boxHandler
     */
    public function __construct(string $boxClass, string $cachedPath, BoxHandler $boxHandler)
    {
        $this->boxHandler = $boxHandler;
        $this->boxClass = $boxClass;
        $this->cachedPath = $cachedPath;
    }

    /**
     * @param int $pageId
     * @param string $attributeName
     *
     * @return string|null
     */
    public function getSubTagsFromBoxesAttributes(int $pageId, string $attributeName): ?string
    {
        $boxId = $this->getBoxByPageId($pageId);

        if ($boxId === null) {
            return null;
        }

        $result = $this->boxHandler->getAttr($boxId, $attributeName);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function getBoxAttributeByName(string $attributeName): ?string
    {
        $result = $this->boxHandler->getAttr($this->getBoxId(), $attributeName);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @param int $pageId
     *
     * @return string|null
     */
    private function getBoxByPageId(int $pageId): ?string
    {
        $queryString = "SELECT b.box_id
            FROM pages AS p INNER JOIN boxes AS b ON p.page_id = b.page_id
            WHERE b.page_id = $pageId";

        $result = phive('SQL')->getValue($queryString);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getBoxId(): string
    {
        $queryString = sprintf(
            "SELECT b.box_id
            FROM pages AS p INNER JOIN boxes AS b ON p.page_id = b.page_id
            WHERE b.box_class = '%s' AND p.cached_path = '%s'",
            phive('SQL')->escape($this->boxClass, false),
            phive('SQL')->escape($this->cachedPath, false)
        );

        return phive('SQL')->getValue($queryString);
    }
}
