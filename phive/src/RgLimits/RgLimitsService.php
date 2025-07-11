<?php

declare(strict_types=1);

namespace Videoslots\RgLimits;

use DBUser;
use RgLimits;
use Videoslots\RgLimits\Builders\SingleResetablesBuilder;
use Videoslots\Services\Renderer\Renderer;
use Videoslots\Services\Renderer\RendererInterface;

final class RgLimitsService
{
    /**
     * @var \RgLimits
     */
    private RgLimits $rgLimits;

    /**
     * @var \DBUser
     */
    private DBUser $user;

    /**
     * @var array
     */
    private array $limits;

    /**
     * @var string
     */
    private string $cooloffPeriod;

    /**
     * @var \Videoslots\Services\Renderer\RendererInterface
     */
    private Renderer $renderer;

    /**
     * @var bool
     */
    private bool $isApi;

    /**
     * @param \RgLimits $rgLimits
     * @param \DBUser $user
     * @param bool $isApi
     * @param \Videoslots\Services\Renderer\RendererInterface $renderer
     */
    public function __construct(RgLimits $rgLimits, DBUser $user, RendererInterface $renderer, bool $isApi)
    {
        $this->rgLimits = $rgLimits;
        $this->user = $user;
        $this->cooloffPeriod = (string) lic('getCooloffPeriod', [$user->getCountry()]);
        $this->limits = $this->rgLimits->getGrouped($user, [], true);
        $this->renderer = $renderer;
        $this->isApi = $isApi;
    }

    /**
     * @param string $tspan
     * @param string $type
     *
     * @return bool
     */
    private function hasDefaultLimits(string $tspan, string $type): bool
    {
        $singleResettableLimits = SingleResetablesBuilder::getResetableLimits();

        if ($type == 'betmax' && $this->limits['betmax']) {
            return false;
        }

        if ($tspan == 'na' && ! in_array($type, $singleResettableLimits)) {
            return false;
        }

        if ($tspan != 'na' && in_array($type, $singleResettableLimits)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $type
     * @param string|null $groupname
     *
     * @return array
     */
    public function getLimitData(string $type, string $groupname = null): array
    {
        if (is_null($groupname)) {
            $groupname = $type;
        }

        $limits = $this->limits[$type];
        $changesAt = '';
        $timeSpans = array_merge(['na'], $this->rgLimits->time_spans);
        $limitsArray = [];
        foreach ($timeSpans as $tspan) {
            $rgl = $limits[$tspan];
            if (is_null($rgl)) {
                if (! $this->hasDefaultLimits($tspan, $type)) {
                    continue;
                }
                $rgl = $this->getDefaultLimitData($tspan);
            }

            if (empty($changesAt) && ! empty($rgl['changes_at'])) {
                $changesAt = $rgl['changes_at'];
            }

            unset($rgl['changes_at']);

            $newLimit = $this->rgLimits->prettyLimit($type, $rgl['new_lim'], false, !$this->isApi);
            $curLimit = $this->rgLimits->prettyLimit($type, $rgl['cur_lim']);
            $progress = $this->rgLimits->prettyLimit($type, $rgl['progress']);

            $additionalData = [
                'bullet_options' => [],
                'checkboxes_options' => [],
                'values' => [
                    'rem' => $this->rgLimits->prettyLimit($type, $this->rgLimits->getRemaining($rgl)),
                    'new' => $newLimit,
                    'cur' => $curLimit,
                ],
            ];

            $rgl['cur_lim'] = $curLimit;
            $rgl['new_lim'] = $newLimit;
            $rgl['progress'] = $progress;

            $limitsArray[] = array_merge($rgl, $additionalData);
        }

        return [
            'type' => $type,
            'limits' => $limitsArray,
            'buttons' => $this->buildButtons($type),
            'headline' => $groupname . '.limit.headline',
            'subheadline' => '',
            'description' => [
                $groupname . '.limit.info.html',
            ],
            'changes_at' => $changesAt,
            'disp_unit' => $this->rgLimits->displayUnit($type, $this->user),
            'cooloffPeriod' => $this->getCooloffPeriod(),
        ];
    }

    /**
     * @return \RgLimits
     */
    public function getRgLimits(): RgLimits
    {
        return $this->rgLimits;
    }

    /**
     * @return \DBUser
     */
    public function getUser(): DBUser
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getCooloffPeriod(): string
    {
        return $this->cooloffPeriod;
    }

    /**
     * @return \Videoslots\Services\Renderer\Renderer
     */
    public function getRenderer(): Renderer
    {
        return $this->renderer;
    }

    /**
     * @param string $type
     *
     * @return array[]
     */
    private function buildButtons(string $type): array
    {
        $buttons = [
            [
                'type' => 'save',
                'alias' => 'set.a.limit',
            ],
        ];

        if (! lic('hideRgRemoveLimit', [$type], $this->getUser())) {
            $buttons[] = [
                'type' => 'remove',
                'alias' => 'remove.limits',
            ];
        }

        return $buttons;
    }

    /**
     * @param string $tspan
     *
     * @return string[]
     */
    private function getDefaultLimitData(string $tspan): array
    {
        return [
            'id' => '',
            'user_id' => '',
            'cur_lim' => '',
            'new_lim' => '',
            'time_span' => $tspan,
            'progress' => '',
            'started_at' => '0000-00-00 00:00:00',
            'resets_at' => '0000-00-00 00:00:00',
            'changes_at' => '0000-00-00 00:00:00',
            'forced_until' => '0000-00-00 00:00:00',
            'extra' => '',
            'created_at' => '0000-00-00 00:00:00',
            'updated_at' => '0000-00-00 00:00:00',
            'old_lim' => '0',
        ];
    }
}
