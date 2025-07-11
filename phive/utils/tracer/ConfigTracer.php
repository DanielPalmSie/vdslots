<?php
require_once 'nicer/Nicer.php';

class ConfigTracer extends Nicer
{

    private array $configOrder = [];

    public function __construct()
    {
    }

    /**
     * @param array $configOrder
     */
    public function setConfigOrder(array $configOrder): void
    {
        $this->configOrder = $configOrder;
    }


    public function setData($value)
    {
        parent::__construct($value);
    }

    protected function _generate_keyvalue($key, $val)
    {
        $id = $this->_generate_dropid();
        $p = ''; // preview
        $d = ''; // description
        $t = gettype($val); // get data type
        $is_hash = ($t == 'array') || ($t == 'object');

        switch ($t) {
            case 'boolean':
                $p = $val ? 'TRUE' : 'FALSE';
                break;

            case 'integer':
            case 'double':
                $p = (string)$val;
                break;

            case 'string':
                $d .= ', '.sprintf($this->STR_STR_DESC, strlen($val));
                $p = $val;
                break;

            case 'resource':
                $d .= ', '.sprintf($this->STR_RES_DESC, get_resource_type($val));
                $p = (string)$val;
                break;

            case 'array':
                $d .= ', '.sprintf($this->STR_ARR_DESC, count($val));
                break;

            case 'object':
                $d .= ', '.get_class($val).', '.sprintf($this->STR_OBJ_DESC, count(get_object_vars($val)));
                break;
        }

        $cls = $this->css_class;
        $xcls = !$is_hash ? (' '.$cls.'_ad') : '';
        $html = '<a class="'.$cls.'_c '.$xcls.'" '.($is_hash ? 'href="javascript:;"' : '').' onclick="'.$this->js_func.'(\''.$this->html_id.'\',\''.$id.'\');">';
        $html .= '<span class="'.$cls.'_a" id="'.$this->html_id.'_a'.$id.'">&#9658;</span>';

        preg_match('/-td-(\d)/', $key, $tracerKey);

        if ($tracerKey) {
            $key = $tracerKey[1];
            $configKeys = array_keys($this->configOrder);
            $configName = $configKeys[$key];
            $configDesc = $this->configOrder[$configName];

            $html .= '	<span class="'.$cls.'">'.$configDesc.' </span>';
        } else {
            $html .= '	<span class="'.$cls.'_k">'.$this->_esc_html($key).'</span>';
        }

        //$html .= '	<span class="' . $cls . '_d">(<span>' . ucwords($t) . '</span>' . $d . ')</span>';
        $html .= '	<span class="'.$cls.'_p '.$cls.'_t_'.$t.'">'.$this->_esc_html($p).'</span>';
        $html .= '</a>';

        if ($is_hash) {
            $html .= $this->_generate_value($val, $cls.'_v', $this->html_id.'_v'.$id);
        }

        return $html;
    }

}