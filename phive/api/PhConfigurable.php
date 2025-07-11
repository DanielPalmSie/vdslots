<?php

// TODO henrik remove this
require_once __DIR__ . '/PhSetting.php';

/**
 * The base configurable functionality that all modules extend from.
 */
class PhConfigurable{

    // TODO henrik remove
    public $settings = array();
    
    /**        
     * @var The complete settings data as defined in the phive/config/ModuleName.config.php file.
     */
    public $settings_data = null;

    // TODO henrik remove
    public $functions = array();

    /**
     * Gets a setting.
     *
     * @param string $setting The key of the setting in the settings array.
     * @param mixed $default An optional default value in case the setting does not exist.
     *
     * @return string|mixed The setting / value.
     */
    function getSetting($setting, $default = null){
        if ($this->settings_data === null)
            $this->loadSettings();
        if(is_array($this->settings_data))
            return array_key_exists($setting, $this->settings_data) ? $this->settings_data[$setting] : $default;
        return $default;
    }

    /**
     * Returns the array intersection between an array and a config setting.
     *
     * @param string $key The dot notation key of the setting.
     * @param array $array The array to intersect with the setting.
     * @param bool $case_insensitive Flag to use case insensitive comparison.
     * @return array The array of intersecting values.
     * @example getSettingArrayIntersect('worldpay.deposit.excluded_countries', ['AU', 'BR']);
     */
    public function getSettingArrayIntersect(string $key, array $array, bool $case_insensitive = false): array
    {
        $keys = explode('.', $key);
        $setting = is_array($this->getSetting($keys[0]) ?? null) ? $this->getSetting($keys[0]) : [];
        for ($i = 1; $i < count($keys); $i++) {
            $setting = $setting[$keys[$i]] ?? [];
            if (!is_array($setting) || !$setting) {
                $setting = [];
                break;
            }
        }

        if (!$case_insensitive) {
            return array_intersect($array, $setting);
        }

        // array_intersect(array_map('strtolower' ...) would return lowercase matches.
        $intersect = [];
        foreach ($array as $a) {
            foreach ($setting as $s) {
                if (strcasecmp((string)$a, (string)$s) === 0) {
                    $intersect[] = $a;
                }
            }
        }
        return $intersect;
    }

    public function getDomainSetting($setting, $default = null)
    {
        $setting = $this->getSetting($setting, $default);
        $environment = $_SERVER['SERVER_NAME'];
        if (empty($environment)) {
            $environment = 'default';
        }

        if (!is_array($setting)) {
            return $setting;
        }

        if (!empty($setting[$environment])) {
            return $setting[$environment];
        }

        return $setting['default'] ?? $default;
    }

    /**
     * Checks if a setting is not empty.
     *
     * TODO henrik checking for empty is not correct w.r. to the method name, it should be a !== null check.
     *
     * @param string $setting The key of the setting in the settings array.
     *
     * @return bool True if not empty, false otherwise.
     */
    function settingExists($setting){
        $setting = $this->getSetting($setting);
        return !empty($setting);
    }

    /**
     * Returns all of a module's settings.
     *
     * @return array The settings.
     */
    function allSettings(){
        if ($this->settings_data === null)
            $this->loadSettings();
        return $this->settings_data;
    }

    /**
     * Sets a setting.
     *
     * @param string $setting The key of the setting in the settings array.
     * @param mixed $value The value.
     *
     * @return null
     */
    function setSetting($setting, $value){
        if ($value === null) {
            return;
        }
        if ($this->settings_data === null){
            $this->settings_data = [];
        }

        $this->settings_data[$setting] = $value;
    }

    // TODO henrik remove
    function settingIsSet($setting){
        if ($this->settings_data === null)
            $this->loadSettings();
        return (is_array($this->settings_data) && isset($this->settings_data[$setting]));
    }

    // TODO henrik remove
    function getSettingsList(){ return $this->settings; }


    // TODO henrik remove
    function configurable(){ return (is_array($this->settings) && !empty($this->settings)); }

    // TODO henrik remove
    function isConfigured(){
        foreach ($this->getSettingsList() as $setting){
            if (!$this->settingIsSet($setting->getName()))
	        return false;
        }
        return true;
    }

    /**
     * Loads the phive/config/ModuleName.config.php file.
     *
     * @return null
     */
    public function loadSettings(): void
    {
        $file = getConfigFile(get_class($this) . '.config.php');
        if (file_exists($file)) {
            include $file;
        }
    }

    public function filterSettingsByCountryAndProvince(array $settings, DBUser $user): array
    {
        $country = strtolower($user->getCountry());
        $province = strtolower($user->getMainProvince());

        return array_map(function ($value) use ($country, $province) {
            if (is_array($value)) {
                $key = isset($value[$country]) ? $country : "$country-$province";
                return $value[$key] ?? $value['default'];
            }
            return $value;
        }, $settings);
    }

    // TODO henrik remove
    function functions(){
        $arr = func_get_args();
        if (!empty($this->functions))
            $this->functions = array_merge($this->functions, $arr);
        else
            $this->functions = $arr;
    }

    // TODO henrik remove
    function hasFunctions(){
        return (is_array($this->functions) && !empty($this->functions));
    }

    // TODO henrik remove
    function getFunctions() { return $this->functions; }
}
