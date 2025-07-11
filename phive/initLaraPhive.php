<?php

use Illuminate\Container\Container;
use Laraphive\ServiceLocator;
use Laraphive\Phive\PhiveSetup;
use Laraphive\Phive\Setups\BoxHandler\CashierWithdrawBoxBaseDecoratorSetup;
use Laraphive\Phive\Setups\BoxHandler\CashierWithdrawBoxBaseSetup;
use Laraphive\Phive\Setups\BoxHandler\MobileFilterBoxBaseDecoratorSetup;
use Laraphive\Phive\Setups\BoxHandler\MobileFilterBoxBaseSetup;
use Laraphive\Phive\Setups\Config\ConfigDecoratorSetup;
use Laraphive\Phive\Setups\Config\ConfigSetup;
use Laraphive\Phive\Setups\Db\ConnectionManagerSetup;
use Laraphive\Phive\Setups\DBUserHandler\DBUserHandlerDecoratorSetup;
use Laraphive\Phive\Setups\DBUserHandler\DBUserHandlerSetup;
use Laraphive\Phive\Setups\DBUserHandler\SetupRegister;
use Laraphive\Phive\Setups\IpBlock\IpBlockDecoratorSetup;
use Laraphive\Phive\Setups\Licensed\LicensedDecoratorSetup;
use Laraphive\Phive\Setups\Licensed\LicensedSetup;
use Laraphive\Phive\Setups\Localizer\LocalizerDecoratorSetup;
use Laraphive\Phive\Setups\User\UserSetup;
use Laraphive\Phive\Setups\EventPublisher\EventPublisherDecoratorSetup;
use Laraphive\Phive\Setups\SitePublisher\SitePublisherDecoratorSetup;
use Laraphive\Phive\Setups\SitePublisher\SitePublisherSetup;
use Laraphive\Phive\Setups\EventPublisher\EventPublisherSetup;
use Laraphive\Phive\Setups\IpBlock\IpBlockSetup;
use Laraphive\Phive\Setups\Payment\PspConfigServiceSetup;


if (isset($GLOBALS['lara-api'])) {
    $container = app();
} else {
    $container = new Container();

    $setups = [
        new MobileFilterBoxBaseDecoratorSetup(),
        new MobileFilterBoxBaseSetup(),
        new CashierWithdrawBoxBaseDecoratorSetup(),
        new CashierWithdrawBoxBaseSetup(),
        new ConfigDecoratorSetup(),
        new ConfigSetup(),
        new ConnectionManagerSetup(),
        new DBUserHandlerDecoratorSetup(),
        new DBUserHandlerSetup(),
        new EventPublisherDecoratorSetup(),
        new EventPublisherSetup(),
        new IpBlockDecoratorSetup(),
        new IpBlockSetup(),
        new LicensedDecoratorSetup(),
        new LicensedSetup(),
        new LocalizerDecoratorSetup(),
        new SetupRegister(),
        new SitePublisherDecoratorSetup(),
        new SitePublisherSetup(),
        new UserSetup(),
        new PspConfigServiceSetup(),
    ];

    $phiveSetup = new PhiveSetup($container);

    $phiveSetup->setSetups($setups);

    $phiveSetup->runSetups();
}

ServiceLocator::getInstance($container);
