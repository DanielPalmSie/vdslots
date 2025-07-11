<?php

namespace Videoslots\RgLimits\Traits;

trait RgLimitValidationHelper
{
    /**
     * @param array $content
     * @return array[]
     */
    private function createResponse(array $content): array
    {
        return [
            self::TYPE => [
                'code' => self::CODE,
                'content' => $content,
            ],
        ];
    }

    /**
     * @param string $value
     * @param string $type
     * @param array $data
     * @return array
     */
    private function createContent(string $value, string $type, array $data): array
    {
        return [
            'value' => $value,
            'type' => $type,
            'data' => $data,
        ];
    }
}
