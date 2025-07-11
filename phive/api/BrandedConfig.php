<?php

class BrandedConfig
{
    public const BRAND_VIDEOSLOTS = 'videoslots';
    public const BRAND_MRVEGAS = 'mrvegas';
    public const BRAND_KUNGASLOTTET = 'kungaslottet';
    public const BRAND_MEGARICHES = 'megariches';
    public const BRAND_DBET = 'dbet';
    private const URL_ENV_KEY = 'as';

    protected string $suffix = '.config.php';

    // keep this protected to prevent leaking the values in exceptions
    protected array $configs;
    // keep this protected to prevent leaking the values in exceptions
    protected array $secrets = [];
    // environment variables
    protected array $env = [];

    protected string $config_dir;
    protected string $base_config_dir;
    protected string $brand = 'videoslots';
    protected string $environment = 'dev';
    protected string $secrets_file_location = 'config/secrets.php';

    protected bool $loaded = false;
    protected bool $new_configs_enabled = false;

    protected bool $new_container = false;
    protected array $configs_order = [];
    protected bool $configs_cache = false;

    protected string $config_local_file_name = 'local.php';

    protected string $phive_dir;
    private bool $url_env_changed = false;
    public array $tracer_log = [];


    /**
     * Load new configs or fallback to old configs
     *
     * @param string $phive_dir
     * @param bool $force_reload
     * @return void
     * @throws Exception
     */
    public function bootstrap(string $phive_dir, bool $force_reload = false): void
    {
        if ($this->loaded && !$force_reload) {
            return;
        }
        $this->phive_dir = realpath($phive_dir);
        $this->setupEnvVariables($phive_dir);

        $this->enableNewConfigs();

        $this->setConfigsOrder();

        $this->setupBaseConfigDir($phive_dir);

        if ($this->configs_cache && !$this->isCli()){
            //loading config data from cache
            $configsData = $this->getConfigDataFromCache($phive_dir);
        } else {
            //loading config data without cache
            $configsData = $this->setupConfigData($phive_dir);
        }

        $this->configs = $configsData;
        $this->loaded = true;

        if (!$this->isProduction()) {
            $this->loadTestTools();
            $this->setConfigsOrder();
        }
    }

    private function loadTestTools()
    {
        $this->setupTestEnvVariables();
        $this->setupEnvFromUrl();
        $this->loadLicenseDefaults();

        if (isset($_GET['print-config'])) {
            $this->printConfiguration();
        }
    }

    private function setupConfigData(string $phive_dir): array {
        if ($this->new_configs_enabled) {
            $configs = $this->loadNewConfigs($phive_dir);
        }

        // new configs disabled or loading new configs failed
        if (!isset($configs)) {
            // production doesn't have local.php
            $configs = $this->loadOldConfigs();
        }

        return $configs;
    }

    /**
     * Sets order in which configs will be applied. Also adds description to specify config file locations
     *
     * @return void
     */
    public function setConfigsOrder(): void {
        $data = [];
        $data['local'] =  $this->env['CONFIG_DIR'].'/'.$this->config_local_file_name;;
        $data['local_brand'] = $this->env['CONFIG_DIR']."/brand-{$this->getBrand()}/" . $this->config_local_file_name;
        $data['lic_env'] = (!$this->env['APP_LICENSE']) ? '[not set]' : 'lic-defaults/' . $this->env['APP_LICENSE'] . '.config.php';
        $data['domain'] = $this->env['CONFIG_DIR']."/domains/{$this->env['DOMAIN']}.config.php";
        $data['brand_env'] = $this->env['CONFIG_DIR']."/{$this->getBrand()}/{$this->env['APP_ENVIRONMENT']}/{module}.config.php";

        $this->configs_order = $data;
    }


