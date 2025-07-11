<?php

require_once __DIR__ . '/../../vendor/autoload.php';
use GeoIp2\Database\Reader;

require_once __DIR__ . '/../../api/ExtModule.php';
require_once __DIR__ . '/IpBlockException.php';

// TODO henrik extend from phmodule instead.

/*
 * This class wraps various logic for blocking people by IP, using the Maxmind IP database.
 * It can also get various info on an IP via the Udger database.
 *
 * @link https://www.maxmind.com/en/home The Maxmind homepage.
 * @link https://udger.com/ The Udger homepage.
 */
class IpBlock extends ExtModule{

    /**
     * @var string The database table to work with.
     */
    public $table = 'blocked_ips';

    /**
     * @var string The path to the Udger database.
     */
    public $udger_db;

    /**
     * @var string The path to the Maxmind country database.
     */
    public $country_db;

    /**
     * @var string The path to the Maxmind city database.
     */
    public $city_db;

    /**
     * @var Reader The Maxmind reader object as returned by their SDK logic.
     */
    public $reader;

    /**
     * @var \Udger\Parser The udger parser instance
     */
    public $parser;


    /**
     * @var bool Used for cache
     */
    private bool $cache = true;

    /**
     * The constructor where we assign values to the member variables.
     */
    public function __construct()
    {
        $this->table = 'blocked_ips';
        $this->udger_db = $this->getSetting('udger_db_location');
        $this->country_db = $this->getSetting('country_db_location');
        $this->city_db = $this->getSetting('city_db_location');

        try {
            $this->reader = new Reader($this->country_db);
        } catch (Exception $e) {
            phive('Logger')->error($e->getMessage() . '\nIpBlock: Missing country DB', [$e]);
        }

        parent::__construct();
    }

    // TODO henrik remove
    function table(){
        return $this->table;
    }

    /**
     * Simple wrapper to get a key for use with Redis.
     *
     * @return string The key.
     */
    function ipMemKey(){
        return remIp().'.limit';
    }

    /**
     * Checks if a user has been blocked because of too many requests to a certain resource in a
     * certain timespan. If yes it will terminat execution with an error message.
     *
     * @return null
     */
    function ipLimit(){
        if(isCli() || $this->getSetting('test') === true){
            return false;
        }

        $blocked = phMget(remIp().'.block');

        if(!empty($blocked)){
            http_response_code(429);
            die('Sorry, for security reasons your IP has been blocked');
        }
    }

    /**
     * Restricts repeat requests to certain resources, for example the double account check
     * in order to prevent discovery of which accounts exist already. We might for instance
     * increase a counter 3 times and after that block further access.
     *
     * @used-by DBUserHandler::checkExistsByAttr()
     *
     * @return null
     */
    function ipIncLimit(){
        if(!isCli()){
            $ip_limits = phive('Config')->getByTagValues('ip-limits');
            $key       = $this->ipMemKey();

            if(phMget($key) > $ip_limits['block-count']){
                // We block for an hour.
                phMset(remIp().'.block', 1, 3600);
            }

            $white_listed = explode(',', $ip_limits['whitelist']);
            if(!in_array(remIp(), $white_listed)){
                phMinc($key, 1);
            }
        }
    }

    // TODO henrik remove
    function check($to = "www.rakebacklovers.com"){
        $rem_ip = remIp();
        $ips = phive('SQL')->queryAnd("SELECT * FROM {$this->table} WHERE ipnum = '$rem_ip'")->fetch();
        if(!empty($ips)){
            header("Location: http://$to");
            exit;
        }
    }

    /**
     * Gets a country by way of IP from the Maxmind Reader object.
     *
     * Note that we just create a standard structure with empty data in case we don't get a proper result from the Maxmind database.
     *
     * @param string $ip The IP number to check.
     *
     * @return object The result object.
     */
    public function getGeoCountryRecord($ip = '')
    {
        $ip = $this->getIp($ip);
        try {
            $record = $this->reader->country($ip);
        } catch (Throwable $e) {
            $record = new stdClass();
            $record->country = new stdClass();
            $record->country->isoCode = '';
        }
        return $record;
    }

    /**
     * Get exact localization stuff like Timezone
     *
     * @param string $ip
     * @return stdClass
     */
    public function getGeoIpRecord($ip = ''){

        $ip = $this->getIp($ip);
        try{
            $record = (new Reader($this->city_db))->city($ip);
        }catch(Exception $e){
            $record = new stdClass();
            if (!isset($record->country))  {
                $record->country = new stdClass();
            }
            if (!isset($record->location))  {
                $record->location = new stdClass();
            }
            $record->country->isoCode = '';
            $record->location->latitude = 0;
            $record->location->longitude = 0;
            $record->location->timeZone = '';
        }
        return $record;
    }

