<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 30/11/17
 * Time: 18:46
 */

namespace App\Controllers\Api;

use App\Extensions\Database\FManager as DB;
use App\Models\CrmSentMailsEvents;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Right now it only listens on "send","open","click".
 *
 * Class MandrillNotificationsController
 * @package App\Controllers\Api
 */
class MandrillNotificationsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->post('/notifications/mandrill/', 'App\Controllers\Api\MandrillNotificationsController::receive');

        return $factory;
    }

    public function receive(Application $app, Request $request)
    {
        $mandrill_events = json_decode($request->get("mandrill_events"));

        $events = collect($mandrill_events);

        $messaging_campaign_users = DB::table('messaging_campaign_users')
            ->whereIn('message_id', $events->pluck('_id'))
            ->select('id', 'message_id', 'smtp_events', 'resends', 'reject')
            ->get()
            ->groupBy('message_id');

        $actions = $events
            ->map(function ($event) use ($messaging_campaign_users) {
                $mcu = $messaging_campaign_users->get($event->_id);

                $event->mcu = $mcu ? (object)$mcu->first() : null;
                return $event;
            })
            ->filter(function ($event) {
                return $event->mcu !== null;
            })
            ->reduce(function ($carry, $event) {

                $insert = [
                    "message_id" => $event->_id,
                    "event" => $event->event,
                    "ts" => date('Y-m-d H:i:s', $event->ts)
                ];

                $update = [
                    "id" => $event->mcu->id
                ];

                if ($event->event === 'send') {
                    $update["reject"] = $event->msg->reject;
                    $update["status"] = $event->msg->state;

                } else {

                    $insert["location"] = json_encode($event->location ?? []);
                    $insert["user_agent_parsed"] = json_encode($event->user_agent_parsed ?? []);
                    $insert["user_agent"] = $event->user_agent;
                    $insert["ip"] = $event->ip;

                    $update["smtp_events"] = json_encode($event->msg->smtp_events);
                    $update["resends"] = json_encode($event->msg->resends);

                    if ($event->mcu->smtp_events === $update["smtp_events"] and $event->mcu->resends === $update["resends"]) {
                        $update = null;
                    }
                }

                if ($event->event === 'click') {
                    $insert["url"] = $event->url;
                }

                if ($update !== null) {
                    $carry['updates'][] = $update;
                }

                $carry['inserts'][$event->event][] = $insert;

                return $carry;
            }, [
                "inserts" => [
                    "send" => [],
                    "open" => [],
                    "click" => []
                ],
                "updates" => []
            ]);

        foreach ($actions['inserts'] as $key => $elements)
        {
            if (count($elements) > 0)
            {
                CrmSentMailsEvents::bulkInsert($elements);
            }
        }

        if (count($actions['updates']) > 0)
        {
            DB::updateBatch('messaging_campaign_users', $actions['updates'], 'id');
        }

        return $app->json();
    }
}
