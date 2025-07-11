<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 24/01/18
 * Time: 14:10
 */

namespace App\Repositories;

use App\Classes\Filters;
use App\Models\Config;
use App\Models\Export;
use Silex\Application;

class ExportRepository
{
    /**
     * @param Application $app
     * @param string      $type
     * @param integer     $id
     * @param null        $schedule_time
     *
     * @return mixed
     */
    public static function getExportView($app, $type, $id, $schedule_time = null)
    {
        if ('contacts-list' === $type && !$app['messaging']['allow_contact_filters_download']) {
            return $app->abort(403);
        }

        // make sure the email config is set for notification
        try {
            Config::getValue(
                'export',
                'notifications',
                '',
                [
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Email><delimiter>"
                ], true);
        } catch (\Exception $e) {
            $app['monolog']->addError('checkCancellationsCount', [$e->getMessage()]);
        }
        $export = Export::query()->where('type', '=', $type)->where('target_id', '=', $id)->latest()->first();

        $permission = Export::EXPORT_MAP[$type]['permission'];
        $allow_multiple_exports = Export::EXPORT_MAP[$type]["allow_multiple_exports"];
        $export_text = Export::EXPORT_MAP[$type]["export_text"] ?? 'Export';
        $download_text = Export::EXPORT_MAP[$type]["download_text"] ?? 'Download';

        return $app['blade']->view()->make(
            'admin.export.index',
            compact('app', 'export', 'type', 'id', 'permission', 'allow_multiple_exports', 'schedule_time', 'export_text', 'download_text')
        );
    }
}
