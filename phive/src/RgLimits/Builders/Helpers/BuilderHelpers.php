<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders\Helpers;

trait BuilderHelpers
{
    /**
     * Transforms input array of button objects [{ type: string, alias: string}, ...] into assoc array, with `type`
     * as key and `alias` as value
     *
     * Example input:
     *
     * $buttons = [
     *   [
     *     'type'  => 'save',
     *     'alias' => 'save.alias',
     *   ], [
     *     'type'  => 'rest',
     *     'alias' => 'lock.alias',
     *   ]
     * ];
     *
     * Output:
     * $buttons = [
     *   'save' => 'save.alias',
     *   'lock' => 'lock.alias',
     * ]
     *
     * @param array $buttons
     *
     * @return array
     */
    public function groupButtonsByType(array $buttons): array
    {
        $result = [];

        foreach ($buttons as $button) {
            $result[$button['type']] = $button['alias'];
        }

        return $result;
    }
}
