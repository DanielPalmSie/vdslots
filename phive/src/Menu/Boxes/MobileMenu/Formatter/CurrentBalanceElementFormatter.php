<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\CurrentBalanceData;

final class CurrentBalanceElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "current-balance";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\CurrentBalanceData
     */
    private CurrentBalanceData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\CurrentBalanceData $data
     */
    public function __construct(CurrentBalanceData $data)
    {
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function toHtml(): void
    {
        ?>

        <table class="txt-table">
            <tr>
                <td>
                    <span class="medium-bold">
                        <?php et($this->data->getCurrentBalanceAlias()) ?>
                    </span>
                </td>
                <td class="right">
                    <span class="medium-bold header-3">
                        <?= $this->data->getCurrency() ?>
                        <span id="mobile-left-menu-balance">
                            <?= $this->data->getBalance() ?>
                        </span>
                    </span>
                </td>
            </tr>
        </table>

        <?php
    }

    /**
     * @deprecated Will not be returned on API
     *
     * @return array
     */
    public function toJson(): array
    {
        return [
            'element-type' => self::ELEMENT_TYPE,
            'alias' => $this->data->getCurrentBalanceAlias(),
            'currency' => $this->data->getCurrency(),
            'balance' => $this->data->getBalance(),
        ];
    }
}
