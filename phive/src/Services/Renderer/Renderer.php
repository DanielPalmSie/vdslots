<?php

declare(strict_types=1);

namespace Videoslots\Services\Renderer;

use InvalidArgumentException;

final class Renderer implements RendererInterface
{
    /**
     * @var string
     */
    private string $templatePath;

    public function __construct()
    {
        $this->templatePath = __DIR__ . "/../../../templates";
    }

    /**
     * @param string $path
     * @param array $params
     *
     * @return string
     */
    public function render(string $path, array $params = []): string
    {
        if (empty($path)) {
            throw new InvalidArgumentException("method parameter \$template cannot be empty.");
        }

        $templatePath = $this->getTemplatePath($path);

        extract($params, EXTR_SKIP);

        ob_start();

        require $templatePath;

        return ob_get_clean();
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getTemplatePath(string $path): string
    {
        $filePath = str_replace(".", DIRECTORY_SEPARATOR, $path) . ".php";

        return $this->templatePath . DIRECTORY_SEPARATOR . $filePath;
    }
}
