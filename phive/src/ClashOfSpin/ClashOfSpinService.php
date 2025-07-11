<?php

declare(strict_types=1);

namespace Videoslots\ClashOfSpin;

use ClashOfSpinBoxBase;
use Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinData;
use Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinFilterData;
use Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinResponseData;

require_once __DIR__ . '/../../modules/BoxHandler/boxes/diamondbet/ClashOfSpinBoxBase.php';

final class ClashOfSpinService
{
    private const FILTER_TYPE_ALL = 'clashes.schedule.all.clash.of.spins';
    private const FILTER_ORDER_NEW = 'clashes.schedule.newest';
    private const FILTER_ORDER_OLD = 'clashes.schedule.oldest';
    private const CLASH_OF_SPINS_DESCRIPTION = 'clashes.schedule.how.clash.works';

    private ClashOfSpinBoxBase $box;

    public function __construct()
    {
        $this->box = new ClashOfSpinBoxBase();
    }

    public function getClashOfSpins(ClashOfSpinData $data): ClashOfSpinResponseData
    {
        $this->init($data);
        $filter = new ClashOfSpinFilterData(
            $this->getFilterTypes(),
            $this->getFilterDays(),
            $this->box->vars['time_from'],
            $this->box->vars['time_to'],
            $this->getFilterOder()
        );

        return new ClashOfSpinResponseData(
            $filter,
            $this->getClashes(),
            self::CLASH_OF_SPINS_DESCRIPTION,
            $this->getMainBanner()
        );
    }

    private function init(ClashOfSpinData $data): void
    {
        foreach ($data->getData() as $key => $value)
        {
            if(!is_null($value)) {
                $_POST[$key] = $value;
            }
        }

        $this->box->init();
    }

    private function getFilterDays(): array
    {
        $result = [];
        $today = $this->box->vars['day'];

        foreach ($this->box->days as $data => $day)
        {
            $result[] = [
                'value' => $data,
                'name' => t($day),
                'selected' => $today == $data
            ];

        }

        return $result;
    }

    private function getFilterTypes(): array
    {
        return [
            'value' => '0',
            'name' => t(self::FILTER_TYPE_ALL),
            'selected' => true
        ];
    }

    private function getFilterOder(): array
    {
        $order = $this->box->vars['order'];

        return [
            [
                'value' => '0',
                'name' => t(self::FILTER_ORDER_NEW),
                'selected' => $order == 0
            ],
            [
                'value' => '1',
                'name' => t(self::FILTER_ORDER_OLD),
                'selected' => $order == 1
            ],
        ];
    }

    private function getClashes(): array
    {
        $result = [];

        foreach ($this->box->clashes as $clash)
        {
            $duration = phive()->subtractTimes($clash['end_time'], $clash['start_time'], 'm');

            $result[] = [
                'race_id' => $clash['id'],
                'banner' => self::getRaceBanner($clash['race_type']),
                'template_id' => $clash['template_id'],
                'state' => $clash['state'],
                'race_type' => $clash['race_type'],
                'total_winners' => substr_count($clash['prizes'], ':') + 1,
                'duration' => $duration,
                'tag' => $clash['tag'],
                'start_time' => $clash['start_time'],
                'end_time' => $clash['end_time'],
                'prize' => $this->getPrize($clash)
            ];
        }

        return $result;
    }

    private function getPrize(array $clash): array
    {
        $result = [
            'type' => $clash['prize_type']
        ];

        $prize = explode(':', $clash['prizes'], 2)[0];

        if ($clash['prize_type'] == 'award') {
            $award = phive('Race')->getRaceAwardByPrize($prize, '');
            $image = phive('Trophy')->getAwardUri($award, '');
            $result['image'] = $image;
        } elseif ($clash['prize_type'] == 'cash') {
            $result['cash'] = $this->box->fmtRacePrize($prize);
        }

        if($clash['template_id'] > 0) {
            $result['prize_pool'] = t("clash.t{$clash['template_id']}.prize.pool");
        } else {
            $result['prize_pool'] = t("clash.c{$clash['id']}.prize.pool");
        }

        return $result;
    }

    private function getMainBanner(): string
    {
        return self::getBanner('clash.info.top.image', 'clash_of_spins_banner.jpeg');
    }

    public static function getRaceBanner(string $race_type): string
    {
        if ($race_type == "bigwin") {
            $uri = self::getBanner('clash.bigwin.top.image', 'bigwinclash_clashofspin.jpg');
        } else {
            $uri = self::getBanner('clash.spin.top.image', 'spinclash_Banner.jpg');
        }

        return $uri;
    }

    private static function getBanner(string $alias, string $default_image, int $width = 960, int $height = 307): string
    {
        list($uri) =  phive("ImageHandler")->img(
            $alias,
            $width,
            $height,
            $alias,
            null,
            '/diamondbet/images/' . brandedCss() . 'clashes/' . $default_image
        );

        return phive()->getSiteUrl() . $uri;
    }
}
