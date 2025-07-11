<?php
// TODO henrik extend from PhModule instead.
require_once __DIR__ . '/../../api/ExtModule.php';

/*
 * This class wraps basic database based configuration logic.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_config The wiki docs for the table behind this logic / class.
 */
class Config extends ExtModule{

    /**
     * @var string The database table this class works with.
     */
    private $table = 'config';

    function __construct(){
        $this->table = 'config';
        parent::__construct();
    }

    /**
     * Standard configuration is fetched from the database config under the 'feature-flag' tag
     * and the flag-name in "lower + hyphen" format. This behavior can be overridden by setting
     * an environment variable in "upper case + snake" format like: FLAG_NAME.
     *
     * If the environment variable is set, its value will take precedence over the database configuration.
     */
    public function featureFlag(string $featureFlag): bool
    {
        $envFormattedFeatureFlag = strtoupper(str_replace('-', '_', $featureFlag));
        if (getenv($envFormattedFeatureFlag) === 'true') {
            return true;
        }

        return 'on' === $this->getValue('feature-flag', $featureFlag);
    }

    /**
     * Getter for the table variable.
     *
     * @return string The table name.
     */
    function table(){
        return $this->table;
    }

    /**
     * Gets the base SELECT WHERE part of the SQL statement.
     *
     * @param
     *
     * @return xxx
     */
    function getSelect(){
        return "SELECT * FROM {$this->table()} WHERE ";
    }

    /**
     * Gets a whole config row from the DB by way of the id.
     *
     * @param int $id The id.
     *
     * @return
     */
    function getConfig($id){
        return phive("SQL")->lb()->loadAssoc($this->getSelect()." id = $id");
    }

    /**
     * Gets all config rows with the same tag.
     *
     * @param string $tag The tag.
     *
     * @return array The config rows.
     */
    function getByTag($tag){
        return phive('SQL')->lb()->loadArray($this->getSelect()."config_tag = '$tag'", 'ASSOC', 'config_name');
    }

    /**
     * Gets config by config name.
     *
     * @param string $config_name
     * @param string $tag
     *
     * @return array The config rows.
     */
    public function getByNameAndTag(string $config_name, string $tag): array
    {
        $config = phive('SQL')->lb()->loadAssoc(
            $this->getSelect()."config_name = '$config_name' AND config_tag = '$tag'"
        );

        return $config ?: [];
    }

    /**
     * Get all configs for the requested "config_tag"(s) grouped by the config_tag (to avoid name collision)
     * Ex.
     * ```php
     *  [
     *      'RG' => [
     *          'RG1' => [...],
     *          'RG2'=>[...]
     *      ],
     *      'AML' => [
     *          'AML1' => [...]
     *      ]
     * ]
     * ```
     *
     * @param string|array $tag The tag(s).
     * @param boolean $only_values By default it will return the full config, if true only the value is returned ['RG' => ['RG1' => XX ]]
     * @return array The config data.
     */
    public function getByTags($tag, $only_values = false)
    {
        $tags = phive('SQL')->makeIn($tag);
        $configs = phive('SQL')->loadArray($this->getSelect() . "config_tag IN ($tags)");
        $result = [];
        foreach($configs as $config) {
            if(!isset($result[$config['config_tag']])) {
                $result[$config['config_tag']] = [];
            }
            $result[$config['config_tag']][$config['config_name']] = $only_values ? $this->getValueFromTemplate($config) : $config;
        }

        return $result;
    }

    /**
     * Return the right value based on the template, it can be a single value (string|int) or an Array
     *
     * @param array $config The config row.
     * @return string|int|array The return value that was parsed from the template.
     */
    public function getValueFromTemplate($config) {
        $data_format = json_decode($config['config_type']);

        switch ($data_format->type) {
            case 'number':
            case 'text':
            case 'datetime': // YYYY-MM-DD H:i:s (expected format in the config)
            case 'choice': // on/off or yes/no
                if(isset($data_format->delimiter)) {
                    $value = explode($data_format->delimiter, $config['config_value']);
                } else {
                    if($data_format->type == 'number') {
                        $value = strpos($config['config_value'], '.') !== false ? (float)$config['config_value'] : (int)$config['config_value'];
                    } else {
                        $value = $config['config_value'];
                    }
                }
                break;
            case 'iso2':
            case 'ISO2': // work with both " " and ","
                $value = explode($data_format->delimiter, $config['config_value']);
                break;
            case 'template':
                if (isset($data_format->delimiter) && isset($data_format->next_data_delimiter)) {
                    $value = phive()->fromDualStr($config['config_value'], $data_format->next_data_delimiter, $data_format->delimiter);
                } elseif(isset($data_format->delimiter)) {
                    $value = explode($data_format->delimiter, $config['config_value']);
                } else {
                    $value = $config['config_value'];
                }
                break;
            case 'ISO2-template':
                if (isset($data_format->delimiter) && isset($data_format->next_data_delimiter)) {
                    $value = phive()->fromDualStr($config['config_value'], $data_format->next_data_delimiter, $data_format->delimiter);
                    $value = array_map(function($v){
                        return explode(" ", $v);
                    }, $value);
                } elseif(isset($data_format->delimiter)) {
                    $value = explode($data_format->delimiter, $config['config_value']);
                } else {
                    $value = $config['config_value'];
                }
                break;
            case 'json':
                $value = json_decode($config['config_value'], true);
                break;
            default:
                $value = $config['config_value'];
        }

        return $value;
    }

