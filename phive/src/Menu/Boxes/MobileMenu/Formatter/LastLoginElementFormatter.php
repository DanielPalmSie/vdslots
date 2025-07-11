<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

use Videoslots\Menu\Boxes\MobileMenu\Element\LastLoginBalanceData;

final class LastLoginElementFormatter implements MenuElementFormatterInterface
{
    /**
     * @var string
     */
    public const ELEMENT_TYPE = "last-login-balance";

    /**
     * @var \Videoslots\Menu\Boxes\MobileMenu\Element\LastLoginBalanceData
     */
    private LastLoginBalanceData $data;

    /**
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\LastLoginBalanceData $data
     */
    public function __construct(LastLoginBalanceData $data)
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
                <td style="padding: 10px 0;">
                    <span class="medium-bold header-3">
                        <?php et($this->data->getHeader()) ?>
                    </span>
                </td>
            </tr>
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
                            <?= $this->data->getCurrentBalance() ?>
                        </span>
                    </span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="medium-bold">
                        <?php et($this->data->getLastLoginBalanceAlias()) ?>
                    </span>
                </td>
                <td class="right">
                    <span class="medium-bold header-3">
                        <?= $this->data->getCurrency() ?>
                        <span id="mobile-left-menu-balance">
                            <?= $this->data->getLastLoginBalance() / 100 ?>
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
            'header' => $this->data->getHeader(),
            'current_balance_alias' => $this->data->getCurrentBalanceAlias(),
            'current_balance' => $this->data->getCurrentBalance(),
            'last_login_balance_alias' => $this->data->getLastLoginBalanceAlias(),
            'last_login_balance' => (string) ($this->data->getLastLoginBalance() / 100),
            'currency' => $this->data->getCurrency(),
        ];
    }
}
