<?php

declare(strict_types=1);

namespace Videoslots\User\RgData;

final class RgLogoForSE implements RgLogo
{
    /**
     * @var string
     */
    private string $spelpausUrl;

    /**
     * @var string
     */
    private string $spelpausImage;

    /**
     * @var string
     */
    private string $gamTestUrl;

    /**
     * @var string
     */
    private string $gamTestImage;

    /**
     * @var string
     */
    private string $respGamingUrl;

    /**
     * @var string
     */
    private string $respGamingImage;

    /**
     * @param string $spelpausUrl
     * @param string $spelpausImage
     * @param string $gamTestUrl
     * @param string $gamTestImage
     * @param string $respGamingUrl
     * @param string $respGamingImage
     */
    public function __construct(
        string $spelpausUrl,
        string $spelpausImage,
        string $gamTestUrl,
        string $gamTestImage,
        string $respGamingUrl,
        string $respGamingImage
    ) {
        $this->spelpausUrl = $spelpausUrl;
        $this->spelpausImage = $spelpausImage;
        $this->gamTestUrl = $gamTestUrl;
        $this->gamTestImage = $gamTestImage;
        $this->respGamingUrl = $respGamingUrl;
        $this->respGamingImage = $respGamingImage;
    }

    /**
     * @return string
     */
    public function getSpelpausUrl(): string
    {
        return $this->spelpausUrl;
    }

    /**
     * @return string
     */
    public function getSpelpausImage(): string
    {
        return $this->spelpausImage;
    }

    /**
     * @return string
     */
    public function getGamTestUrl(): string
    {
        return $this->gamTestUrl;
    }

    /**
     * @return string
     */
    public function getGamTestImage(): string
    {
        return $this->gamTestImage;
    }

    /**
     * @return string
     */
    public function getRespGamingUrl(): string
    {
        return $this->respGamingUrl;
    }

    /**
     * @return string
     */
    public function getRespGamingImage(): string
    {
        return $this->respGamingImage;
    }

    /**
     * @return array[]
     */
    public function toArray(): array
    {
        return [
            [
                'type' => 'link',
                'url' => $this->spelpausUrl,
                'image' => $this->spelpausImage,
                'target' => '_blank',
            ],
            [
                'type' => 'button',
                'url' => $this->gamTestUrl,
                'action' => 'DO_GAM_TEST',
                'image' => $this->gamTestImage,
            ],
            [
                'type' => 'link',
                'url' => $this->respGamingUrl,
                'image' => $this->respGamingImage,
            ],
        ];
    }
}
