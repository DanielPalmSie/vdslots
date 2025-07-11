<?php

declare(strict_types=1);

namespace Videoslots\Mts\Url;

use DBUser;
use DBUserHandler;
use Phive;

class UrlHandler
{
    private const DEPOSIT_ROUTE = '/cashier/deposit/?end=true&supplier=';
    private const SELECT_ACCOUNT_ROUTE = '/cashier/withdraw/?action=select_account&success=';

    private DBUser $user;
    private Phive $phive;
    private DBUserHandler $userHandler;
    private string $supplier;

    public function __construct(DBUser $user, string $supplier)
    {
        $this->user = $user;
        $this->phive = phive();
        $this->userHandler = phive('UserHandler');
        $this->supplier = $supplier;
    }

    public function getDepositSuccessReturnUrl(): string
    {
        $actionUrl = $this->getActionUrl();

        return $this->generateUrl($actionUrl);
    }

    public function getDepositFailReturnUrl(): string
    {
        $actionUrl = $this->getActionUrl() . '&action=fail';

        return $this->generateUrl($actionUrl);
    }

    private function getActionUrl(): string
    {
        $brandUrl = $this->getBrandBaseUrl();

        return $brandUrl . self::DEPOSIT_ROUTE . $this->supplier;
    }

    private function getBrandBaseUrl(): string
    {
        $brandUrl = $this->userHandler->getSiteUrl($this->user->data['country']);
        $language = $this->user->data['preferred_lang'];
        $isMobile = $this->phive->isMobile() ? '/mobile' : '';

        return $brandUrl . '/' . $language . $isMobile;
    }

    private function generateUrl(string $actionUrl): string
    {
        $brandUrl = $this->getBrandBaseUrl();
        $queryParams = $this->phive->isMobile() ? '' : '/?show_deposit=true&show_url=';

        return $this->phive->isMobile() ? $actionUrl : $brandUrl . $queryParams . urlencode($actionUrl);
    }

    public function getSelectAccountSuccessReturnUrl(): string
    {
        return $this->getBrandBaseUrl() . self::SELECT_ACCOUNT_ROUTE . 'true';
    }

    public function getSelectAccountFailReturnUrl(): string
    {
        return $this->getBrandBaseUrl() . self::SELECT_ACCOUNT_ROUTE . 'false';
    }
}
