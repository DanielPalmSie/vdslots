<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Mappers;

use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\LimitsData;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\LimitTypesData;

final class RgLimitsMapper
{
    /**
     * @var \RgLimits
     */
    private \RgLimits $rg;

    /**
     * @var \DBUser
     */
    private \DBUser $user;

    /**
     * @var bool
     */
    private bool $hasBalanceTypeLimit;

    /**
     * @param \RgLimits $rg
     * @param \DBUser $user
     */
    public function __construct(\RgLimits $rg, \DBUser $user)
    {
        $this->rg = $rg;
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return array<\Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\LimitTypesData>
     */
    public function mapDataInDto(array $data): array
    {
        $rgLimitsTypes = [];

        $this->hasBalanceTypeLimit = lic('hasBalanceTypeLimit');

        foreach ($data as $type => $items) {
            if ($items === null) {
                continue;
            }
            $rgLimitsTypes[] = new LimitTypesData(
                $type,
                sprintf('rg.info.%s.limits', $type),
                $this->user->getCurrency(),
                $this->getDefaultRgLimits($items)
            );
        }

        return $rgLimitsTypes;
    }

    /**
     * @param array $data
     *
     * @return array<\Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\LimitsData>
     */
    private function getDefaultRgLimits(array $data): array
    {
        $limits = [];

        foreach ($data as $key => $items) {
            $remaining = null;

            if ($this->hasBalanceTypeLimit) {
                $remaining = (string) $this->rg->getRemaining($items);
            }

            $limits[$key] = new LimitsData(
                $items['id'],
                $items['user_id'],
                $this->rg->prettyLimit($items['type'], $items['cur_lim']),
                $items['time_span'],
                $this->rg->prettyLimit($items['type'], $remaining)
            );
        }

        return $limits;
    }
}
