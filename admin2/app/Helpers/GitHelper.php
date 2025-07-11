<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 10/03/16
 * Time: 10:12
 */

namespace App\Helpers;

use DateTime;

class GitHelper
{
    protected $repository_path;

    public function __construct($repository_path = null)
    {
        if (is_null($repository_path)) {
            $this->repository_path = substr(getenv('STORAGE_PATH'), 0, -8);
        } else {
            $this->repository_path = realpath($repository_path);
        }
    }

    public static function getLastDate()
    {
        $mh = new GitHelper();
        $last_log = $mh->getLastUpdateLogEntry();

        return $last_log ? $last_log['date'] : null;
    }

    public static function getLastChangeset()
    {
        $mh = new GitHelper();
        $last_log = $mh->getLastUpdateLogEntry();
        return $last_log ? trim($last_log['commit'], 'commit') : null;
    }

    public function getLastUpdateLogEntry()
    {
        try {
            $log = $this->execute('git log -n 1');
        } catch (\Exception $e) {
            return false;
        }

        return $this->parseLog($log);
    }

    protected function execute($command)
    {
        $cwd = getcwd();
        chdir($this->repository_path);
        exec($command, $output, $return_code);
        chdir($cwd);
        if ($return_code !== 0) {
            error_log("Error with command {$command} launched in {$this->repository_path}");
        }
        return $output;
    }

    public function parseLog($log)
    {
        $author = $log[1];
        $date =  $log[2];
        return [
            'commit'  => trim($log[0]),
            'author'  => trim($author),
            'date'    => $date,
        ];
    }

    public function extractEmail($string)
    {
        preg_match_all('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $string, $matches);
        return empty($matches[0][0]) ? 'Unknown' : $matches[0][0];
    }

    public function extractAuthor($string)
    {
        preg_match_all('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $string, $matches);
        $string = str_replace($matches[0], '', $string);
        $string = str_replace(['<', '>', '()'], '', $string);
        $string = trim($string);
        return empty($string) ? 'Unknown' : $string;
    }


}
