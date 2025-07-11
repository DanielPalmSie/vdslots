<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 04/03/16
 * Time: 12:44
 */

namespace App\Classes;

use App\Helpers\Common;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Settings
 *
 * @package App\Classes
 * @property int $verified
 * @property string $email_code_verified
*/
class Settings
{
    public $login_allowed = [];

    public $forums_username = [];

    private $data_source;

    const DB_SOURCE = 1;
    const FORM_SOURCE = 2;

    /**
     * Settings constructor.
     * @param array|Collection $settings
     * @param int $source
     * @throws \Exception
     */
    public function __construct($settings, $source = self::DB_SOURCE)
    {
        $settings_list = [];
        if ($source == self::DB_SOURCE) {
            if (!is_array($settings)) {
                $settings_list = $settings->toArray();
            }
        } elseif ($source == self::FORM_SOURCE) {
            foreach ($settings as $key => $item) {
                    $settings_list[] = [
                        'setting' => $key,
                        'value' => $item
                    ];
            }
        } else {
            throw new \Exception("Not supported source for a Settings object.", 500);
        }

        $this->data_source = $source;
        foreach ($settings_list as $setting) {
            if (strlen($setting['setting']) > 13 && substr_compare($setting['setting'], 'login-allowed-', 0, 14) == 0) {
                $this->login_allowed[] = explode('-', $setting['setting'])[2];
            } else {
                $this->{$setting['setting']} = $setting['value'];
            }
        }
    }

    public function keyLike($like)
    {
        $res = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (Common::isLike($like, $key)) {
                $res[$key] = $value;
            }
        }

        return $res;
    }

}
