<?php

require_once 'Abstract/AbstractTable.php';

/**
 * //TODO Check the type of properties (integer or string)
 * Section 8.4
 * Final class RegionalCode
 */
final class RegionalCode extends AbstractTable
{

    public static $piemonte = "01";

    public static $valle_d_aosta = "02";

    public static $lombardia = "03";

    public static $veneto = "05";

    public static $friuli_venezia_giulia = "06";

    public static $liguria = "07";

    public static $emilia_romagna = "08";

    public static $tuscany = "09";

    public static $umbria = "10";

    public static $marche = "11";

    public static $lazio = "12";

    public static $abruzzo = "13";

    public static $molise = "14";

    public static $campania = "15";

    public static $puglia = "16";

    public static $basilicata = "17";

    public static $calabria = "18";

    public static $sicily = "19";

    public static $sardinia = "20";

    public static $autonomous_province_of_bolzano = "21";

    public static $autonomous_province_of_trento = "22";

    public static $abroad = "99";


}