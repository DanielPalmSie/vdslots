<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData;

final class MobileBalanceTableElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "mobile-balance-table";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData
     */
    private MobileBalanceTableData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData $data
     */
    public function __construct(MobileBalanceTableData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        if (lic('canFormatMobileBalanceTable')) {
            lic('formatMobileBalanceTableToHtml', [$this->data]);
        }
    }

    /**
     * @deprecated Will not be returned on API
     *
     * @return array
     */
    public function toJson(): array
    {
        if (lic('canFormatMobileBalanceTable')) {
            return lic('formatMobileBalanceTableToJson', [$this->data]);
        }


        return [];
    }
}
