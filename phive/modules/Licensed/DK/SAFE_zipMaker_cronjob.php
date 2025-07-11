<?php
/**
 * FIRST RELEASE: 2019-10-03 by Paolo/Jesus
 * Before doing any changes on this file remember that you need to update the version inside Phive repository
 */

/**
 * This script is standalone!
 * The purpose is to grab all temporary files generated files under a single tamper token session and put them into a zip file.
 *
 * REQUIRE PHP LIBS:
 * - apt-get install php7.0-cli php7.0-zip
 *
 * CRON CONFIG
 * - crontab -e
 * - add a line with "* * * * * php /var/www/SAFE_zipMaker_cronjob.php"
 *
 *
 * Example of the folder data structure that is expected inside the SAFE
 *  - /home/spillemyndigheden/ftps/folderstruktur-spilsystem/Zip
 *      - /2019-10-01 (folders in the past contains only a ZIP that contains the final data needed by DGA)
 *          - Videoslots-1654054.zip
 *      - /2019-10-02 (folders currently worked on, the ZIP are being constantly update by this script)
 *          - /Videoslots-1655260
 *              - /EndOfDay
 *                  - /2019-10-02
 *                      - Videoslots-1655260-1.xml
 *                      - ....
 *                      - Videoslots-1655260-E.xml
 *              - /KasinoSpil (It can span on multiple days)
 *                  - /2019-10-01
 *                      - Videoslots-1655260-1.xml
 *                  - /2019-10-02
 *                      - Videoslots-1655260-E.xml
 *              - KasinoSpil.zip
 *          - Videoslots-1655260.zip
 *      - ...
 */
$init_folder = "/home/spillemyndigheden/ftps/folderstruktur-spilsystem/Zip";

$iti = new RecursiveDirectoryIterator($init_folder);
$Iterator = new RecursiveIteratorIterator($iti);
$Regex = new RegexIterator($Iterator, '/^.+(-temp.xml)$/i', RecursiveRegexIterator::ALL_MATCHES);
$files = array();
foreach ($Regex as $file) {
    $files[filemtime($file[0][0])] = $file[0][0];
}
ksort($files);
foreach ($files as $file) {
    $base = basename($file);
    $version = explode("-", $base);
    $number = $version[1] == 1 ? "E" : $version[1] - 1;
    $folder_zip = array();
    $path = explode('/', $file);
    $data = count($path) - 4;
    for ($i = count($path); $i > $data; $i--) {
        $token = $path[count($path) - 1];
        array_unshift($folder_zip, $token);
        array_pop($path);
    }
    $day = ($path[count($path) - 1]);
    $path_folder = implode('/', $path);
    $zip_file = "$path_folder/$token.zip";
    $folder_zip[count($folder_zip) - 1] = $token . "-E.xml";
    $path_file = implode('/', $folder_zip);
    $zip = new ZipArchive();
    $zip->open($zip_file, ZIPARCHIVE::CREATE);
    $to_rename = $zip->locateName($path_file);
    if ($to_rename !== false && $zip->numFiles == $number) {
        $pos = strpos($zip->getNameIndex($to_rename), '-E.xml');
        $new_name = substr($zip->getNameIndex($to_rename), 0, $pos) . "-" . $number . ".xml";
        $old_name = $zip->getNameIndex($to_rename);
        $zip->renameName($zip->getNameIndex($to_rename), $new_name);
        rename("$init_folder/$day/$old_name", "$init_folder/$day/$new_name");
    }
    $zip->addFile($file, $path_file);
    $zip->close();
    rename("$file", "$init_folder/$day/$path_file");

}

