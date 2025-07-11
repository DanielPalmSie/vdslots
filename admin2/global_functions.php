<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ArchiveFManager as ArchiveDB;
use App\Helpers\DataFormatHelper;
use Illuminate\Support\Carbon;
use App\Classes\DateRange;

/**
 * Loads a javascript file with a timestamp appended to it, to prevent
 * an older version of the file to get served from cache.
 *
 * @param string $filename
 */
function loadJsFile($filename)
{
    $filemtime     = filemtime(dirname(__FILE__) . '/' . str_replace('phive/admin', 'phive_admin', $filename));
    $new_filename  = $filename . '?' . $filemtime;
    echo '<script type="text/javascript" src="'.$new_filename.'"></script>';
}

/**
 * Loads a CSS file with a timestamp appended to it, to prevent
 * an older version of the file to get served from cache.
 *
 * @param string $filename
 */
function loadCssFile($filename)
{
    $filemtime     = filemtime(dirname(__FILE__) . '/' . str_replace('phive/admin', 'phive_admin', $filename));
    $new_filename  = $filename . '?' . $filemtime;
    echo '<link rel="stylesheet" type="text/css" href="'.$new_filename.'" />';
}

/**
 * Returns url to the user profile.
 *
 * @param Silex\Application $app
 * @param  string $username
 * @return string
 */
function accLinkAdmin(Silex\Application $app, $username = null)
{
    return \App\Helpers\URLHelper::generateUserProfileLink($app, $username);
}

function pluckSessionValue($key) {
    $val = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $val;
}

/**
 * Common global function to be used instead of "\App\Helpers\DataFormatHelper"
 * in blade templating.
 *
 * @return DataFormatHelper
 */
function dfh()
{
    // TODO check silex code to refactor this into a singleton, instead of instantiating 1 class everytime.
    return new DataFormatHelper();
}

if (!function_exists('loadPhive')) {
    /**
     * Loads phive from PHIVE_PATH only if not already loaded
     * @throws Exception
     */
    function loadPhive() {
        $GLOBALS['from-admin2'] = true;
        if (!function_exists('phive')) {
            if (file_exists(getenv('PHIVE_PATH'))) {
                require getenv('PHIVE_PATH');
            } else {
                throw new \Exception('Phive not loaded so ProcessMessages could not be added as Command.');
            }
        }
    }
}

if (!function_exists('replicaDatabaseSwitcher')) {
    /**
     * Returns replica database if it is configured or enabled. Otherwise, returns default database. Or you can return true or false according replica database is configured or not
     * @param boolean $returnDatabaseName
     * @returns boolean | string
     */
    function replicaDatabaseSwitcher(bool $returnDatabaseName = false) {
        $connectionsList = DB::getConnectionsList();
        if(array_key_exists('replica', $connectionsList)) {
            return $returnDatabaseName == false ? true : 'replica';
        }
        return $returnDatabaseName == false ? false : null;
    }
}

if (!function_exists('archiveDatabaseSwitcher')) {
    /**
     * Returns archive database if it is configured or enabled. Otherwise, returns default database. Or you can return true or false according archive database is configured or not
     * @param boolean $returnDatabaseName
     * @return bool|string|null
     */
    function archiveDatabaseSwitcher(bool $returnDatabaseName = false) {
        $connectionsList = ArchiveDB::getConnectionsList();
        if(array_key_exists('videoslots_archived', $connectionsList)) {
            return $returnDatabaseName == false ? true : 'videoslots_archived';
        }
        return $returnDatabaseName == false ? false : null;
    }
}

if (!function_exists('isArchived')) {
    /**
     * Checks if record exists in archive DB based on the date filter
     * @param string $type
     * @param DateRange|null $date_range
     * @param string|null $end_date
     * @return bool
     */
    function isArchived(string $type, DateRange $date_range = null, string $end_date = null): bool {
        $archive_start_date = DB::shsSelect('misc_cache', "SELECT cache_value FROM misc_cache ms WHERE ms.id_str = 'node-archive-end-date-$type';");

        if (empty($archive_start_date)) {
            return false;
        }

        if (!empty($date_range)) {
            return $date_range->getEnd()->lessThanOrEqualTo(Carbon::parse($archive_start_date[0]->cache_value));
        } elseif (!empty($end_date)) {
            return Carbon::parse($end_date)->lessThanOrEqualTo(Carbon::parse($archive_start_date[0]->cache_value));
        }

        return false;
    }
}