    /**
     * Returns an array of key => value where the key is the config name and the value the config value.
     *
     * @param string $tag The tag to fetch.
     *
     * @return array The result array.
     */
      function getByTagValues($tag){
          $rarr = array();
          foreach($this->getByTag($tag) as $name => $item)
              $rarr[$name] = $item['config_value'];
          return $rarr;
      }

    /**
     * Gets a value from the unique tag -> name combination.
     *
     * @param string $tag The tag.
     * @param string $name The name.
     * @param mixed $defaultValue The default value to insert and return in case the config row was not found.
     * @param string $defaultDelimiter The default delimiter to use if setting a new value. Default is ','.
     * @param array $defaultType The default type to use if setting a new value. Default is ['type' => 'number'].
     *
     * @return mixed The value from the config, or the default value after inserting if the config row was not found.
     */
    function getValue($tag, $name, $defaultValue = null, $defaultType = ['type' => 'number'], $defaultDelimiter = ','){
        $res = phive('SQL')->lb()
                           ->getValue("SELECT config_value FROM {$this->table()} WHERE config_tag = '$tag' AND config_name = '$name'");

        if ($res === false && !empty($defaultValue)) {
            $this->setValue($tag, $name, $defaultValue, $defaultDelimiter, $defaultType);
            return is_array($defaultValue) ? $defaultValue : trim($defaultValue);
        } else {
            return trim($res);
        }
    }

    /**
     * Sets / saves a config value in the config table.
     *
     * @param string $tag The tag.
     * @param string $name The name.
     * @param mixed $value The value.
     * @param string $delimiter The delimiter to implode on in case an array is passed in as the value.
     * @param array $type Type information that is used in the BO in order to validate config values,
     * ie a space separated list of ISO2 country codes can't suddenly contain an ISO3 code, then the
     * validation will refuse insertion.
     *
     * @return null
     */
    function setValue($tag, $name, $value, $delimiter = ',', $type = ['type' => 'number']){
        if (is_array($value)) { //todo support proper multi dim. array with sub delimiters
            $value = implode($delimiter, $value);
        }
        phive('SQL')->save($this->table, [
            'config_tag' => $tag,
            'config_name' => $name,
            'config_value' => $value,
            'config_type' => json_encode($type) //TODO support type based on delimiters
        ]);
    }

    /**
     * Returns the parsed version of the value, ie a space separated string of ISO2 codes becomes
     * an array with the codes in each position.
     *
     * @uses Phive::fromDualStr() In order to parse the value into an array if it is a dual
     * separated string.
     * @see Phive::fromDualStr()
     *
     * @param string $tag The tag.
     * @param string $name The name.
     * @param string $delim The main string to explode on.
     * @param string $sub_delim The sub string to explode on.
     * @param mixed $default The default value.
     *
     * @return array The result array.
     */
    function valAsArray($tag, $name, $delim = ' ', $sub_delim = '', $default = null){
        $val = $this->getValue($tag, $name, $default);
        if (!empty($sub_delim))
            return phive()->fromDualStr($val, $delim, $sub_delim);
        $tmp = explode($delim, $val);
        return array_combine($tmp, $tmp);
    }

    /**
     * Gets a random config row for one tag.
     *
     * @param string $tag The tag.
     * @param bool $value True if we only want to return the value, false if we want the whole row.
     *
     * @return array|string The array or string.
     */
    function getRand($tag, $value = false){
        $tags 	= $this->getByTag($tag);
        $num 	= rand(1, count($tags));
        return $value ? $tags[$num]['config_value'] : $tags[$num];
    }

    /**
     * Checks if country is or is not in config.
     *
     * @param string $tag The tag.
     * @param string $name The name.
     * @param string $country The country (ISO2).
     * @param string $delimiter The country delimiter.
     * @return bool True if yes, false if not.
     */
    public function isCountryIn($tag, $name, $country, $delimiter = ' ')
    {
        if (in_array($country, phive('Config')->valAsArray($tag, $name, $delimiter))) {
            return true;
        }
        return false;
    }
}
