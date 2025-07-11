<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 11/13/16
 * Time: 6:55 PM
 */

namespace App\Controllers\Messaging;

use App\Helpers\PaginationHelper;
use App\Models\BonusType;
use App\Models\BonusTypeTemplate;
use App\Models\Game;
use App\Models\MessagingCampaignTemplates;
use App\Models\SMSQueue;
use App\Models\SMSTemplate;
use App\Models\TrophyAwards;
use App\Extensions\Database\FManager as DB;
use App\Models\User;
use App\Models\VoucherTemplate;
use App\Repositories\MessagingRepository;
use App\Repositories\ReplacerRepository;
use Carbon\Carbon;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PromotionsTemplateController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/promotions/bonus-templates/list/', 'App\Controllers\Messaging\PromotionsTemplateController::listBonusTemplates')
            ->bind('messaging.bonus.list')
            ->before(function () use ($app) {
                if (!p('messaging.promotions.bonus-templates.list')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/promotions/voucher-template/list/', 'App\Controllers\Messaging\PromotionsTemplateController::listVouchersTemplates')
            ->bind('messaging.vouchers.list')
            ->before(function () use ($app) {
                if (!p('messaging.promotions.voucher-templates.list')) {
                    $app->abort(403);
                }
            });

        $factory->match('/promotions/bonus-template/create/', 'App\Controllers\Messaging\PromotionsTemplateController::createBonusTemplate')
            ->bind('messaging.bonus.create-template')
            ->before(function () use ($app) {
                if (!p('messaging.promotions.bonus-templates.new')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/promotions/voucher-template/create/', 'App\Controllers\Messaging\PromotionsTemplateController::createVoucherTemplate')
            ->bind('messaging.vouchers.create-template')
            ->before(function () use ($app) {
                if (!p('messaging.promotions.voucher-templates.new')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/promotions/bonus-template/delete/', 'App\Controllers\Messaging\PromotionsTemplateController::deleteBonusTemplate')
            ->bind('messaging.bonus.delete-template')
            ->before(function () use ($app) {
                if (!p('messaging.promotions.bonus-templates.delete')) {
                    $app->abort(403);
                }
            });

        $factory->get('/promotions/voucher-template/delete/', 'App\Controllers\Messaging\PromotionsTemplateController::deleteVoucherTemplate')
            ->bind('messaging.vouchers.delete-template')
            ->before(function () use ($app) {
                if (!p('messaging.promotions.voucher-templates.delete')) {
                    $app->abort(403);
                }
            });

        $factory->post('/promotions/bonus-type/details/', 'App\Controllers\Messaging\PromotionsTemplateController::getBonusTypeDetails')
            ->bind('messaging.bonus.get-bonus-type-details')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            });
        
        $factory->post('/promotions/bonus-type-template/details/', 'App\Controllers\Messaging\PromotionsTemplateController::getBonusTypeTemplateDetails')
            ->bind('messaging.bonus.get-bonus-type-template-details')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/promotions/voucher-template/details/', 'App\Controllers\Messaging\PromotionsTemplateController::getVoucherTemplateDetails')
            ->bind('messaging.bonus.get-voucher-template-details')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/promotions/rewards/list/', 'App\Controllers\Messaging\PromotionsTemplateController::getRewardsList')
            ->bind('messaging.rewards.list')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/promotions/bonustype/list/', 'App\Controllers\Messaging\PromotionsTemplateController::getBonusTypeList')
            ->bind('messaging.bonustype.list')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/promotions/test/send/', 'App\Controllers\Messaging\PromotionsTemplateController::testCampaign')
            ->bind('messaging.campaign-test')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            })
            ->method('POST');

        return $factory;
    }

    public function testCampaign(Application $app, Request $request)
    {
        $repo = new MessagingRepository(true);

        try {
            if ($request->get('username')) {
                $users = explode(",", $request->get('username'));
                foreach ($users as $u) {
                    $user = User::findByUsername(trim($u));
                    if (!empty($u) && empty($user)) {
                        return $app->json(['msg' => "<div class='callout callout-warning'><p>Test username ({$u}) not found in the database.</p></div>", 'success' => true]);
                    }
                    $res = $repo->generateTestCampaign($app, $request, $user);
                    if (empty($res) || empty($res['msg'])) {
                        $app['monolog']->addError("[TEST-CAMPAIGN] ". json_encode($res));
                        return $app->json(['msg' => "<div class='callout callout-warning'><p>There was an error and the test campaign was not generated.</p></div>", 'success' => true]);
                    }
                }
            } elseif ($request->get('test_list')) {
                $res = $repo->generateTestCampaign($app, $request, null);
                if (empty($res) || empty($res['msg'])) {
                    $app['monolog']->addError("[TEST-CAMPAIGN] ". json_encode($res));
                    return $app->json(['msg' => "<div class='callout callout-warning'><p>There was an error and the test campaign was not generated.</p></div>", 'success' => true]);
                }
            } else {
                return $app->json(['msg' => "<p>There was an error and the test campaign was not generated. [CODE MS302]</p>", 'success' => false, 'exception' => $e->getMessage()]);
            }

            return $app->json(['msg' => "<div class='callout callout-success'><h4>{$res['title']}</h4><p>Result: <i>{$res['msg']}</i></p></div>", 'success' => strlen($res['msg']) > 0]);
        } catch (\Exception $e) {
            return $app->json(['msg' => "<p>There was an error and the test campaign was not generated. [CODE MS302]</p>", 'success' => false, 'exception' => $e->getMessage()]);
        }
    }

    public function listBonusTemplates(Application $app, Request $request)
    {
        $query = DB::table('bonus_type_templates AS btt')
            ->selectRaw('btt.*, ta.description AS reward_desc')
            ->leftJoin('trophy_awards AS ta', 'ta.id', '=', 'btt.award_id');

        if ($request->isMethod('POST')) {
            if (!empty($request->get('voucher-form'))) {
                $query->where('deposit_limit', 0);
            }
            $data = $query->get();
            return $app->json([
                'html' => $app['blade']->view()->make('admin.messaging.vouchers.bonus-template-list', compact('app', 'data'))->render()
            ]);
        } else {
            $data = $query->get();
            return $app['blade']->view()->make('admin.messaging.bonus.list', compact('app', 'data'))->render();
        }
    }

    public function listVouchersTemplates(Application $app, Request $request)
    {
        $data = VoucherTemplate::all();

        return $app['blade']->view()->make('admin.messaging.vouchers.list', compact('app', 'data'))->render();
    }

    public function getBonusTypeDetails(Application $app, Request $request)
    {
        $table = $request->request->get('table');
        if (in_array($table, ['bonus_types', 'bonus_type_templates'])) {
            $data = DB::table($table)->find($request->request->get('bonus'));
        } else {
            $app->abort(404, "Operation not supported");
        }

        return $app->json([
            'html' => $app['blade']->view()->make('admin.messaging.bonus.partials.bonustype-detail', compact('app', 'data'))->render()
        ]);
    }
    
    public function getBonusTypeTemplateDetails(Application $app, Request $request)
    {
        $data = BonusTypeTemplate::find($request->request->get('bonus'))->toArray();
        return $app->json([
            'html' => $app['blade']->view()->make('admin.messaging.bonus.partials.bonustype-detail', compact('app', 'data'))->render()
        ]);
    }

    public function getVoucherTemplateDetails(Application $app, Request $request)
    {
        $data = VoucherTemplate::find($request->request->get('id'))->toArray();
        return $app->json([
            'html' => $app['blade']->view()->make('admin.messaging.bonus.partials.voucher-detail', compact('app', 'data'))->render()
        ]);
    }

    public function getRewardsList(Application $app, Request $request)
    {
        $rewards = TrophyAwards::all();
        return $app->json([
            'html' => $app['blade']->view()->make('admin.messaging.bonus.awards-list', compact('app', 'rewards'))->render()
        ]);
    }

    public function getBonusTypeList(Application $app, Request $request)
    {
        $from = $request->get('from');
        $data = BonusType::all();
        return $app->json([
            'html' => $app['blade']->view()->make('admin.messaging.bonus.bonustype-list', compact('app', 'data', 'from'))->render()
        ]);
    }

    public function createBonusTemplate(Application $app, Request $request)
    {
        if ($request->isMethod('GET')) {
            $step   = $request->get('step');
            $action = $request->get('action');

            if ($step == 2 || $action == 'edit') {
                $bonus_tags = [];
                foreach (Game::select('network')->groupBy('network')->get() as $network) {
                    $bonus_tags[] = $network->network;
                }
            }

            if ($step == 1) {
                $data = BonusType::orderBy('id', 'asc')->get();
                return $app['blade']->view()->make('admin.messaging.bonus.create', compact('app', 'data'))->render();
            } elseif ($step == 2) {
                $bonus_type = BonusType::findOrFail($request->get('bonus-id'));

                $bonus_type->ext_ids = empty($bonus_type->ext_ids) ? [] : explode('|', $bonus_type->ext_ids);
                $bonus_type->game_id = empty($bonus_type->game_id) ? [] : explode('|', $bonus_type->game_id);
                $bonus_type->excluded_countries = empty($bonus_type->excluded_countries) ? [] : explode(' ', $bonus_type->excluded_countries);
                $bonus_type->included_countries = empty($bonus_type->included_countries) ? [] : explode(',', $bonus_type->included_countries);
                $bonus_type->included_countries = array_map(function($el){return strtoupper($el);}, $bonus_type->included_countries);

                $reward = $bonus_type->reward()->first();
                return $app['blade']->view()->make('admin.messaging.bonus.create', compact('app', 'bonus_type', 'bonus_tags', 'reward'))->render();
            } elseif ($action == 'edit') {
                $bonus_type = BonusTypeTemplate::findOrFail($request->get('template-id'));

                $bonus_type->ext_ids = empty($bonus_type->ext_ids) ? [] : explode('|', $bonus_type->ext_ids);
                $bonus_type->game_id = empty($bonus_type->game_id) ? [] : explode('|', $bonus_type->game_id);
                $bonus_type->excluded_countries = empty($bonus_type->excluded_countries) ? [] : explode(' ', $bonus_type->excluded_countries);
                $bonus_type->included_countries = empty($bonus_type->included_countries) ? [] : explode(',', $bonus_type->included_countries);

                $reward = TrophyAwards::find($bonus_type->award_id);

                return $app['blade']->view()->make('admin.messaging.bonus.create', compact('app', 'bonus_type', 'bonus_tags', 'reward'))->render();
            } else {
                return new RedirectResponse($app['url_generator']->generate('messaging.bonus.list'));
            }
        } else {
            $request->request->remove('token');
            $code_type = $request->request->get('code_type');
            $action = $request->request->get('action');
            $request->request->remove('code_type');
            $request->request->remove('action');

            $request->request->set('ext_ids', implode('|', $request->request->get('ext_ids')));
            $request->request->set('game_id', implode('|', $request->request->get('game_id')));
            $request->request->set('excluded_countries', implode(' ', $request->request->get('excluded_countries')));
            $request->request->set('included_countries', implode(',', $request->request->get('included_countries')));

            $bonus_type_template = new BonusTypeTemplate();
            $bonus_type_template->fill($request->request->all());

            if ($request->isXmlHttpRequest())
            {
                $bonus_type_template->id = $request->get('template-id');
                if (!$bonus_type_template->validate())
                    return $app->json(['error' => -1, 'message' => $bonus_type_template->getFirstError()]);
                else
                    return $app->json(['error' => 0, 'message' => '']);
            }

            if ($action == 'edit') {
                $bonus_type_template = BonusTypeTemplate::findOrFail($request->get('template-id'));
                $request->request->remove('action');
                //TODO do validations on bonus template
                $success = $bonus_type_template->update($request->request->all());// todo check before save if dates are ok with carbon try catch
            } else {
                $bonus_type_template = new BonusTypeTemplate();
                $bonus_type_template->fill($request->request->all());
                $success = $bonus_type_template->save();// todo check before save if dates are ok with carbon try catch
            }

            foreach ($bonus_type_template->getErrors() as $errorType)
                foreach ($errorType as $error)
                    $app['flash']->add('warning', "Warning: {$error}");

            if ($success) {
                $app['flash']->add('success', "Bonus template saved successfully.");
            } else {
                $app['flash']->add('danger', "Bonus template not saved.");
            }

            return new RedirectResponse($app['url_generator']->generate('messaging.bonus.list'));
        }
    }

    public function deleteBonusTemplate(Application $app, Request $request)
    {
        $b_template = BonusTypeTemplate::find($request->get('template-id'));

        if ($b_template) {
            $campaign_links_count = MessagingCampaignTemplates::where('bonus_template_id', $b_template->id)->count();
            if ($campaign_links_count > 0) {
                $app['flash']->add('danger', "Cannot be deleted because is linked to a recurring email/sms.");
            } else {
                if ($b_template->delete()) {
                    $app['flash']->add('success', "Bonus template deleted successfully.");
                } else {
                    $app['flash']->add('warning', "There was an error and the bonus code template was not deleted.");
                }
            }
        } else {
            $app['flash']->add('warning', "Bonus code template not found.");
        }

        return new RedirectResponse($app['url_generator']->generate('messaging.bonus.list'));
    }

    public function deleteVoucherTemplate(Application $app, Request $request)
    {
        $v_template = VoucherTemplate::find($request->get('template-id'));

        if ($v_template) {
            $campaign_links_count = MessagingCampaignTemplates::where('voucher_template_id', $v_template->id)->count();
            if ($campaign_links_count > 0) {
                $app['flash']->add('danger', "Cannot be deleted because is linked to a recurring email/sms.");
            } else {
                if ($v_template->delete()) {
                    $app['flash']->add('success', "Voucher template removed successfully.");
                } else {
                    $app['flash']->add('warning', "There was an error and the voucher template was not deleted.");
                }
            }
        } else {
            $app['flash']->add('warning', "Voucher template not found.");
        }

        return new RedirectResponse($app['url_generator']->generate('messaging.vouchers.list'));
    }

    public function createVoucherTemplate(Application $app, Request $request)
    {
        if ($request->isMethod('GET')) {
            $bonus_name = "";
            $reward_name = "";
            if (!empty($request->get('template-id'))) {
                $voucher_template = VoucherTemplate::findOrFail($request->get('template-id'));
                if ($voucher_template)
                {
                    $bonus_name = BonusTypeTemplate::find($voucher_template->bonus_type_template_id)->bonus_name;
                    $reward_name = TrophyAwards::find($voucher_template->trophy_award_id)->description;

                    $voucher_template->deposit_method = empty($voucher_template->deposit_method) ? [] : explode(',', $voucher_template->deposit_method);
                    $voucher_template->games = empty($voucher_template->games) ? [] : explode(',', $voucher_template->games);
                    $voucher_template->game_operators = empty($voucher_template->game_operators) ? [] : explode(',', $voucher_template->game_operators);
                    $voucher_template->user_on_forums = empty($voucher_template->user_on_forums) ? [] : explode(',', $voucher_template->user_on_forums);

                }

            }
            return $app['blade']->view()->make('admin.messaging.vouchers.template-form', compact('app', 'voucher_template', 'bonus_name', 'reward_name'))->render();
        } else {

            $voucher_template = VoucherTemplate::find($request->get('template-id'));
            if ($voucher_template == null)
                $voucher_template = new VoucherTemplate();

            $error = false;
            $voucher_template->template_name = $request->get('template_name');
            $voucher_template->voucher_name = $request->get('voucher_name');
            $voucher_template->voucher_code = $request->get('voucher_code');
            $voucher_template->count = $request->get('count');
            $voucher_template->exclusive = $request->get('exclusive', 1);
            $voucher_template->bonus_type_template_id = $request->get('bonus_type_template_id');
            $voucher_template->trophy_award_id = $request->get('trophy_award_id');

            $voucher_template->expire_time = $request->get('expire_time');

            $voucher_template->deposit_amount = $request->get('deposit_amount');
            $deposit_period = explode(' - ', $request->get('deposit_period'));
            $voucher_template->deposit_start = empty($deposit_period[0]) ? '' : $deposit_period[0];
            $voucher_template->deposit_end = empty($deposit_period[1]) ? '' : $deposit_period[1];
            $voucher_template->deposit_method = !is_null($request->get('deposit_method')) ? implode(',', $request->get('deposit_method')) : '';

            $voucher_template->wager_amount = $request->get('wager_amount');
            $wager_period = explode(' - ', $request->get('wager_period'));
            $voucher_template->wager_start = empty($wager_period[0]) ? '' : $wager_period[0];
            $voucher_template->wager_end = empty($wager_period[1]) ? '' : $wager_period[1];

            $voucher_template->games = !is_null($request->get('games')) ? implode(',',$request->get('games')) : '';
            $voucher_template->game_operators = !is_null($request->get('game_operators')) ? implode(',', $request->get('game_operators')) : '';
            $voucher_template->user_on_forums = !is_null($request->get('user_on_forums')) ? implode(',', $request->get('user_on_forums')) : '';

            if (!$voucher_template->save()) {
                $msg = $voucher_template->getLastError()[0];
                $app['flash']->add('danger', "Voucher template not ".($voucher_template->exists ? 'updated' : 'created').". $msg");
                return new RedirectResponse($app['url_generator']->generate('messaging.vouchers.create-template'));
            } else {
                if ($voucher_template->hasErrors()) {
                    $error = $voucher_template->getLastError()[0];
                    $app['flash']->add('warning', "Warning: {$error}");
                }
                $app['flash']->add('success', "Voucher template ".($voucher_template->exists ? 'updated' : 'created')." successfully.");
                return new RedirectResponse($app['url_generator']->generate('messaging.vouchers.list'));
            }

        }
    }

    public function indexb(Application $app, Request $request)
    {
        $bonus_types = BonusType::query()->orderBy('id');
        $defaults = [ 'order' => ['column' => 'id', 'dir' => 'desc'], 'length' => 25];
        $paginationHelper = new PaginationHelper($bonus_types, $request, $defaults);

        if ($request->isXmlHttpRequest()) {
            return $app->json($paginationHelper->getPage(false));
        }

        $bonusTypes = $paginationHelper->getPage(true);
        return $app['blade']->view()->make('admin.bonus.index', compact('app', 'bonusTypes'))->render();
    }

    public function edit(Application $app, BonusType $bonusType, Request $request)
    {
        if ($request->getRealMethod() == 'POST') {
            $bonusType->update($request->request->all());
        }
        $bonus_tags = [];
        foreach (Game::select('network')->groupBy('network')->get() as $network) {
            $bonus_tags[] = $network->network;
        }
        return $app['blade']->view()->make('admin.bonus.edit', compact('app', 'bonus_tags', 'bonusType'))->render();
    }

    public function filter(Application $app, Request $request)
    {
        return BonusTypeTemplate::where('bonus_name', 'LIKE', '%' . $request->query->get('q') . '%')
            ->orWhere('bonus_code', 'LIKE', '%' . $request->query->get('q') . '%')->get();
    }


}