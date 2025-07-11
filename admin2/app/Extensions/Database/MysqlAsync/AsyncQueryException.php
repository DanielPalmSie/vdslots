<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2/24/17
 * Time: 11:05 AM
 */

namespace App\Extensions\Database\MysqlAsync;

use Exception;

class AsyncQueryException extends Exception
{
    /**
     * The SQL for the query.
     *
     * @var string
     */
    protected $sql;

    /**
     * The mysqli link error message.
     *
     * @var string
     */
    protected $message;

    /**
     * Create a new query exception instance.
     *
     * @param  string $message
     * @param  string $sql
     * @param  Exception $previous
     */
    public function __construct($message, $sql, Exception $previous = null)
    {
        $this->sql = $sql;
        $this->message = $this->formatMessage($message, $sql, $previous);
    }

    /**
     * Format the SQL error message.
     *
     * @param  string $message
     * @param  string $sql
     * @param  Exception $previous
     * @return string
     */
    protected function formatMessage($message, $sql, $previous = null)
    {
        return (is_null($previous) ? '' : $previous->getMessage() . "\n") . sprintf("MysqlAsync error: %s", $message) . "\n\nRaw query:\n $sql";
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->message}\n";
    }

    /**
     * Get the SQL for the query.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the mysqli link error message.
     *
     * @return string
     */
    public function getMysqliMessage()
    {
        return $this->message;
    }
}