<?php

declare(strict_types=1);

namespace Videoslots\MicroGames\Services;

use Laraphive\Domain\Casino\Settings\GamesSettings;
use MgGameChooseBoxBase;
use MgGamePayoutBox;
use Laraphive\Domain\Casino\DataTransferObjects\GamesFilterData;

require_once __DIR__ . '/../../../modules/BoxHandler/boxes/diamondbet/MgGameChooseBoxBase.php';
require_once __DIR__ . '/../../../../diamondbet/boxes/MgGamePayoutBox.php';

final class GamesFilterService
{
    /**
     * @var String
     */
    private const BOX_CLASS = 'MgGameChooseBox';
    private const BOX_CLASS_MOBILE = 'MgMobileGameChooseBox';

    /**
     * @var \MgGameChooseBoxBase|null
     */
    private ?MgGameChooseBoxBase $box = null;

    /**
     * @var string
     */
    private string $category;

    /**
     * @param string $category
     * @param string $provider
     * @param string $type
     */
    public function __construct(string $category, string $provider, string $type)
    {
        $this->category = $category == 'home' ? '.' : $category;
        $box_id = $this->findBoxIdByAlias();

        if ($box_id) {
            $this->box = new MgGameChooseBoxBase($box_id);

            $this->box->init(true, $provider, $type);
        } else {
            Throw new \RuntimeException('Box not found');
        }
    }

    /**
     * @return \Laraphive\Domain\Casino\DataTransferObjects\GamesFilterData
     */
    public function getGamesFilters(): GamesFilterData
    {
        $operators = ['all' => t('all.providers')] + $this->box->operators;
        $types = ['all' => t('all.' . $this->box->str_tag)] + $this->box->subsel;

        return new GamesFilterData($operators, $types);
    }

    /**
     * @return int|null
     */
    private function findBoxId(): ?int
    {
        $page_id = $this->getPageId();

        if (is_null($page_id)) {
            return null;
        }

        $query = sprintf(
            "SELECT box_id FROM boxes WHERE page_id = %s AND box_class = '%s'",
            $page_id,
            self::BOX_CLASS
        );

        return (int) phive('SQL')->getValue($query);
    }

    public function findBoxIdByAlias()
    {
        $category = GamesSettings::getCategoryPageMapping()[$this->category] ?? $this->category;
        $query = sprintf(
            'SELECT box_id FROM boxes b LEFT JOIN pages p ON b.page_id = p.page_id WHERE b.box_class = "%s" and p.alias = "%s"',
            phive()->isMobile() ? self::BOX_CLASS_MOBILE : self::BOX_CLASS,
            $category
        );

        return  (int) phive('SQL')->getValue($query);
    }


    /**
     * @return int|null
     */
    private function getPageId(): ?int
    {
        $pager = phive('Pager');
        $page = $pager->getPageByAlias($this->category, 0);

        if (is_null($page)) {
            return null;
        }

        return (int)$page['page_id'];
    }

    /**
     * Get the payout ratios filters.
     *
     * @return array
     */
    public static function getPayoutRatiosFilters(): array
    {
        $mgGamePayoutBox = self::initializePayoutBox();
        return self::extractPayoutFilters($mgGamePayoutBox);
    }

    /**
     * Initialize the payout box.
     *
     * @return \MgGamePayoutBox
     */
    public static function initializePayoutBox(): MgGamePayoutBox
    {
        $mgGamePayoutBox = new MgGamePayoutBox();
        $mgGamePayoutBox->init();
        return $mgGamePayoutBox;
    }

    /**
     * Extract the payout filters from the payout box.
     *
     * @param \MgGamePayoutBox $mgGamePayoutBox The payout box.
     *
     * @return array
     */
    private static function extractPayoutFilters(MgGamePayoutBox $mgGamePayoutBox): array
    {
        return [
            'months' => $mgGamePayoutBox->getMonths(),
            'order' => $mgGamePayoutBox->order_by,
            'payout_type' => $mgGamePayoutBox->payout_type,
            'per_page' => $mgGamePayoutBox->per_page,
        ];
    }
}
