<?php
namespace IT\Services;

use duncan3dc\Laravel\BladeInstance;
use duncan3dc\Laravel\BladeInterface;

/**
 * Class RenderService
 * @package IT\Services
 */
class RenderService
{
    const TEMPLATE_PATH = __DIR__ . '/../templates/Pacg';
    const TEMPLATE_CACHE_PATH = __DIR__ . '/../templates/cache';

    /**
     * @return BladeInterface
     */
    private function getNewBladeInstance(): BladeInterface
    {
        return new BladeInstance(self::TEMPLATE_PATH, self::TEMPLATE_CACHE_PATH);
    }

    /**
     * @param string $blade_name
     * @param array $data
     * @return string
     */
    public function render(string $blade_name, array $data): string
    {
        $blade = self::getNewBladeInstance();
        return $blade->render($blade_name, $data);
    }
}