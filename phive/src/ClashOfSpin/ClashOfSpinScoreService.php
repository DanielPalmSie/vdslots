<?php

declare(strict_types=1);

namespace Videoslots\ClashOfSpin;

use ClashOfSpinScoreBoxBase;
use Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinScoreData;
use Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinScoreResponseData;

require_once __DIR__ . '/../../modules/BoxHandler/boxes/diamondbet/ClashOfSpinScoreBoxBase.php';

final class ClashOfSpinScoreService
{
    private const BIG_WIN_CLASH_DESCRIPTION = 'clash.of.spins.bigwin.description';
    private const SPIN_CLASH_DESCRIPTION = 'clash.of.spins.description';
    private const BIG_WIN_CLASH_TERMS_CONDITIONS = 'clash.of.spins.bigwin.prices.terms.conditions';
    private const SPIN_CLASH_TERMS_CONDITIONS = 'clash.of.spins.prices.terms.conditions';
    private const WINS = [
        15 => 'clash.of.spins.bigwin.bigwin',
        30 => 'clash.of.spins.bigwin.megawin',
        60 => 'clash.of.spins.bigwin.supermegawin'
    ];

    private ClashOfSpinScoreBoxBase $box;

    public function __construct()
    {
        $this->box = new ClashOfSpinScoreBoxBase();
    }

    /**
     * @param \Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinScoreData $data
     *
     * @return \Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinScoreResponseData|null
     */
    public function getClashOfSpinScore(ClashOfSpinScoreData $data): ?ClashOfSpinScoreResponseData
    {
        $this->init($data);

        if(is_null($this->box->race['template_id'])) {
            return null;
        }

        return new ClashOfSpinScoreResponseData(
            'clash.of.spins.race.'.$this->box->race['race_type'],
            ClashOfSpinService::getRaceBanner($this->box->race['race_type']),
            $this->getDescription(),
            $this->getTermsConditions(),
            $this->getPointsTable(),
            $this->getRaceTable($data->getLimit())
        );
    }

    /**
     * @param \Laraphive\Domain\Content\DataTransferObjects\ClashOfSpin\ClashOfSpinScoreData $data
     *
     * @return void
     */
    private function init(ClashOfSpinScoreData $data): void
    {
        foreach ($data->getData() as $key => $value)
        {
            if(!is_null($value)) {
                $_GET[$key] = $value;
            }
        }

        $this->box->init();
    }

    /**
     * @return string
     */
    private function getDescription(): string
    {
        if($this->box->race['race_type'] == 'bigwin')
        {
            return self::BIG_WIN_CLASH_DESCRIPTION;
        }

        return self::SPIN_CLASH_DESCRIPTION;
    }

    /**
     * @return string
     */
    private function getTermsConditions(): string
    {
        if($this->box->race['race_type'] == 'bigwin')
        {
            return self::BIG_WIN_CLASH_TERMS_CONDITIONS;
        }

        return self::SPIN_CLASH_TERMS_CONDITIONS;
    }

    /**
     * @return array
     */
    private function getPointsTable(): array
    {
        $result = [];
        $levels = explode('|', $this->box->race['levels']);

        foreach ($levels as $key => $level) {
            $levels[$key] = explode(':', $level);
        }

        $siteUrl = phive()->getSiteUrl();
        $result['headers'] = [
            'bet_image' => '',
            'amount' => t('clash.of.spins.bet.spin.amount'),
            'point_image' => '',
            'points' => t('clash.of.spins.number.of.points')
        ];

        foreach ($levels as $level) {
            $result['list'][] = [
                'bet_image' => $siteUrl . '/diamondbet/images/'. brandedCss() .'clashes/Bet_ClashofSpins.png',
                'amount' => $this->getPointsTableAmount((int) $level[0]),
                'point_image' => $siteUrl . '/diamondbet/images/'. brandedCss() .'clashes/points_Clashofspins.png',
                'points' => $level[1] . ' ' . ($level[1] == 1 ? t('clash.of.spins.point') : t('clash.of.spins.points'))
            ];
        }

        return $result;
    }

    /**
     * @param int $level
     *
     * @return string
     */
    private function getPointsTableAmount(int $level): string
    {
        if($this->box->race['race_type'] == 'bigwin') {
            return t(self::WINS[$level]);
        }

        return $this->box->fmtRacePrize($level);
    }

    /**
     * @param int|null $limit
     *
     * @return array
     */
    private function getRaceTable(?int $limit): array
    {
        [$entries, $prizes, $winners] = $this->box->getRaceData($this->box->race, $limit);
        $list = [];
        $race = $this->box->race;
        $siteUrl = phive()->getSiteUrl();

        $i = count(array_filter($entries, function($el) {
            return $el['user_id'] == $this->box->cur_uid;
        })) > 0 ? 3 : 4;

        foreach ($prizes as $key => $p) {
            $entry = $entries[$key];
            $prize = $this->box->getPrize($p, $race['prize_type'], '', phive()->isMobile());
            $list[] = [
                'number' => !empty($this->box->cur_uid) && $entry['user_id'] == $this->box->cur_uid ? $entry['spot'] : $i,
                'award_image' => $siteUrl . '/diamondbet/images/' . brandedCss() . 'clashes/Award_All_ClashofSpins.png',
                'first_name' => $this->box->printUserName($entry, $i, true),
                'points' => $this->box->printRaceAmount($entry['race_balance'], $race, true),
                'prize_image' => $prize['image'],
                'prize' => $prize['description']

            ];

            $i++;
        }

        return [
            'winners' => $this->getRaceWinners($winners),
            'headers' => [
                'number' => t('clash.of.spins.no'),
                'award_image' => '',
                'first_name' => t('rakerace.firstname'),
                'points' => t('rakerace.' . $race['race_type'] . '.place'),
                'prize_image' => '',
                'prize' => t('rakerace.prize')
            ],
            'list' => $list,
        ];
    }

    /**
     * @param array $winners
     *
     * @return array
     */
    private function getRaceWinners(array $winners): array
    {
        $result = [];
        $siteUrl = phive()->getSiteUrl();
        $race = $this->box->race;
        $i = 1;

        foreach ($winners as $winner) {
            $result[] = [
                'prize' => [
                    'icon' => $siteUrl . $winner['icon'],
                    'value' => $this->box->getPrize($winner['prize'], $race['prize_type'])['description']
                ],
                'points' => [
                    'value' => $this->box->printRaceAmount($winner['entry']['race_balance'], $race, true),
                    'title' => t("rakerace.{$race['race_type']}.place")
                ],
                'place' => t("clash.of.spins.winners$i"),
                'user_name' => $this->box->printUserName($winner['entry'], null, true)
            ];
            $i++;
        }

        return $result;
    }
}
