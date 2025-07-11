<?php

require_once 'Abstract/AbstractTable.php';

/**
 * Codes of the gambling acceptance terminal used by the player.
 * Section 8.9
 * Final class TerminalType
 */
final class TerminalType extends AbstractTable
{

    public static $automated_telephone_service = 4;

    public static $telephone_service_with_operator = 5;

    public static $television_channel_or_interactive_tv = 7;

    public static $desktop_via_browser_android_or_unix = 10;

    public static $desktop_via_browser_ios = 11;

    public static $desktop_via_browser_windows = 12;

    public static $desktop_via_browsers_other_service_operator = 14;

    public static $desktop_via_client_app_ios = 21;

    public static $desktop_via_client_app_windows = 22;

    public static $desktop_via_client_app_other_service_operator = 24;

    public static $mobile_site_android_or_unix = 30;

    public static $mobile_site_ios = 31;

    public static $mobile_site_windows = 32;

    public static $mobile_site_other_service_operator = 34;

    public static $client_app_smartphone_android_or_unix = 40;

    public static $client_app_smartphone_ios = 41;

    public static $client_app_smartphone_windows = 42;

    public static $client_app_smartphone_blackberry = 43;

    public static $client_app_smartphone_other_service_operator = 44;

    public static $client_app_tablet_android_or_unix = 50;

    public static $client_app_tablet_ios = 51;

    public static $client_app_tablet_windows = 52;

    public static $client_app_tablet_blackberry = 53;

    public static $client_app_tablet_other_service_operator = 54;


}