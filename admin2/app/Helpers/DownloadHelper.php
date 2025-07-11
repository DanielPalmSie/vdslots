<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 02/03/16
 * Time: 09:35
 */

namespace App\Helpers;

use Silex\Application;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadHelper
{
    private $app;

    public function __construct()
    {
        $this->app = new Application();
    }
    /**
     * Clean the output buffer, generate an in memory array and then push it as Streamed Response into the client.
     *
     * @param Application $app
     * @param array $records
     * @param string $file_name
     * @param string $delimiter
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function streamAsCsv(Application $app, $records, $file_name, $delimiter = ",")
    {
        ob_clean();

        $stream = function () use ($records, $delimiter) {
            $output = fopen('php://output', 'w');
            foreach ($records as $row) {
                fputcsv($output, $row, $delimiter);
            }
            fclose($output);
        };

        $encoded_file_name = urlencode($file_name);

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/csv',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => "attachment; filename=$encoded_file_name.csv",
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
        ]);

    }

    public static function generateDownloadPath($query_data, $extra = null)
    {
        if (is_array($extra)) {
            foreach ($extra as $key => $value) {
                $query_data[$key] = $value;
            }
        }
        $query_data['export'] = 1;
        return '?' . http_build_query($query_data);
    }

    public function fromStorage($folder, $file) {
        $path = BASE_DIR."/storage/{$folder}/{$file}";

        if (!file_exists($path)) {
            return $this->app->abort(403);
        }

        return $this->app
            ->sendFile($path, 200, [
                'Expires' => '0',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0'
            ])
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($path));
    }
}
