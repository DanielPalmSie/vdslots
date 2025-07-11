<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\BonusBalanceData;

final class BonusBalanceElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "bonus-balance";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\BonusBalanceData
     */
    private BonusBalanceData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\BonusBalanceData $data
     */
    public function __construct(BonusBalanceData $data)
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
                        <?php et($this->data->getBonusBalanceAlias()) ?>
                    </span>
                </td>
                <td class="right">
                    <span class="medium-bold header-3">
                        <?= $this->data->getCurrency() ?>
                        <span id="mobile-left-menu-bonus-balance">
                            <?= $this->data->getBonusBalance() ?>
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
            'alias' => $this->data->getBonusBalanceAlias(),
            'currency' => $this->data->getCurrency(),
            'balance' => $this->data->getBonusBalance(),
        ];
    }
}
