<?php

namespace App\Controllers\Messaging;

use App\Helpers\CampaignHelper;
use App\Models\MailerQueueCrm;
use App\Models\MessagingCampaign;
use App\Models\MessagingCampaignTemplates;
use App\Models\SMSQueue;
use App\Repositories\MessagingRepository;
use Carbon\Carbon;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CampaignController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/campaigns/list/recurring/', 'App\Controllers\Messaging\CampaignController::listRecurring')
            ->bind('messaging.campaigns.list-recurring')
            ->before(function () use ($app) {
                if (!p('messaging.sms.campaign.list') || !p('messaging.email.campaign.list')) {
                    $app->abort(403);
                }
            });

        $factory->get('/campaigns/list/scheduled/', 'App\Controllers\Messaging\CampaignController::listScheduled')
            ->bind('messaging.campaigns.list-scheduled')
            ->before(function () use ($app) {
                if (!p('messaging.sms.campaign.list') || !p('messaging.email.campaign.list')) {
                    $app->abort(403);
                }
            });

        $factory->get('/campaigns/list/past/', 'App\Controllers\Messaging\CampaignController::listPast')
            ->bind('messaging.campaigns.list-past')
            ->before(function () use ($app) {
                if (!p('messaging.sms.campaign.list') || !p('messaging.email.campaign.list')) {
                    $app->abort(403);
                }
            });

        $factory->post('/campaigns/details/', 'App\Controllers\Messaging\CampaignController::getRecurringDetails')
            ->bind('messaging.campaigns.get-recurring-details')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/campaigns/stats/', 'App\Controllers\Messaging\CampaignController::getStats')
            ->bind('messaging.campaigns.stats')
            ->before(function () use ($app) {
                if (!p('messaging.sms.campaign.stats') || !p('messaging.email.campaign.stats')) {
                    $app->abort(403);
                }
            });

        $factory->get('/sms/schedule-delete/', 'App\Controllers\Messaging\CampaignController::deleteCampaign')
            ->bind('messaging.campaigns.delete')
            ->before(function () use ($app) {
                if (!p('messaging.sms.campaign.delete') || !p('messaging.email.campaign.delete')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function listRecurring(Application $app, Request $request)
    {
        $c_type = new CampaignHelper($request->get('type'));

        $recurring_list = MessagingCampaignTemplates::where('template_type', $c_type->getRawType())
            ->where('recurring_type', '!=', MessagingCampaignTemplates::RECURRING_ONCE)
            ->get();

        return $app['blade']->view()->make('admin.messaging.campaigns.templates-list', compact('app', 'recurring_list', 'c_type'))->render();
    }

    public function listScheduled(Application $app, Request $request)
    {
        $c_type = new CampaignHelper($request->get('type'));

        $templates = MessagingCampaignTemplates::query()
            ->where('status', '!=', MessagingCampaignTemplates::STATUS_ARCHIVED)
            ->where('recurring_end_date', '>=', Carbon::now()->toDateTimeString())
            ->orWhere('recurring_type', 'one')
            ->get();

        /** @var array $future_list this is a virtual list, not saving or querying in the database */
        $future_list = [];

        foreach ($templates as $template) {
            /** @var  MessagingCampaignTemplates $template */
            $virtual_campaign = new MessagingCampaign();
            $first_future = $template->getFirstFutureScheduled();
            if (Carbon::parse($first_future)->lt(Carbon::now())) {
                continue;
            } else {
                $virtual_campaign->scheduled_time = $first_future;
            }
            $virtual_campaign->virtual = true;
            $virtual_campaign->template = $template;
            $virtual_campaign->status = MessagingCampaign::STATUS_PLACED;

            $future_list[] = $virtual_campaign;
        }

        return $app['blade']->view()->make('admin.messaging.campaigns.schedules-list', compact('app', 'future_list', 'c_type'))->render();
    }

    public function getRecurringDetails(Application $app, Request $request)
    {
        $data = MessagingCampaignTemplates::find($request->request->get('id'));
        return $app->json([
            'html' => $app['blade']->view()->make('admin.messaging.campaigns.partials.recurring-detail', compact('app', 'data'))->render()
        ]);
    }

    public function listPast(Application $app, Request $request)
    {
        $c_type = new CampaignHelper($request->get('type'));
        $past_list = MessagingRepository::getPastCampaignsList($request->get('type'));

        return $app['blade']->view()->make('admin.messaging.campaigns.list', compact('app', 'past_list', 'c_type'))->render();
    }

    public function getStats(Application $app, Request $request)
    {
        /** @var MessagingCampaign $campaign */
        $campaign = MessagingCampaign::find($request->get('campaign-id'));
        $in_queue = 0;

        if ($campaign->type == 2)
        {
            $in_queue = MailerQueueCrm::where('messaging_campaign_id', $campaign->id)->count();
        }
        else if ($campaign->type == 1)
        {
            $in_queue = SMSQueue::where('messaging_campaign_id', $campaign->id)->count();
        }

        $progress = [
            'total_contacts' => $campaign->contacts_count,
            'total' => $campaign->sent_count,
            'total_percentage' => round(($campaign->sent_count * 100)/$campaign->contacts_count),
            'sent' => max($campaign->sent_count - $in_queue, 0),
            'sent_percentage' => round((($campaign->sent_count - $in_queue) / $campaign->sent_count) * 100),
            'queue' => $in_queue,
            'queue_percentage' => round(($in_queue / $campaign->sent_count) * 100)
        ];

        $c_type = new CampaignHelper($campaign->type);

        return $app['blade']->view()->make('admin.messaging.campaigns.stats', compact('app', 'campaign', 'campaign_template', 'pending_sms', 'progress', 'c_type'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteCampaign(Application $app, Request $request)
    {
        /** @var MessagingCampaignTemplates $campaign_template */
        $campaign_template = MessagingCampaignTemplates::where([
            'id' => $request->get('campaign-template-id')
        ])->first();

        if ($campaign_template) {
            $campaigns_count = $campaign_template->campaigns()->count();
            if ($campaigns_count == 0) {
                if ($campaign_template->delete()) {
                    $app['flash']->add('success', "Campaign deleted successfully.");
                } else {
                    $app['flash']->add('danger', "Campaign not delete due to an internal error.");
                }
            } else {
                $campaign_template->status = MessagingCampaignTemplates::STATUS_ARCHIVED;
                if ($campaign_template->save()) {
                    $app['flash']->add('success', "Campaign archived successfully.");
                } else {
                    $app['flash']->add('danger', "Campaign not archived due to an internal error.");
                }
            }
        } else {
            $app['flash']->add('danger', "Campaign not found.");
        }
        return new RedirectResponse($request->headers->get('referer'));
    }
}
