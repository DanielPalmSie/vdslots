<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 24/03/16
 * Time: 16:14
 */

namespace App\Helpers;

use App\Models\TransLog;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\HttpFoundation\Request;

class Common
{
    /**
     * Return true if the string is a JSON
     *
     * @param $string
     * @return bool
     */
    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function isLike($pattern, $subject)
    {
        return (bool)preg_match("/$pattern/", $subject);
    }

    public static function logSlowQueries($app)
    {
        $query_log = DB::connection()->getQueryLog();
        $res = (float)0;
        foreach ($query_log as $query) {
            $res += $query['time'];
        }
        $res = $res / 1000;
        if ($res > 1.0) {
            $app['monolog']->addError("Slow query time: $res sec. Url: ". $app['request_stack']->getCurrentRequest()->getRequestUri());
        }
    }

    public static function dumpTbl($tag, $var, $user_id = 0)
    {
        TransLog::create(['user_id' => $user_id, 'tag' => $tag, 'dump_txt' => var_export($var, true)]);
    }

    public static function saveAsCSV($filename, $data, $delete_if_exist = false)
    {
        $file_path = getenv('STORAGE_PATH') . "/reports/$filename.csv";

        if ($delete_if_exist === true && file_exists($file_path)) {
            unlink($file_path);
        }

        $fp = fopen($file_path, 'w');

        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
    }

    /**
     * Generates a base64-encoded signature for a Mandrill webhook request.
     * @param string $webhook_key the webhook's authentication key
     * @param string $url the webhook url
     * @param array $params the request's POST parameters
     * @return string
     */
    public static function generateSignature($webhook_key, $url, $params)
    {
        $signed_data = $url;
        ksort($params);
        foreach ($params as $key => $value) {
            $signed_data .= $key;
            $signed_data .= $value;
        }

        return base64_encode(hash_hmac('sha1', $signed_data, $webhook_key, true));
    }

    public static function doRoute(&$app, &$factory, $url, $controller, $method, $bind, $permission = '', $http_method = '', $directory = ''){
        if(empty($http_method)){
            $http_method = 'GET|POST';
        }

        if(empty($directory)){
            $directory = 'App\Controllers\\';
        } else {
            $directory .= '\\';
        }

        $factory->match($url, "$directory$controller::$method")->bind($bind);

        if(!empty($permission)){
            $factory->before(function () use ($app, $permission){
                if (!p($permission)) {
                    $app->abort(403);
                }
            });
        }

        $factory->method($http_method);
    }

    public static function doRoutes(&$app, &$factory, $config, $default_p = '', $controller = ''){
        foreach($config as $route){
            self::doRoute($app,
                          $factory,
                          $route['url'],
                          empty($controller) ? $route['c'] : $controller,
                          $route['m'],
                          $route['bind'],
                          empty($route['p']) ? $default_p : $route['p'],
                          $route['method'],
                          $route['dir']);
        }
    }

    public static function view(&$app, $view, $args = []){
        return $app['blade']->view()->make($view, $args)->render();
    }

    /**
     * Sends a notification on cron failure.
     *
     * @param string $cronName
     * @param string $message
     * @return void
     */
    public static function notifyCronFailure(string $cronName, string $message): void
    {
        loadPhive();
        $mh = phive('MailHandler');

        $subject = "Cron Failed: $cronName";
        $content = "<p>{$message}</p>";

        $to = $mh->getSetting("dev_support", 'devsupport@videoslots.com');
        $mh->saveRawMail($subject, $content, $mh->getNotificationAddress(), $to);
    }

}
