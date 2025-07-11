<?php

class ZipHandler
{
    /**
     * Creates a zip file with the request path and inserts or Replaces files within zip file.
     *
     * @param $zip_path
     * @param $file
     * @param $file_path_in_zip
     * @return bool
     */
    public static function insertFile($zip_path, $file, $file_path_in_zip)
    {
        $zip = new ZipArchive();
        $zip->open($zip_path, ZIPARCHIVE::CREATE);
        $is_file_inserted = $zip->addFile($file, $file_path_in_zip);
        $zip->close();

        return $is_file_inserted;
    }

    /**
     * Delete file from ZIP.
     *
     * @param $zip_path
     * @param $file_path_in_zip
     * @return bool
     */
    public static function deleteFile($zip_path, $file_path_in_zip)
    {
        $zip = new ZipArchive();
        $zip->open($zip_path, ZIPARCHIVE::CREATE);
        $is_file_inserted = $zip->deleteName($file_path_in_zip);
        $zip->close();

        return $is_file_inserted;
    }
}