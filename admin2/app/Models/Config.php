<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.24.
 * Time: 11:49
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use Valitron\Validator;

class Config extends FModel
{
    public $timestamps = false;
    protected $table = 'config';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $fillable = [
        'config_name',
        'config_tag',
        'config_value',
        'config_type',
    ];

    const TYPE_GROUP_LIST_WIRAYA = [
        "type" => "template",
        "delimiter" =>"::",
        "next_data_delimiter" =>"^_^",
        "format" =>"<:Language><delimiter><:ContactListId><delimiter><:WirayaProjectId>"
    ];

    const TYPE_COUNTRIES_LIST = [
        "type" => "iso2",
        "next_data_delimiter" => ","
    ];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['config_name'], ['config_tag'], ['config_type']],
                'lengthMin' => [['config_name', 1], ['config_tag', 1]],
            ]
        ];
    }

    /**
     * Todo only ',' delimiter support when as array is true, do the rest and the delimiters by default
     *
     * @param $name
     * @param $tag
     * @param $default
     * @param bool|array $set_default
     * @param bool $as_array
     * @param bool $use_all_delimiters Use both <b>next_data_delimiter</b> and <b>delimiter</b> if <b>$as_array</b> is <b>true</b>
     * @return array|mixed
     * @throws \Exception
     */
    public static function getValue($name, $tag, $default, $set_default = false, $as_array = false, bool $use_all_delimiters = false)
    {
        $self = new self();
        $config = $self->newQuery()->where(['config_name' => $name, 'config_tag' => $tag])->get();

        if ($config->isEmpty()) {
            $res = $default;
            if ($set_default !== false) {
                $type = is_array($set_default) ? $set_default : ['type' => 'number']; //todo Do this properly putting types in constants
                $self->fill([
                    'config_name' => $name,
                    'config_tag' => $tag,
                    'config_value' => $default,
                    'config_type' => json_encode($type)
                ])->save();
            }
        } elseif ($config->count() > 1) {
            throw new \Exception("Found more than one value for the same combination of name and tag");
        } else {
            /** @var Config $config_obj */
            $config_obj = $config->first();
            $res = $config_obj->config_value;
        }

        if ($as_array === true) {
            $delimiter1 = !empty($config_obj) ? $config_obj->getDelimiter() : ',';
            $values1 = array_map(function ($value) {
                return trim($value);
            }, explode($delimiter1, $res));
            $delimiter2 = !empty($config_obj) ? ($config_obj->getType()['delimiter'] ?? null) : null;
            if (!$use_all_delimiters || !$delimiter2) {
                return $values1;
            }

            $values2 = [];
            foreach ($values1 as $val1) {
                $arr = explode($delimiter2, $val1);
                if ($arr) {
                    $values2[$arr[0]] = $arr[1] ?? null;
                }
            }
            return $values2;
        } else {
            return $res;
        }
    }

    public function getType()
    {
        return !empty($this->config_type) ? json_decode($this->config_type, true) : [];
    }

    public function getDelimiter()
    {
        return !empty($this->getType()['next_data_delimiter']) ? $this->getType()['next_data_delimiter'] : ',';
    }

    public function validate()
    {
        if (!$this->is_validated) {
            return true;
        }

        $parent_validate_result = parent::validate();

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());

        $config_type_json = json_decode($this->config_type, true);
        if (!isset($config_type_json['type'])) {
            $validator->error('config_type', "Config type needs to describe the type of the value in JSON format.");
        }

        if ($config_type_json['type'] == 'number') {
            $value = trim($this->config_value);
            $type = "";

            if (empty($value)) {
                $this->config_value = 0;
            } else {
                if (preg_match('/^\-?\d+$/', $value, $matches)) {
                    $type = 'number';
                } else if (preg_match('/^\-?\d+\.(\d+)$/', $value, $matches)) {
                    $type = 'number';
                }

                if ($type != "number") {
                    $validator->error('config_value', "Provided value is not a number.");
                }
            }
        }

        if (!$validator->validate()) {
            $this->appendErrors($validator->errors());
            return false;
        }

        return $parent_validate_result;
    }
}
