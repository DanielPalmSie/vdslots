<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddAllowedSmsCountriesConfig extends Seeder
{
    private const TABLE = 'config';
    private const CONFIG_TAG = 'sms';
    private const CONFIG_NAME = 'countries';
    private const CONFIG_TYPE = '{"type":"ISO2", "delimiter":" "}';
    private const COUNTRIES = 'AD AE AF AG AI AL AM AN AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BM BN BO BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG US UY UZ VA VC VE VG VN VU WF WS YE YT ZA ZM ZW';

    private $connection;


    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection->table(self::TABLE)
            ->insert([
                'config_tag' => self::CONFIG_TAG,
                'config_name' => self::CONFIG_NAME,
                'config_value' => self::COUNTRIES,
                'config_type' => self::CONFIG_TYPE,
            ]);
    }

    public function down()
    {
        $this->connection->table(self::TABLE)
            ->where('config_tag', self::CONFIG_TAG)
            ->where('config_name', self::CONFIG_NAME)
            ->where('config_type', self::CONFIG_TYPE)
            ->delete();
    }
}
