<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 24/01/18
 * Time: 13:51
 */

namespace App\Controllers;

use App\Models\Export;
use App\Models\IpLog;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class ExportController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/export/{type}/{id}/{schedule_time?}/', 'App\Controllers\ExportController::process')
            ->bind('export.process');

        $factory->get('/check/{type}/{id}/', 'App\Controllers\ExportController::checkProgress')
            ->bind('export.progress.check');

        $factory->get('/export/get/{type}/{target}/', 'App\Controllers\ExportController::get')
            ->bind('export.get');

        return $factory;
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @param             $type
     * @param             $id
     * @param             $schedule_time
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function process(Application $app, Request $request, $type, $id, $schedule_time = null)
    {
        if (!p(Export::EXPORT_MAP[$type]['permission'])) {
            $app['monolog']->addError('ExportController::process insufficient permissions');
            return new JsonResponse(['forbidden' => true]);
        }

        if (empty($schedule_time)) {
            $schedule_time = $request->get('schedule_time');
        }

        $actor_id = UserRepository::getCurrentId();
        $tag = "export-$type";
        $description = $schedule_time ? 'Scheduled export.' : 'Generated export.';

        ActionRepository::logAction($id, $description, $tag, false, $actor_id);
        IpLog::logIp($actor_id, $id, $tag, $description);

        $command = BASE_DIR . "/console export {$type} {$id} {$schedule_time}";
        $app['monolog']->addInfo("ExportController::process - Run process: $command");
        $command = explode(' ', $command, 5);
        $process = new Process($command);
        $process->setTimeout(180);
        $process->run();
        $app['monolog']->addInfo("ExportController::process finished,  exitCode:{$process->getExitCode()} exitCodeMessage:{$process->getExitCodeText()}");
        $export = Export::lastExport($id, $type, [Export::STATUS_SCHEDULED, Export::STATUS_PROGRESS, Export::STATUS_FINISHED]);

        return new JsonResponse(['status' => $export->status ?? Export::STATUS_FAILED]);
    }

    /**
     * @param Application $app
     * @param             $type
     * @param             $id
     *
     * @return bool|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkProgress(Application $app, $type, $id)
    {
        if (!p(Export::EXPORT_MAP[$type]['permission'])) {
            $app->abort(403);
            return false;
        }
        $export = Export::query()
            ->where('type', '=', $type)
            ->where('id', '=', $id)
            ->first();

        return $app->json([
            "export" => $id,
            "status" => $export->status,
            "type" => $export->type
        ]);
    }

    /**
     * @param Application $app
     * @param             $type
     * @param             $target
     *
     * @return bool|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function get(Application $app, $type, $target)
    {
        if (!p(Export::EXPORT_MAP[$type]['permission'])) {
            $app->abort(403);
            return false;
        }
        $export = Export::query()
            ->where('type', '=', $type)
            ->where('target_id', '=', $target)
            ->get()
            ->pop();

        return $app->json([$export]);
    }
}