    // TODO henrik remove
    function getLongLat($ip = '', $type = 'latitude'){
        return $this->getGeoIpRecord($ip)->location->$type;
    }

    /**
     * Gets a country from an IP number.
     *
     * In panic situations manual override can be used to avoid ISP reselling big IP ranges issues until our IP database gets updated.
     *
     * @link https://www.maxmind.com/en/home The Maxmind homepage.
     *
     * @param string $ip The IP.
     * @return string The ISO2 code of the country the IP belongs to.
     */
    function getCountry($ip = ''){
        if (!empty($this->getSetting('test_from_country'))) {
            return $this->getSetting('test_from_country');
        }
        $manual_override = $this->getSetting('manual_override');
        if (!empty($manual_override) && ip_in_range($ip, $manual_override['range'])) {
            return $manual_override['country'];
        }
        if (!empty($forced_country = $this->getDomainSetting('environment_country'))) {
            return $forced_country;
        }
        return $this->getGeoCountryRecord($ip)->country->isoCode;
    }

    /**
     * Returns jurisdiction code based on the given IP location.
     *
     * @param string $ip The IP.
     * @return string The jurisdiction string
     */
    public function getJurisdictionFromIp($ip = '')
    {
        $geo_ip = $this->getGeoIpRecord($ip);
        $country = $this->getCountry($ip);
        $province = !empty($geo_ip->mostSpecificSubdivision->isoCode) ?
            $geo_ip->mostSpecificSubdivision->isoCode :
            $this->getProvinceFromIp(null, $ip, 'province_redirect_to_own_top_domain');

        $license = phive('Licensed')->getLicense($country . '-' . $province);
        $is_province_license = false;
        if (!empty($license)) {
            $is_province_license = get_class($license) === $country . $province;
        }
        if ($is_province_license) {
            return $country . '-' . $province;
        }

        return empty($country) ? licJur() : $country;
    }

    /**
     * We get the local time zone for a particular IP.
     *
     * For testing purposes it is possible to enforce the time zone via settings.
     *
     * @param string|null $ip Not set will use current web context IP
     * @return mixed
     */
    public function getLocalTimeZone($ip = null)
    {
        if (!empty($this->getSetting('test_from_timezone'))) {
            return $this->getSetting('test_from_timezone');
        }
        return $this->getGeoIpRecord(trim($ip))->location->timeZone;
    }