    private function getConfigDataFromCache(string $phive_dir): array {
        //if SESSION caching is enabled
        if ($this->env['CONFIG_CACHE_TYPE'] == 'SESSION'){
            phive()->secureSessionStart();

            //get last used configuration file from session
            $cacheFileName = $_SESSION['CONFIGS_CACHE_TIME'].'-config.json';
            $currentCacheFile = $this->getCachingDirectoryPath($phive_dir)."/$cacheFileName";

            //if we have configs in session and config file is not outdated - load from session. Otherwise - reload configs from cache file.
            if ($_SESSION['CONFIGS'] && file_exists($currentCacheFile)){
                $configsData = $_SESSION['CONFIGS'];
            } else {
                $configsData = $this->getConfigDataFromCacheFile($phive_dir);
                $_SESSION['CONFIGS'] = $configsData;
                $_SESSION['CONFIGS_CACHE_TIME'] = $configsData['CACHE_TIME'];
            }
        } else {
            $configsData = $this->getConfigDataFromCacheFile($phive_dir);
        }

        return $configsData;
    }


    private function getCachingDirectoryPath(string $phive_dir): string {
        $configDirectory = $_ENV['CONFIG_DIR'];
        $cachingDirectory = $_ENV['CONFIG_CACHE_DIR'];

        $cachingDirectoryPath = "$phive_dir/$configDirectory/$cachingDirectory";

        $brand = $_SERVER['HTTP_X_CONSUMER_CUSTOM_ID'];
        if ($brand) {
            $cachingDirectoryPath = "$cachingDirectoryPath/$brand";
        }

        return $cachingDirectoryPath;
    }

    private function getConfigDataFromCacheFile(string $phive_dir): array {
        $configsData = [];

        $configDirectoryCacheFiles = scandir($this->getCachingDirectoryPath($phive_dir));
        $fileName = end($configDirectoryCacheFiles);

        //checking if available cache file has a correct file name and format
        if(preg_match("/^\d+-config.json$/", $fileName)){
            $cacheFilePath = $this->getCachingDirectoryPath($phive_dir)."/$fileName";
            if (file_exists($cacheFilePath)){
                $configsData = json_decode(file_get_contents($cacheFilePath), true);
            }
        }

        //if we don't have cache file or we can't read from it - then use standard config loading method
        if (!$configsData){
            $configsData = $this->setupConfigData($phive_dir);
        }

        return $configsData;
    }

    /**
     * Get full path to possibly branded config file
     *
     * @param string $file_name
     * @param string|null $brand
     * @return string
     */
    public function getConfigFile(string $file_name, ?string $brand = null): string
    {
        if (empty($brand) || !$this->new_configs_enabled) {
            return $this->base_config_dir . $file_name;
        }
        return $this->base_config_dir . "brand-{$brand}/" . $file_name;
    }

    /**
     * Returns the location of the modules.php file
    * @return string
     */
    public function getModulesFile(): string
    {
        return $this->base_config_dir . $this->getConfigFileName();
    }

    /**
     * Returns the reference to where phive path has been configured
     * @return string
     */
    public function getPhiveDir(): string
    {
        return $this->phive_dir;
    }

