<?php

declare(strict_types=1);

namespace Videoslots\RgLimits;

final class RgLimitsBuilder
{
    /**
     * @var \Videoslots\RgLimits\RgLimitsService
     */
    private RgLimitsService $rgLimitsService;

    /**
     * @var \Videoslots\RgLimits\Builders\RgLimitsBuilderInterface[]
     */
    private array $builders;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @param \Videoslots\RgLimits\Builders\RgLimitsBuilderInterface[] $builders
     */
    public function __construct(RgLimitsService $rgLimitsService, array $builders)
    {
        $this->rgLimitsService = $rgLimitsService;
        $this->builders = $builders;
    }

    /**
     * @return array
     */
    public function buildData(): array
    {
        $data = [];

        foreach ($this->builders as $builder) {
            $data = $builder->build($data, $this->rgLimitsService);
        }

        return $data;
    }

    /**
     * @return void
     */
    public function renderData(): void
    {
        $data = $this->buildData();
        foreach ($this->builders as $builder) {
            $builder->render($data, $this->rgLimitsService);
        }
    }
}