    /**
     * A bunch of shell command to auto download the latest database from Maxmind.
     *
     * @param string $type To specify the type of report to download
     * @throws Exception
     */
    public function downloadGeoIpDatabase($type = 'country'): void
    {
        if ($type === 'country') {
            $edition = 'GeoIP2-Country';
            $fileName = 'GeoIP2-Country.mmdb';
            $dbName = $this->country_db;
        } else {
            $edition = 'GeoIP2-City';
            $fileName = 'GeoIP2-City.mmdb';
            $dbName = $this->city_db;
        }

        try {
            $downloadFolder = '/tmp/maxmind-folder-' . phive()->randCode(18);
            $downloadFile = "{$downloadFolder}/geoip.tar.gz";
            shell_exec("mkdir -p {$downloadFolder}");
            $url = "https://download.maxmind.com/geoip/databases/{$edition}/download?suffix=tar.gz";
            // Defining the token to use
            $token = $this->getSetting('maxmind_uid') . ":" . $this->getSetting('maxmind_key');
            // We download to the $downloadFolder to a temp file
            passthru("curl --retry 3 --compressed -sSfLko {$downloadFile} -u {$token} '{$url}'", $curlOutput);
            if (!file_exists("{$downloadFile}")) {
                throw new IpBlockException("Curl failed with error {$curlOutput} on: " . gethostname() . PHP_EOL);
            }
            // Validate file before unpacking
            $fileSize = filesize("{$downloadFile}");
            if (($edition === "GeoIP2Country" && $fileSize < 7680000) || ($edition === "GeoIP2City" && $fileSize < 11790000)) {
                throw new IpBlockException("Downloaded file {$downloadFile} seems too small ({$fileSize} bytes)");
            }
            // We unpack to the $downloadFolder dir
            passthru("tar -xzf {$downloadFile} -C {$downloadFolder}", $unpackStatus);
            if ($unpackStatus !== 0) {
                throw new IpBlockException("Failed to unpack the downloaded tar.gz file on: " . gethostname() . PHP_EOL);
            }
            // We get the actual file name
            $downloadName = trim(shell_exec('find ' . $downloadFolder . '/ -name "' . $fileName . '"'));
            if (empty($downloadName)) {
                throw new IpBlockException("Downloaded file not found in {$downloadFolder} on: " . gethostname() . PHP_EOL);
            }

            // Backup the existing database
            if (file_exists($dbName)) {
                $backupFile = "{$dbName}-backup";
                if (!copy($dbName, $backupFile)) {
                    throw new IpBlockException("Failed to backup the existing database file {$dbName} on: " . gethostname() . PHP_EOL);
                }
            } else {
                echo "No previous database file {$dbName} on: " . gethostname() . PHP_EOL;
            }

            // We copy to a folder where the http user can access the file
            if (!rename($downloadName, $dbName)) {
                throw new IpBlockException("Failed to move the downloaded file to: {$dbName} on: " . gethostname() . PHP_EOL);
            }

            $permissionChanged = chmod($dbName, 0777);
            if (!$permissionChanged) {
                throw new IpBlockException("Unable to change permission of this file: {$dbName}. Current permission is: {$permissionChanged} on: " . gethostname() . PHP_EOL);
            }

            $dbPermissions = substr(sprintf('%o', fileperms($dbName)), -4);
            if ($dbPermissions !== '0777') {
                throw new IpBlockException("Expected 0777 but got permissions {$dbPermissions} for file: {$dbName} on: " . gethostname() . PHP_EOL);
            }

            // instantiate reader to see if it can load the database
            new Reader($dbName);
        } catch (Exception $e) {
            if (!file_exists("{$downloadFile}") || $unpackStatus !== 0 || empty($downloadName)) {
                throw new IpBlockException($e->getMessage());
            }

            if (($edition === "GeoIP2Country" && $fileSize < 7680000) || ($edition === "GeoIP2City" && $fileSize < 11790000)) {
                throw new IpBlockException($e->getMessage());
            }

            if (!file_exists($backupFile)) {
                throw new IpBlockException($e->getMessage() . "Backup file not found. The file {$dbName} must be restored manually on: " . gethostname());
            }

            phive('Logger')->error($e->getMessage() . "\nMaxMind download: replacing {$dbName} with the backup file on: " . gethostname(), [$e]);

            // try to use the backup to prevent bringing down the website
            if (!rename($backupFile, $dbName)) {
                throw new IpBlockException("Failed to restore backup file from {$backupFile} to {$dbName} on: " . gethostname() . PHP_EOL);
            }

            $permissionChanged = chmod($dbName, 0777);
            if (!$permissionChanged) {
                throw new IpBlockException("Unable to change permission of this file: {$dbName}. Current permission is: {$permissionChanged} on: " . gethostname() . PHP_EOL);
            }

            // throw exception only if backup is unusable
            try {
                new Reader($dbName);
            } catch (Exception $backup_exception) {
                // use original $message to get more context into why the downloaded file was not good
                throw new IpBlockException($backup_exception->getMessage() . "\nFailed to replace corrupt {$dbName} with backup file: {$backupFile} on: " . gethostname());
            }
        } finally {
            // prevent executing `rm -rf` on the wrong folder if someone decides to change $downloadFolder
            if (strpos($downloadFolder, '/tmp/maxmind-folder') === 0) {
                shell_exec("rm -rf {$downloadFolder}");
            }
        }
    }

    /**
     * Downloads the Udger database, typically run as a cron job.
     *
     * TODO sync to all other machines that are loading the homepage after the download
     *
     * @return null
     */
    function downloadUdger(){
        $url = "http://data.udger.com/{$this->getSetting('udger_key')}/udgerdb_v3.dat";
        shell_exec("wget -O /tmp/udger.dat $url");
        shell_exec("mv /tmp/udger.dat {$this->udger_db}");
        chmod($this->udger_db, 0777);
    }

    /**
     * Boot strapping for Udger before we can make a request to the binary database.
     *
     * @return null
     */
    function setupUdger()
    {
        if(!empty($this->parser)) {
            return;
        }

        $this->factory = new Udger\ParserFactory($this->udger_db);
        $this->parser  = $this->factory->getParser();
        $this->parser->setDataFile($this->udger_db);
    }

