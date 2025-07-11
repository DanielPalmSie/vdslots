<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData;

final class MobileBalanceTableElementFactory
{
    /*
     * @param string $currency
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData|null
     */
    public function create(string $currency): ?MobileBalanceTableData
    {
        if (lic('canFormatMobileBalanceTable')) {
            $data = lic('getMobileBalanceTable');

            if (! empty($data['amount'])) {
                return new MobileBalanceTableData(
                    $data['amount'],
                    $data['label_alias'] ?? "",
                    $data['id'] ?? "",
                    $currency,
                );
            }
        }

        return null;
    }
}
