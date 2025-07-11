<?php

namespace Videoslots\Services\Renderer;

interface RendererInterface
{
    /**
     * @param string $path
     * @param array $params
     *
     * @return string
     */
    public function render(string $path, array $params = []): string;
}