    /**
     * Gets info for for a certain IP from the Udger database.
     *
     * Will typically return info as to whether or not the IP is a VPN IP or a bot etc. We use this
     * to assign fraud scores.
     *
     * @used-by Aml::onLogin()
     *
     * @link https://udger.com/ The Udger homepage.
     *
     * @param string $ip The IP.
     *
     * @return array The IP info.
     */
    function udgerInfo($ip = ''){
        $ip = $this->getIp($ip);
        $this->setupUdger();
        $this->parser->setIP($ip);
        return $this->parser->parse();
    }

    /**
     * Forces redirection on registration or login form to correct country website
     *
     * @param string|null $iso
     * @param string|null $province
     * @return false|string
     */
    public static function getIsoDomainRedirection(string $iso = null, string $province = null)
    {
        $server_name_override = array_search($iso,  phive('IpBlock')->getSetting('environment_country'));
        if (!empty($province) && !empty(phive('Localizer')->getSetting('province_domain')[$iso][$province])) {
            $server_name_override = phive('Localizer')->getSetting('province_domain')[$iso][$province];
        }
        $server_name = $_SERVER['SERVER_NAME'];

        if (!empty($server_name_override) && $server_name_override != $server_name) {
            return "https://$server_name_override";
        }
        return false;
    }

    public function getIp($ip = ''){
        return empty($ip) ? remIp() : $ip;
    }


    /**
     * @return array
     */
    public function getIp2ProxyCountry(): array{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.ip2proxy.com/?' . http_build_query([
                'ip'      => remIp(),
                'key'     => phive('Licensed')->getSetting('ip2proxy_key'),
                'package' => 'PX3',
            ]));

        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            return [];
        }

        return json_decode($result, true);
    }

    /**
     * @param $ip
     * @return array
     *
     * Scan the user IP and return location
     */
    public function getIp2LocationCity($ip): array
    {
        // check if $ip is in the whitelisted IPs configuration
        if (in_array($ip, phive('IpBlock')->getSetting('ip2_whitelist'))) {
            return [];
        }

        if ($this->cache){
            $cachedIpData = phMgetArr("ipdata-$ip");

            if (count($cachedIpData)){
                return $cachedIpData;
            }
        }

        try {
            $ws = new \IP2Location\WebService(phive('Licensed')->getSetting('ip2location_key'), 'WS3', false);
            $result = $ws->lookup($ip, 'en');
            if(empty($result)){
                return [];
            }
        } catch (Exception $e) {
            phive('Logger')->getLogger('ip2location')->critical($e->getMessage());
            return [];
        }


        if ($this->cache && $result['response'] == "OK"){
            $ipdata = [];
            $ipdata['country_code'] = $result['country_code'];
            $ipdata['country_name'] = $result['country_name'];
            $ipdata['region_name'] = $result['region_name'];
            $ipdata['city_name'] = $result['city_name'];

            phMsetArr("ipdata-$ip", $ipdata, phive('Licensed')->getSetting('ip_data_cache_time_sec'));
        }

        return $result;
    }

    /**
     *
     * If province check is enabled for this country we return the name of the province the IP is from
     *
     * Example return: Ontario
     *
     * @param mixed $country_or_user The user or ISO2 country code to check province for, if left out we assume not logged in context.
     * @param string $ip Optional IP to directly use, otherwise we get from SERVER.
     * @param string Optional setting to use, different contexts might require different on/off flags.
     * @return string Empty string if we couldn't get the province, province code otherwise.
     */
    public function getProvinceFromIp($country_or_user = null, $ip = null, $setting = null)
    {
        if(empty($country_or_user)){
            $country = getCountry();
        } else {
            $country = is_object($country_or_user) ? $country_or_user->getCountry() : $country_or_user;
        }

        $province_isos = $this->getSetting('province_isos')[$country] ?? null;

        if(empty($province_isos)){
            // Country is not configured for having province codes so we return empty string right away.
            return '';
        }

        $do_get = empty($setting) ? true : lic('getLicSetting', [$setting], null, null, $country);

        if($do_get !== true){
            // We're not supposed to check or get Province.
            return '';
        }

        $ip = empty($ip) ? remIp() : $ip;

        // NOTE: this call costs money!
        $location = $this->getIp2LocationCity($ip)['region_name'];

        phive('Logger')->getLogger('ip2location')->debug(__METHOD__, [
            'IP' => $ip,
            'COUNTRY' => $country,
            'LOCATION' => $location
        ]);

        return $province_isos[$location['region_name']] ?? '' ;
    }

    /**
     * @param string $country
     * @return void
     */
    public function setTestFromCountry(string $country)
    {
        $this->setSetting('test_from_country', $country);
    }
}

