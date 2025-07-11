<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.05.
 * Time: 11:46
 */

namespace App\Controllers\Messaging;

use App\Helpers\CampaignHelper;
use App\Models\BonusTypeTemplate;
use App\Models\MessagingCampaignTemplates;
use App\Models\VoucherTemplate;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Models\SMSTemplate;
use App\Models\NamedSearch;
use App\Repositories\ReplacerRepository;

class SmsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        /**
         * Recurring SMS
         */
        $factory->get('/sms/templates/list/', 'App\Controllers\Messaging\SmsController::listTemplates')
            ->bind('messaging.sms-templates')
            ->before(function () use ($app) {
                if (!p('messaging.sms')) {
                    $app->abort(403);
                }
            });

        $factory->match('/sms/templates-new/create/', 'App\Controllers\Messaging\SmsController::createSmsTemplate')
            ->bind('messaging.sms-templates.new')
            ->before(function () use ($app) {
                if (!p('messaging.sms.new')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->post('/sms/templates-new/show/', 'App\Controllers\Messaging\SmsController::getSMSTemplate')
            ->bind('messaging.sms-templates.show')
            ->before(function () use ($app) {
                if (!p('messaging.sms')) {
                    $app->abort(403);
                }
            });

        $factory->match('/sms/templates-edit/{smsTemplate}/', 'App\Controllers\Messaging\SmsController::editSmsTemplate')
            ->convert('smsTemplate', $app['smsTemplateProvider'])
            ->bind('messaging.sms-templates.edit')
            ->before(function () use ($app) {
                if (!p('messaging.sms.edit')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/sms/templates-clone/{smsTemplate}/', 'App\Controllers\Messaging\SmsController::cloneSmsTemplate')
            ->convert('smsTemplate', $app['smsTemplateProvider'])
            ->bind('messaging.sms-templates.clone')
            ->before(function () use ($app) {
                if (!p('messaging.sms.new')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->get('/sms/templates-delete/{smsTemplate}/', 'App\Controllers\Messaging\SmsController::deleteSmsTemplate')
            ->convert('smsTemplate', $app['smsTemplateProvider'])
            ->bind('messaging.sms-templates.delete')
            ->before(function () use ($app) {
                if (!p('messaging.sms.delete')) {
                    $app->abort(403);
                }
            });

        /**
         * Schedule SMS templates
         */
        $factory->match('/sms/schedule/', 'App\Controllers\Messaging\SmsController::schedule')
            ->bind('messaging.sms-campaigns.new')
            ->before(function () use ($app) {
                if (!p('messaging.sms.campaign.new')) {
                    $app->abort(403);
                }
            })->method("GET|POST");

        return $factory;
    }

    public function listTemplates(Application $app, Request $request)
    {
        $c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_SMS);
        $data = SMSTemplate::all();
        return $app['blade']->view()->make('admin.messaging.sms.template-list', compact('app', 'data', 'c_type'))->render();
    }

    public function getSMSTemplate(Application $app, Request $request)
    {
        $template_obj = SMSTemplate::find($request->get('template'));
        $c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_SMS);

        if ($template_obj) {
            return $app->json([
                'html' => $app['blade']->view()->make('admin.messaging.sms.partials.template-show', compact('app', 'c_type', 'template_obj'))->render(),
                'contacts_list' => NamedSearch::where('language', $template_obj->language)->get()
            ]);
        } else {
            return $app->json(['html' => '<p>Template not found.</p>']);
        }
    }

    public function createSmsTemplate(Application $app, Request $request)
    {
        $repo = new ReplacerRepository();
        $replacers = $repo->getAllowedReplacers();
        $c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_SMS);

        if ($request->isMethod('POST')) {
            $template = new SMSTemplate();
            $template->fill($request->request->all());
            if ($template->save()) {
                $app['flash']->add('success', "Recurring SMS template created successfully.");
                return new RedirectResponse($app['url_generator']->generate('messaging.sms-templates'));
            } else {
                $app['flash']->add('danger', "There was an error while creating this SMS template.");
            }
        }

        return $app['blade']->view()->make('admin.messaging.sms.template-form', compact('app', 'replacers', 'c_type'))->render();
    }

    public function editSmsTemplate(Application $app, Request $request, SMSTemplate $smsTemplate)
    {
        $repo = new ReplacerRepository();
        $replacers = $repo->getAllowedReplacers();
        $c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_SMS);

        if ($request->isMethod('POST')) {
            if ($smsTemplate->fill($request->request->all())->save()) {
                $app['flash']->add('success', "Recurring SMS template edited successfully.");
            } else {
                $app['flash']->add('danger', "There was an error while editing this SMS template.");
            }
            return new RedirectResponse($app['url_generator']->generate('messaging.sms-templates'));
        }
        return $app['blade']->view()->make('admin.messaging.sms.template-form', compact('app', 'replacers', 'smsTemplate', 'c_type'))->render();
    }

    public function cloneSmsTemplate(Application $app, Request $request, SMSTemplate $smsTemplate)
    {
        if ($request->isMethod('POST'))
            return $this->createSmsTemplate($app, $request);

        return $this->editSmsTemplate($app, $request, $smsTemplate);
    }

    public function updateSmsTemplate(Application $app, Request $request, SMSTemplate $smsTemplate)
    {
        $smsTemplate->fill($request->request->all())->save();
        return new RedirectResponse($app['url_generator']->generate('messaging.sms-templates'));
    }

    public function deleteSmsTemplate(Application $app, SMSTemplate $smsTemplate)
    {
        $has_campaign = MessagingCampaignTemplates::where(['template_type' => MessagingCampaignTemplates::TYPE_SMS, 'template_id' => $smsTemplate->id])->count();
        if ($has_campaign > 0) {
            $app['flash']->add('danger', "Cannot be deleted because is linked to a recurring sms.");
        } else {
            $smsTemplate->delete();
            $app['flash']->add('success', "SMS template deleted successfully.");
        }
        return new RedirectResponse($app['url_generator']->generate('messaging.sms-templates'));
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function schedule(Application $app, Request $request)
    {
        $action = $request->get('action', 'new');
        $c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_SMS);
        if ($request->isMethod('POST')) {
            if (in_array($action, ['new', 'clone', 'edit'])) {
                if ($action == 'edit') {
                    $campaign_template = MessagingCampaignTemplates::find($request->get('campaign-template-id'));
                } else {
                    $campaign_template = new MessagingCampaignTemplates();
                    $campaign_template->template_type = MessagingCampaignTemplates::TYPE_SMS;
                }
                $request->request->set('recurring_days', implode(',', $request->request->get('recurring_days')));
                //to avoid mysql 1092 error / datetime not valid
                if (empty($request->get('recurring_end_date'))) {
                    $request->request->set('recurring_end_date', null);
                }
                $campaign_template->fill($request->request->all());
                if ($campaign_template->save()) {
                    $app['flash']->add('success', "Campaign scheduled successfully.");
                    $app['monolog']->addInfo("SMS campaign scheduled successfully", [
                        'id' => $campaign_template->id,
                        'action' => $action,
                        'name' => $campaign_template->smsTemplate()->first()->template_name,
                        'recurring_type' => $campaign_template->getRecurringTypeName(),
                        'start_datetime' => $campaign_template->generateScheduledTime(),
                        'first_future_scheduled' => $campaign_template->getFirstFutureScheduled()
                    ]);
                    return new RedirectResponse($app['url_generator']->generate('messaging.campaigns.list-recurring', ['type' => $c_type->getRawType()]));
                } else {
                    $app['flash']->add('danger', "Campaign not scheduled due to: {$campaign_template->getFirstError()[0]}");
                    $app['monolog']->addError("SMS campaign could not be scheduled", [
                            'error' => $campaign_template->getFirstError()[0],
                            'template_id' => $request->get('template_id'),
                            'named_search_id' => $request->get('named_search_id'),
                            'recurring_type' => $request->get('recurring_type'),
                            'scheduled_time' => $request->get('scheduled_time')
                        ]
                    );
                }
            } else {
                $app->abort('404', "Action not supported");
            }
        } elseif ($action == 'edit') {
            $campaign_template = MessagingCampaignTemplates::find($request->get('campaign-template-id'));
            $named_searches = NamedSearch::where('language', $campaign_template->smsTemplate()->first()->language)->get();
        } elseif ($action == 'clone') {
            $campaign_template = MessagingCampaignTemplates::find($request->get('campaign-template-id'))->replicate(['id']);
            $named_searches = NamedSearch::where('language', $campaign_template->smsTemplate()->first()->language)->get();
        }

        if ($campaign_template) {
            $campaign_template->recurring_days = empty($campaign_template->recurring_days) ? [] : explode(',', $campaign_template->recurring_days);
        }

        $voucher_templates = VoucherTemplate::all();
        $bonus_templates = BonusTypeTemplate::all();
        $templates_list = SMSTemplate::all();

        $type_id = MessagingCampaignTemplates::TYPE_SMS;

        if (!empty($request->get('smsTemplate'))) {
            $template_obj = SMSTemplate::find($request->get('smsTemplate'));
            $named_searches = NamedSearch::where('language', $template_obj->language)->get();
        }

        return $app['blade']->view()->make('admin.messaging.sms.schedule', compact('app', 'campaign_template', 'named_searches', 'bonus_templates', 'voucher_templates', 'templates_list', 'template_obj', 'c_type', 'type_id'))->render();
    }

}