    /**
     * Get branded config value
     *
     * @param string $key FILE_NAME.level_1.level_n.config
     * @param $default
     * @return mixed
     */
    public function getConfigValue(string $key, $default)
    {
        $keys = explode('.', $key);
        $keys[0] = $this->normalizeFilenameKey($keys[0]);

        if (empty($this->configs)) {
            return $default;
        }

        if ($this->isNewConfigEnabled()) {
            return $this->getNewConfigValue($keys, $default);
        }

        $value = $this->configs;
        foreach ($keys as $array_key) {
            // if (isset($value[$array_key])) {
            // isset(['k'=>null]['k']) returns false
            if (array_key_exists($array_key, $value)) {
                $value = $value[$array_key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public function getNewConfigData():array {
        return $this->configs;
    }

    /**
     * @return array
     */
    public function getConfigsOrder():array {
        return $this->configs_order;
    }

    protected function getNewConfigValue(array $keys, $default)
    {
        $tracerData = [];

        // order of these keys is important
        // search for value in local, then in local_brand, then domain, then last brand_env
        $configsOrder = $this->getConfigsOrder();

        foreach ($configsOrder as $k => $v) {
            $data[] = $this->configs[$k];
        }

        foreach ($keys as $key) {
            foreach ($data as $index => $config) {
                if (!array_key_exists($key, $config)) {
                    // if key is missing in config set we remove the set
                    unset($data[$index]);
                    unset($tracerData['-td-'.$index]);
                } else {
                    // override the value of config set until we reach the override value
                    $data[$index] = $config[$key];
                    //adds config's data to a tracer using specific format of a key
                    $tracerData['-td-'.$index] = $config[$key];
                }
            }
        }

        // there is a value in at least one config set
        if (!empty($data)) {
            // the remaining sets all have a value for the override key,
            // but we want only the highest priority one
            // so use the value defined in the first set
            // example: [local=3, local_brand=2, domain_file=2, brand_env=1], then we want 3
            // example 2: [local=undefined, local_brand=2, domain_file=1, brand_env=0], then we want 2
            // example 3: [local=undefined, local_brand=undefined, domain_file=undefined, brand_env=0], then we want 0
            // array_values will reset the index, so we can grab the first item
            $default = array_values($data)[0];

            //we also are saving overridden values to a tracer log
            $this->tracer_log['overriden'][implode('.', $keys)] = $tracerData;

        } else {
            //defaults are also saved to a tracer log
            $this->tracer_log['defaults'][implode('.', $keys)] = $default;
        }

        return $default;
    }

    /**
     * Get value configured in secrets file
     *  Secrets will always be unidimensional assoc array
     *      to enable replacing the use of php file with a different strategy
     *
     * @param string $key
     * @return mixed
     */
    public function getSecretValue(string $key)
    {
        return $this->secrets[$key];
    }

    /**
     * Check if new config are enabled
     *
     * @return bool
     */
    public function isNewConfigEnabled(): bool
    {
        return $this->new_configs_enabled === true;
    }

    /**
     * Getter for brand
     *
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * Function will return brand names who's active bonuses will get forfeited after withdrawal
     * @return string[]
     */
    public function getWithdrawalForfeitBrands(): array
    {
        return [
            self::BRAND_VIDEOSLOTS,
            self::BRAND_MRVEGAS,
            self::BRAND_MEGARICHES,
        ];
    }

    /**
     * Enable new laravel container
     *
     * @return bool
     */
    public function isNewContainerEnabled(): bool
    {
        return $this->env['APP_LARAVEL_CONTAINER'] === 'true' ?? false;
    }

    /**
     * If the app uses the new container or not
     * @return bool
     */
    public function newContainer(): bool
    {
        return $this->new_container;
    }

    /**
     * @param $default
     * @return string
     */
    public function getConfigFileName(): string
    {
        return $this->new_container ? 'modules_new.php' : 'modules.php';
    }

    /**
     * Normalize filename key
     *  Convert FILE_NAME key to FILENAME to match the real filename
     *  Apply only to new configs because old ones are loaded differently
     *
     * @param string $filename_key
     * @param null|string $suffix
     * @return string
     */
    protected function normalizeFilenameKey(string $filename_key, ?string $suffix = null): string
    {
        if (!$this->new_configs_enabled || empty($filename_key)) {
            return $filename_key;
        }

        if (!empty($suffix)) {
            $filename_key = str_replace($suffix, '', $filename_key);
            // replace . with _ is required only for Sql.slave.config.php
            $filename_key = str_replace('.', '_', $filename_key);
        }

        return strtoupper(str_replace('_', '', $filename_key));
    }

    /**
     *  Parse env file content to key => value array
     *
     *  @param string $file_path
     *  @return array
     */
    public function parseEnvFile(string $envPath) {
        $envFileContents = file_get_contents($envPath);
        $envArray = array();
        $lines = explode("\n", $envFileContents);

        foreach ($lines as $line) {
          if ($line === '' || substr($line, 0, 1) === '#') {
              continue;
          }

          $pos = strpos($line, '=');

          if ($pos !== false) {
              $key = substr($line, 0, $pos);
              $value = substr($line, $pos + 1);
              if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
                  $value = substr($value, 1, -1);
              }
              elseif (substr($value, 0, 1) === "'" && substr($value, -1) === "'") {
                  $value = substr($value, 1, -1);
              }
              $envArray[$key] = $value;
          }
        }

        return $envArray;
    }
    /**
     * Setup env variables
     *
     * @param string $phive_dir
     * @return void
     */
    protected function setupEnvVariables(string $phive_dir): void
    {
        try {
            // prevent exceptions between code pulled and composer install
            if (class_exists(\Dotenv\Dotenv::class)) {
                if (! method_exists(\Dotenv\Dotenv::class, 'createUnsafeImmutable')) {
                    $dotEnv = new \Dotenv\Dotenv($phive_dir);
                    $dotEnv->overload();
                } else {
                    $configDir = $this->parseEnvFile("{$phive_dir}/.env")['CONFIG_DIR'] ?? 'config-new';
                    $domain = $_SERVER['HTTP_X_CONSUMER_CUSTOM_ID'];
                    $domainEnvFileExists = file_exists("{$phive_dir}/{$configDir}/env-files/{$domain}.env");

                    if ($domain && !$domainEnvFileExists) {
                        $this->logError('Domain env file not found, fallback to phive/.env');
                    }

                    if ($domain && $domainEnvFileExists) {
                        $dotEnv = \Dotenv\Dotenv::createMutable("{$phive_dir}/{$configDir}/env-files", "{$domain}.env");
                    } else {
                        $dotEnv = \Dotenv\Dotenv::createMutable($phive_dir);
                    }
                    $dotEnv->load();
                }
            } else {
                throw new Exception('Dotenv not installed. Please run composer install.');
            }
        } catch (Exception $e) {
            $this->logError('Dotenv failed to load: ' . $e->getMessage());
        }
        if (empty($_ENV)) {
            $pid = getmypid();
            $this->logError("Process $pid is running with empty ENV.");
        }
        $this->env = $_ENV;
    }

    /**
     * Attempt to enable new configs
     *
     * @return void
     */
    protected function enableNewConfigs(): void
    {
        $this->new_configs_enabled = $this->env['ENABLE_NEW_CONFIGS'] === 'true';
        $this->configs_cache = $this->env['CONFIG_CACHING'] === 'true';

        // use array_key_exists to allow .env to set these to null
        if (array_key_exists('SECRETS_FILE', $this->env)) {
            $this->secrets_file_location = $this->env['SECRETS_FILE'];
        }

        // use array_key_exists to allow .env to set these to null
        if (array_key_exists('APP_BRAND', $this->env)) {
            $this->brand = $this->env['APP_BRAND'];
        }

        // use array_key_exists to allow .env to set these to null
        if (array_key_exists('APP_ENVIRONMENT', $this->env)) {
            $this->environment = $this->env['APP_ENVIRONMENT'];
        }
        // enable the new laravel container
        $this->new_container = $this->isNewContainerEnabled();

    }

    /**
     * Wrapper around error log to easily enable/disable
     *
     * @param $message
     * @return void
     */
    protected function logError($message): void
    {
        error_log('WARNING: ' . $message);
    }

    /**
     * Create the full path to config folder
     *  When .env is not configured fallback to 'config' directory
     *  Throw EXCEPTION when the fallback to 'config' directory fails
     *
     * @param string $phive_dir
     * @return void
     * @throws Exception
     */
    protected function setupBaseConfigDir(string $phive_dir): void
    {
        $base_config_dir = null;

        // new config directory provided
        if (!empty($this->env['CONFIG_DIR'])) {
            $base_config_dir = $phive_dir . '/' . $this->env['CONFIG_DIR'] . '/';

            if (!file_exists($base_config_dir)) {
                // new config dir is missing so use old configs
                $this->new_configs_enabled = false;
                $base_config_dir = null;
            }
        }

        // when new config dir is not provided, or it is missing, try to fall back to old config
        if (empty($base_config_dir)) {
            $base_config_dir = $phive_dir . '/config/';
            if (!file_exists($base_config_dir)) {
                throw new Exception("Missing config folder: {$base_config_dir}");
            }
        }

        $this->base_config_dir = $base_config_dir;
    }

    /**
     * Attempt to load new configs
     * If this fails we should load old configs
     *
     * @param string $phive_dir
     * @return array|null
     */
    protected function loadNewConfigs(string $phive_dir): ?array
    {
        $configsData = null;

        try {
            $this->loadSecrets($phive_dir);
            $configsData = $this->loadConfigsFromConfigFiles();
        } catch (Exception $e) {
            // phive logger is not yet setup
            $this->logError('New configs not loaded due to: ' . $e->getMessage());
        }

        return $configsData;
    }

    /**
     * Load secrets from configured location
     *
     * @param string $dir
     * @return void
     */
    protected function loadSecrets(string $dir): void
    {
        $secretsData = include $dir . '/' . $this->secrets_file_location;
        if (!is_array($secretsData)) {
            $secretsData = [];
        }
        $this->secrets = $secretsData;
    }

    /**
     * Load configs from configured folders and files
     *
     * @return array
     * @throws Exception
     */
    protected function loadConfigsFromConfigFiles(): array
    {
        $this->setupBrandedConfigDirPath();

        $result = [
            'brand_env' => [],
            'domain' => [],
            'lic_env' => [],
            'local_brand' => [],
            'local' => [],
        ];

        // load files from directory phive/<configured_dir>/<brand>/<env>/*.config.php
        foreach ($this->getListOfConfigFiles() as $file) {
            $file_name = $this->normalizeFilenameKey($file, $this->suffix);
            $result['brand_env'][$file_name] = $this->includeConfigFile($this->config_dir . $file);
        }

        // load config/domains/*.config.php
        // in cli SERVER_NAME will be empty so fallback to .env
        $domain_file = "domains/{$_SERVER['SERVER_NAME']}.config.php";
        if (!file_exists($this->getConfigFile($domain_file))) {
            $domain_file = "domains/{$this->env['DOMAIN']}.config.php";
        }
        $result['domain'] = $this->includeConfigFile($this->getConfigFile($domain_file));

        // used mainly during development, can still be useful during CI/CD checks
        if (empty($this->env['SKIP_LOCAL_FILES'])) {
            // load config/brand-*/local.php
            $result['local_brand'] = $this->includeConfigFile($this->getConfigFile($this->config_local_file_name, $this->getBrand()));

            // load config/local.php
            $result['local'] = $this->includeConfigFile($this->getConfigFile($this->config_local_file_name));
        }

        return $this->fixNamesAndExplodeInlineKeys($result);
    }

    /**
     * Fix file names and explode dot separated inline keys
     *
     * @param array $result
     * @return array
     */
    protected function fixNamesAndExplodeInlineKeys(array $result): array
    {
        foreach ($result as $key => $files) {
            foreach ($files as $file => $config) {
                $key_items = explode('.', $file);
                $key_items[0] = $this->normalizeFilenameKey($key_items[0]);
                unset($result[$key][$file]);

                if (count($key_items) > 1) {
                    $result[$key] = $this->setValue($result[$key], $key_items, $config);
                    continue;
                }

                $result[$key][$key_items[0]] = $config;
            }
        }

        return $result;
    }

    /**
     * Safely include config file and fallback to empty array
     *
     * @param $file
     * @return array
     */
    protected function includeConfigFile($file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $data = include $file;
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Setup path to branded configs
     *
     * @return void
     * @throws Exception
     */
    protected function setupBrandedConfigDirPath(): void
    {
        $config_dir = $this->base_config_dir;
        if (empty($this->brand)) {
            throw new Exception('Empty brand value provided.');
        }

        if (empty($this->environment)) {
            throw new Exception('Empty env value provided.');
        }

        $config_dir .= 'brand-' . $this->brand . '/';
        if (!file_exists($config_dir)) {
            throw new Exception('Missing brand directory.');
        }

        $config_dir .= $this->environment . '/';
        if (!file_exists($config_dir)) {
            throw new Exception('Missing env directory.');
        }

        $this->config_dir = $config_dir;
    }

    /**
     * Get filtered list of config files
     *
     * @return array
     */
    protected function getListOfConfigFiles(): array
    {
        $files = scandir($this->base_config_dir);
        if (empty($files)) {
            return [];
        }

        return array_values(array_filter($files, function ($file) {
            return strpos($file, $this->suffix) !== false;
        }));
    }

    /**
     * Set $value on $config at the correct level based on provided $keys
     *
     * @param $config
     * @param $keys
     * @param $value
     * @return mixed
     */
    protected function setValue($config, $keys, $value)
    {
        $reference = &$config;

        foreach ($keys as $key) {
            $reference = &$reference[$key];
        }

        $reference = $value;

        unset($reference);

        return $config;
    }

    /**
     * Attempt to load old configs
     * On production no overwrites are required because local.php is missing
     *
     * @return array
     */
    protected function loadOldConfigs(): array
    {
        $config_file = $this->getConfigFile($this->config_local_file_name);

        $values = include $config_file;
        if (!is_array($values)) {
            $values = [$values];
        }

        return empty($values) ? [] : $values;
    }

    /**
     * Loads default country and localizer settings for the current environment from lic-defaults folder
     *
     * @return void
     */
    private function loadLicenseDefaults()
    {
        // Local license files
        if (!empty($this->env['APP_LICENSE'])) {
            $lic_config_file = $this->getConfigFile('lic-defaults/' . $this->env['APP_LICENSE'] . '.config.php');
            if (file_exists($lic_config_file)) {
                // Instead of calling local 20 times, we set the full domain here
                $this->env['APP_FULL_DOMAIN'] = $this->getConfigValue("PHIVE.FULL_DOMAIN_WITHOUT_SCHEMA", 'local.videoslots.com');
                $this->configs['lic_env'] = $this->includeConfigFile($lic_config_file);
                $this->configs = $this->fixNamesAndExplodeInlineKeys($this->configs);
            }
        }
    }

    /**
     * Setup test env variables
     * This is used by PHPUnit tests (/vendor/bin/pest --group=lic-CAON) See Pest.php
     *
     * @return void
     */
    private function setupTestEnvVariables()
    {
        if ($test_env = getenv('TEST_APP_LICENSE')) {
            $this->env['APP_LICENSE'] = $test_env;
        }
    }

    /**
     * Switch env based on URL parameter and reload the page
     *     Switches to ITALY  =>  ?as=it
     *     Resets to default  =>  ?as=reset
     *     Switches to CA-ON but don't reload  =>  ?as=caon&reload=0
     *
     * Subsequent calls without ?lic-env= will use the last set env
     * This overrides the default set on phive/.env config if any
     *
     * Will reload the page after the switch unless ?reload=0 is set
     *
     * #NOTE: This will not work with config caching enabled, but this should only be enabled on production
     *
     * @return void
     */
    private function setupEnvFromUrl()
    {
        if (!$this->newEnvFromURL() && isset($_COOKIE['app-lic-env']) && $_COOKIE['app-lic-env'] !== 'expired') {
            $this->env['APP_LICENSE'] = $_COOKIE['app-lic-env'];
        }

        $this->reloadPageIfEnvChanged();
    }

    private function newEnvFromURL(): bool
    {
        if (!empty($_GET[self::URL_ENV_KEY]) && !$this->resetEnvFromURL()) {
            $this->env['APP_LICENSE'] = $this->envFromUrl();
            $this->url_env_changed = true;
            return setcookie('app-lic-env', $this->envFromUrl(), time() + 3600, '/');
        }
        return false;
    }


    private function resetEnvFromUrl(): bool
    {
        if ($_GET[self::URL_ENV_KEY] === 'reset') {
            setcookie('app-lic-env', 'expired', time() - 3600, '/');
            $this->url_env_changed = true;
            return true;
        }
        return false;
    }

    private function reloadOnUrlEnvChange(): bool
    {
        return !isset($_GET['reload']) || $_GET['reload'];
    }

    private function envFromUrl(): string
    {
        return 'lic-' . strtoupper($_GET[self::URL_ENV_KEY]);
    }

    /**
     * Reload the page if the env was changed via URL
     *
     * @return void
     */
    private function reloadPageIfEnvChanged()
    {
        if ($this->url_env_changed && $this->reloadOnUrlEnvChange()) {
            header("HTTP/1.1 307 Temporary Redirect");
            header('Location: /');
            header("Connection: close");
            exit;
        }
    }

    /**
     * Usage:
     *    ?print-config=1    prints the full config
     *    ?print-config=sql.database   prints the value of SQL
     *
     * @return void
     */
    private function printConfiguration()
    {
        if($this->isProduction()) {
            return;
        }

        echo "<pre>";
        if ($_GET['print-config'] !== '1') {
            echo json_encode($this->getConfigValue(strtoupper($_GET['print-config']), 'not-found'), JSON_PRETTY_PRINT);
        } else {
            echo json_encode($this->configs,  JSON_PRETTY_PRINT);
        }
        echo "</pre>";
        exit;
    }


    private function isCli(): bool {
        if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if environment is configured as production
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->environment === 'prod';
    }

    public function getTracerLog():array {
        $data = [];

        if(!$this->isProduction()){
            $configOrder = $this->getConfigsOrder();
            $tracerLog = $this->tracer_log;

            $data = [
                    'order' => $configOrder,
                    'log' => $tracerLog,
            ];
        }

        return $data;
    }
}

if (!function_exists('getConfigFile')) {
    /**
     * Get full path to possibly branded config file
     *
     * TODO-REMOVE-COMMENT: This IS used on old prod configs
     *  when old configs are loaded, the path will fallback to folder /phive/config/
     *
     * @param $file_name
     * @param $brand
     * @return string
     */
    function getConfigFile($file_name, $brand = null): string
    {
        return phive('BrandedConfig')->getConfigFile($file_name, $brand);
    }
}

if (!function_exists('getSecretValue')) {
    /**
     * Get value configured in secrets file
     *  Secrets will always be unidimensional assoc array
     *      to enable replacing the use of php file with a different strategy
     *
     * TODO-REMOVE-COMMENT: This is NOT used on old prod configs
     *  no fallback to old config is necessary
     *
     * @param $key
     * @return mixed
     */
    function getSecretValue($key)
    {
        return phive('BrandedConfig')->getSecretValue($key);
    }
}

if (!function_exists('local')) {
    /**
     * Get branded config value
     *
     * TODO-REMOVE-COMMENT: This is NOT used on old prod configs
     *  no fallback to old config is necessary
     *
     * @param $key
     * @param $default
     * @return mixed
     */
    function local($key, $default)
    {
        return phive('BrandedConfig')->getConfigValue($key, $default);
    }
}

if (!function_exists('phivePath')) {
    /**
     * Get branded config value
     *
     *
     * @param $key
     * @param $default
     * @return mixed
     */
    function phivePath($path)
    {
        return phive('BrandedConfig')->getPhiveDir() . $path;
    }
}

if (!function_exists('tracer')) {
    function tracer()
    {
        return phive('BrandedConfig')->getTracerLog();
    }
}
