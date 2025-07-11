<?php

namespace App\Repositories;

use App\Extensions\Database\FManager as DB;
use App\Models\PageSetting;
use FilesystemIterator;
use LimitIterator;
use RegexIterator;
use Silex\Application;


class CmsRepository
{
    /** @var Application $app */
    protected $app;


    /**
     *
     * @param string $folder  image_uploads or file_uploads
     * @return string
     */
    public function getSubfolders($folder)
    {
        if(phive('DBUserHandler')->getSetting('send_public_files_to_dmapi')) {
            $result = phive('Dmapi')->getPublicSubfolders($folder);

            if(!empty($result['errors'])) {
                return $result['errors'];
            }

            $subfolders = $result['subfolders'];
        } else {

            $path = phive('Filer')->getSetting('UPLOAD_PATH');

            $result = scandir($path);
            $subfolders = [];
            foreach ($result as $value) {
                if(is_dir($path . '/' . $value) && $value != '.' && $value != '..') {
                     $subfolders[] = $value;
                }
            }
        }

        // add main folder too
        $subfolders[] = $folder;

        // remove folder user-files
        if(in_array('user-files', $subfolders)) {
            $key = array_search('user-files', $subfolders);
            unset($subfolders[$key]);
        }

        return $subfolders;
    }

    /**
     * Get a list of public files from a given folder and optional subfolder.
     *
     * @param string $folder
     * @param string $subfolder
     * @return array
     */
    public function getListOfPublicFiles($folder, $subfolder = '')
    {
        $files = [];
        if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            $result = phive('Dmapi')->getListOfPublicFiles($folder, $subfolder);

            $files = $result['files'];
        } else {
            $base_uri = phive('Filer')->getSetting('UPLOAD_PATH_URI');

            $folder = phive('Filer')->getSetting('UPLOAD_PATH');
            if(!empty($subfolder)) {
                $folder .= '/' . $subfolder;
                $base_uri .= '/' . $subfolder;
            }

            $result = scandir($folder);
            $files = $this->getFormattedFiles($result, $base_uri, $folder);
        }

        // todo: sort files in alphabetical order

        return $files;
    }

    /**
     * Get a list of paginated public files.
     *
     * @param string $subfolder
     * @param int $offset
     * @param int $rows_per_page
     * @param string $search
     * @return array
     */
    public function getPaginatedListOfPublicFiles($subfolder = '', $offset = 0, $rows_per_page = 10, $search = '')
    {
        $base_uri = phive('Filer')->getSetting('UPLOAD_PATH_URI');

        $folder = phive('Filer')->getSetting('UPLOAD_PATH');
        if(!empty($subfolder)) {
            $folder .= '/' . $subfolder;
            $base_uri .= '/' . $subfolder;
        }

        [$files, $total_files_count] = $this->getPaginatedFilesInFolder($folder, $offset, $rows_per_page, $search);
        $formatted_files = $this->getFormattedFiles($files, $base_uri, $folder);

        return [array_values($formatted_files), $total_files_count];
    }

    /**
     * Get a list of pages with backgrounds
     *
     * @return type
     */
    public function getListOfPagesWithBackgrounds()
    {
        $pages = DB::select("
            SELECT p.page_id, p.alias, p.cached_path, ps.value AS filename
            FROM pages p
            JOIN page_settings ps ON p.page_id = ps.page_id
            WHERE ps.name = 'landing_bkg'
            ORDER BY p.alias ASC
        ");

        $base_uri = phive('Filer')->getSetting('UPLOAD_PATH_URI');

        foreach ($pages as &$page) {
            $backgrounds_path = phive('Filer')->getSetting('UPLOAD_PATH') . '/backgrounds/' . $page->filename;
            if (file_exists($backgrounds_path)) {
                $page->background_url = $base_uri . '/backgrounds/' . $page->filename;
            } else {
                $page->background_url = $base_uri . '/' . $page->filename;
            }
        }

        return $pages;
    }

    /**
     * Change the background for a page
     *
     * @param int $page_id
     * @param string $filename
     * @return boolean
     */
    public function changePageBackground($page_id, $filename)
    {

        $base_uri = phive('Filer')->getSetting('UPLOAD_PATH_URI');
        $relative_path = str_replace($base_uri . '/', '', $filename);

        $page_setting = PageSetting::where('page_id', $page_id)
                ->where('name', 'landing_bkg')
                ->first();

        $page_setting->value = $relative_path;
        if($page_setting->save()) {
            return true;
        }

        return false;
    }


    public function getLandingPages($search_string)
    {
        $result = DB::select("
            SELECT p.page_id, p.alias, p.cached_path, b.box_id, b.box_class
            FROM pages p
            JOIN boxes b ON p.page_id = b.page_id
            WHERE b.box_class IN ('FullImageBox', 'JsBannerRotatorBox', 'DynamicImageBox')
            AND p.cached_path LIKE :search_string
            GROUP BY p.cached_path
            ORDER BY p.cached_path
        ", ['search_string' => '%'.$search_string.'%']);

        $landing_pages = [];
        foreach ($result as $key => $landing_page) {
            $landing_pages[] = ['id' => $landing_page->page_id, 'text' => $landing_page->cached_path];
        }

        return ['results' => $landing_pages, 'more' => false];
    }

    private function getPaginatedFilesInFolder(string $folder, int $offset, int $rows_per_page, string $search): array
    {
        $filesystem_iterator = new FilesystemIterator($folder . '/');
        $regex_iterator = new RegexIterator($filesystem_iterator, "/.*$search.*/i");
        $limit_iterator = new LimitIterator($regex_iterator, $offset, $rows_per_page);

        $files_info = iterator_to_array($limit_iterator, false);
        $total_files_count = iterator_count($regex_iterator);

        $files = array_map(function($info) {
            return $info->getFilename();
        }, $files_info);

        return [$files, $total_files_count];
    }

    private function getFormattedFiles(array $files, string $base_uri, string $folder): array
    {
        $formatted_files = [];

        foreach ($files as $file) {
            if (is_dir($folder . '/' . $file) || $file === '.' || $file === '..') {
                continue;
            }

            $formatted_file = [];
            $formatted_file['name'] = $file;
            $formatted_file['url'] = $base_uri . '/' . $file;
            $formatted_file['size'] = filesize($folder . '/'. $file);

            $formatted_files[] = $formatted_file;
        }

        return $formatted_files;
    }
}

