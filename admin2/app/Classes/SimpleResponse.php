<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 17/10/16
 * Time: 10:19 AM
 */

namespace App\Classes;

use Silex\Application;

class SimpleResponse
{
    /** @var  bool $success */
    private $success;

    /** @var  string $message */
    private $message;

    /** @var  \Exception $exception */
    private $exception;

    /**
     * SimpleResponse constructor.
     * @param bool $status
     * @param string $message
     * @param null|\Exception $exception
     */
    public function __construct($status, $message, $exception = null)
    {
        $this->success = $status;
        $this->message = $message;
        $this->exception = $exception;
    }

    /**
     * @param string $message
     * @param null|\Exception $exception
     * @return SimpleResponse
     */
    public static function fail($message, $exception = null)
    {
        return new self(false, $message, $exception);
    }

    /**
     * @param string $message
     * @return SimpleResponse
     */
    public static function success($message)
    {
        return new self(true, $message);
    }

    /**
     * @param Application $app
     * @return string
     */
    public function asJson($app = null)
    {
        if (!empty($app) && !empty($this->exception)) {
            $app['monolog']->addError($this->exception->getMessage());
        }
        return json_encode(['success' => $this->success, 'message' => $this->message]);
    }

}