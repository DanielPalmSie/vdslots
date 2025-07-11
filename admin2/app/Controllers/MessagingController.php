<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.05.
 * Time: 9:56
 */

namespace App\Controllers;

use App\Classes\Filter\FilterClass;
use App\Classes\Filter\FilterData;
use App\Models\BonusTypeTemplate;
use App\Models\Export;
use App\Models\NamedSearch;
use App\Models\Segment;
use App\Models\SegmentGroup;
use App\Models\UsersSegments;
use App\Models\VoucherTemplate;
use App\Repositories\ContactsFilterRepository;
use App\Repositories\MessagingRepository;
use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\OfflineCampaigns;
use PHPExcel;
use PHPExcel_IOFactory;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class MessagingController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/dashboard/', 'App\Controllers\MessagingController::index')
            ->bind('messaging.index')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/dashboard/stats/', 'App\Controllers\MessagingController::getDashboardStats')
            ->bind('messaging.get-dashboard-stats')
            ->before(function () use ($app) {
                if (!p('messaging.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/contacts/list-contacts/', 'App\Controllers\MessagingController::listContacts')
            ->bind('messaging.contact.list-contacts')
            ->before(function () use ($app) {
                if (!p('messaging.contacts')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/contacts/list-filters/', 'App\Controllers\MessagingController::listContactFilters')
            ->bind('messaging.contact.list-filters')
            ->before(function () use ($app) {
                if (!p('messaging.contacts')) {
                    $app->abort(403);
                }
            });

        $factory->get('/contacts/new-filter-form/', 'App\Controllers\MessagingController::createFilter')
            ->bind('messaging.contact.new-filter-form')
            ->before(function () use ($app) {
                if (!p('messaging.contact.new-filter-form')) {
                    $app->abort(403);
                }
            });

        $factory->post('/contacts/new-filter/', 'App\Controllers\MessagingController::saveFilter')
            ->bind('messaging.contact.new-filter')
            ->before(function () use ($app) {
                if (!p('messaging.contacts.new')) {
                    $app->abort(403);
                }
            });

        $factory->post('/contacts/get-field-data/', 'App\Controllers\MessagingController::getFieldData')
            ->bind('messaging.contact.get-field-data');

        $factory->get('/contacts/edit/{namedSearch}/', 'App\Controllers\MessagingController::editFilter')
            ->convert('namedSearch', $app['namedSearchProvider'])
            ->bind('messaging.contact.edit-filter')
            ->before(function () use ($app) {
                if (!p('messaging.contacts.edit')) {
                    $app->abort(403);
                }
            });

        $factory->post('/contacts/edit/{namedSearch}/', 'App\Controllers\MessagingController::updateFilter')
            ->convert('namedSearch', $app['namedSearchProvider'])
            ->bind('messaging.contact.update-filter')
            ->before(function () use ($app) {
                if (!p('messaging.contacts.edit')) {
                    $app->abort(403);
                }
            });

        $factory->get('/contacts/delete/{namedSearch}/', 'App\Controllers\MessagingController::deleteFilter')
            ->convert('namedSearch', $app['namedSearchProvider'])
            ->bind('messaging.contact.delete-filter')
            ->before(function () use ($app) {
                if (!p('messaging.contacts.delete')) {
                    $app->abort(403);
                }
            });

        $factory->get('/contacts/clone/{namedSearch}/', 'App\Controllers\MessagingController::cloneFilter')
            ->convert('namedSearch', $app['namedSearchProvider'])
            ->bind('messaging.contact.clone-filter')
            ->before(function () use ($app) {
                if (!p('messaging.contacts.new')) {
                    $app->abort(403);
                }
            });

        $factory->get('/segments/segments/form/', 'App\Controllers\MessagingController::createSegment')
            ->bind('messaging.segments.form')
            ->before(function () use ($app) {
                if (!p('messaging.segments.form')) {
                    $app->abort(403);
                }
            });

        $factory->post('/segments/segments/new/', 'App\Controllers\MessagingController::saveSegment')
            ->bind('messaging.segments.new')
            ->before(function () use ($app) {
                if (!p('messaging.segments.new')) {
                    $app->abort(403);
                }
            });
        $factory->get('/contacts/segments/list/', 'App\Controllers\MessagingController::listSegments')
            ->bind('messaging.segments.list')
            ->before(function () use ($app) {
                if (!p('messaging.segments.list')) {
                    $app->abort(403);
                }
            });
        $factory->get('/contacts/segments/edit/{segment}/', 'App\Controllers\MessagingController::editSegment')
            ->bind('messaging.segments.edit')
            ->before(function () use ($app) {
                if (!p('messaging.segments.edit')) {
                    $app->abort(403);
                }
            });

        $factory->get('/contacts/segments/groups/delete/', 'App\Controllers\MessagingController::deleteSegmentGroup')
            ->bind('messaging.segments.groups.delete')
            ->before(function () use ($app) {
                if (!p('messaging.segments.groups.delete')) {
                    $app->abort(403);
                }
            });
        $factory->get('/contacts/segments/delete/{segment}/', 'App\Controllers\MessagingController::deleteSegment')
            ->bind('messaging.segments.delete')
            ->before(function () use ($app) {
                if (!p('messaging.segments.delete')) {
                    $app->abort(403);
                }
            });

        $factory->get('/offline-campaigns/', 'App\Controllers\MessagingController::getOfflineCampaigns')
            ->bind('messaging.offline-campaigns')
            ->before(function () use ($app) {
                if (!p('messaging.offline-campaigns')) {
                    $app->abort(403);
                }
            });
        $factory->get('/offline-campaigns/new/', 'App\Controllers\MessagingController::addOfflineCampaign')
            ->bind('messaging.offline-campaigns.new')
            ->before(function () use ($app) {
                if (!p('messaging.offline-campaigns')) {
                    $app->abort(403);
                }
            });

        $factory->post('/offline-campaigns/save/', 'App\Controllers\MessagingController::saveOfflineCampaign')
            ->bind('messaging.offline-campaigns.save')
            ->before(function () use ($app) {
                if (!p('messaging.offline-campaigns')) {
                    $app->abort(403);
                }
            });

        $factory->get('/offline-campaigns/edit/{campaign}/', 'App\Controllers\MessagingController::editOfflineCampaign')
            ->bind('messaging.offline-campaigns.edit')
            ->before(function () use ($app) {
                if (!p('messaging.offline-campaigns.edit')) {
                    $app->abort(403);
                }
            });

        $factory->get('/offline-campaigns/delete/{campaign}/', 'App\Controllers\MessagingController::deleteOfflineCampaign')
            ->bind('messaging.offline-campaigns.delete')
            ->before(function () use ($app) {
                if (!p('messaging.offline-campaigns.delete')) {
                    $app->abort(403);
                }
            });

        $factory->get('/offline-campaigns/export/{campaign}/', 'App\Controllers\MessagingController::exportOfflineCampaign')
            ->bind('messaging.offline-campaigns.export')
            ->before(function () use ($app) {
                if (!p('messaging.offline-campaigns.export')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function index(Application $app)
    {
        $data = [
            'total_sms' => DB::shsSelect('messaging_campaigns', "SELECT sum(sent_count) AS sum FROM messaging_campaigns WHERE type = 1")[0]->sum,
            'total_email' => DB::shsSelect('messaging_campaigns', "SELECT sum(sent_count) AS sum FROM messaging_campaigns WHERE type = 2")[0]->sum,
            'active_contacts' => DB::table('users AS u')->where('u.active', 1)->selectRaw('COUNT(u.id) as total')->first()->total,
            'filtered_contacts' => NamedSearch::selectRaw('sum(result) as res_sum')->first()->res_sum
        ];

        $past_list = MessagingRepository::getPastCampaignsList();

        return $app['blade']->view()->make('admin.messaging.index', compact('app', 'data', 'past_list'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDashboardStats(Application $app, Request $request)
    {
        if (!empty($request->get('start_date')) && !empty($request->get('end_date')))
        {
            $start_date = Carbon::parse($request->get('start_date'))->startOfDay();
            $end_date = Carbon::parse($request->get('end_date'))->endOfDay();
        } else {
            $start_date = Carbon::now()->startOfMonth();
            $end_date = Carbon::now()->endOfMonth();
        }

        $data = [
            'total_sms' => DB::shsSelect('messaging_campaigns', "SELECT sum(sent_count) AS sum FROM messaging_campaigns WHERE type = 1 
                                        AND sent_time BETWEEN :start_date AND :end_date", ['start_date' => $start_date->toDateTimeString(), 'end_date' => $end_date->toDateTimeString()])[0]->sum,
            'total_email' => DB::shsSelect('messaging_campaigns', "SELECT sum(sent_count) AS sum FROM messaging_campaigns WHERE type = 2
                                        AND sent_time BETWEEN :start_date AND :end_date", ['start_date' => $start_date->toDateTimeString(), 'end_date' => $end_date->toDateTimeString()])[0]->sum,
        ];

        return $app->json(['html' => $app['blade']->view()->make('admin.messaging.partials.dashboard-stats', compact('app', 'data'))->render()]);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function listContacts(Application $app, Request $request)
    {
        $repo = new ContactsFilterRepository();

        if ($request->isXmlHttpRequest())
        {
            if($request->get('filter-id'))
            {
                return $app->json($repo->getFilteredContacts($request));
            }
            return $app->json($repo->getContactByFilter($request));
        } else {
            $page = $repo->getFilteredContacts($request);
            $load_type = 1;
            return $app['blade']->view()->make('admin.messaging.contacts.list-all', compact('app', 'page', 'load_type'))->render();
        }
    }

    /**
     * Filters: list
     * @param Application $app
     * @return mixed
     */
    public function listContactFilters(Application $app)
    {
        $namedSearches = NamedSearch::all();
        foreach($namedSearches as $ns)
            unset($ns->sql_statement);

        return $app['blade']->view()->make('admin.messaging.contacts.filter-list', compact('app', 'namedSearches'))->render();
    }

    /**
     * Filters: create
     * @param Application $app
     * @return mixed
     */
    public function createFilter(Application $app)
    {
        $filter_fields = ContactsFilterRepository::getFilterFields();

        $default_fields = ['name', 'email', 'mobile'];

        return $app['blade']->view()->make('admin.messaging.contacts.form', compact('app', 'filter_fields', 'default_fields'))->render();
    }

    /**
     * Filters: save
     * @param Application $app
     * @param Request $request
     * @return string|RedirectResponse
     */
    public function saveFilter(Application $app, Request $request)
    {
        $repo = new ContactsFilterRepository();
        $res = $repo->saveNamedSearch($request);

        return  $res['success'] === true
            ?   $app->json($res)
            :   $app->json([
                    "success" => false,
                    "message" => "Your filter cannot be save into the database due to an error."
                ]);
    }

    /**
     * Filters: update
     * @param Application $app
     * @param NamedSearch $namedSearch
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateFilter(Application $app, NamedSearch $namedSearch, Request $request)
    {
        $repo = new ContactsFilterRepository();
        $res = $repo->saveNamedSearch($request, $namedSearch);

        return  $res['success'] === true
            ?   $app->json($res)
            :   $app->json([
                "success" => false,
                "message" => "Your filter cannot be save into the database due to an error."
            ]);
    }

    /**
     * Filters: edit
     * @param Application $app
     * @param NamedSearch $namedSearch
     * @return mixed
     */
    public function editFilter(Application $app, NamedSearch $namedSearch)
    {
        unset($namedSearch->sql_statement);

        $filter_fields = ContactsFilterRepository::getFilterFields();

        $output_fields = json_decode($namedSearch->output_fields);

        $default_fields = empty($output_fields) ? ['name', 'email', 'mobile'] : $output_fields;

        return $app['blade']->view()->make('admin.messaging.contacts.form', compact('app', 'namedSearch', 'filter_fields', 'default_fields'))->render();
    }

    /**
     * Filters: clone
     * @param Application $app
     * @param NamedSearch $namedSearch
     * @return mixed
     */
    public function cloneFilter(Application $app, NamedSearch $namedSearch)
    {
        unset($namedSearch->id);
        unset($namedSearch->sql_statement);
        $filter_fields = ContactsFilterRepository::getFilterFields();

        $default_fields = ['name', 'email', 'mobile'];

        return $app['blade']->view()->make('admin.messaging.contacts.form', compact('app', 'namedSearch', 'filter_fields', 'default_fields'))->render();
    }

    /**
     * Filters: get field data
     * @param Application $app
     * @param Request $request
     * @return bool|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getFieldData(Application $app, Request $request)
    {
        if (!empty($field_id = $request->get('field_id')))
        {
            $contacts_filter_repo = new ContactsFilterRepository();
            return $app->json($contacts_filter_repo->getFieldData($field_id));
        }
        return false;
    }

    /**
     * Filters: delete
     * @param Application $app
     * @param NamedSearch $namedSearch
     * @return RedirectResponse
     * @throws \Exception
     */
    public function deleteFilter(Application $app, NamedSearch $namedSearch)
    {
        if ($namedSearch->delete())
        {
            $app['flash']->add('success', "Contacts filter deleted successfully.");
        } else {
            $app['flash']->add('warning', "Your search cannot be saved due to an error.");
        }
        return new RedirectResponse($app['url_generator']->generate('messaging.contact.list-filters'));
    }

    /**
     * Segment: create
     * @param Application $app
     * @return mixed
     * @throws \Exception
     */
    public function createSegment(Application $app)
    {
        $filter_fields  = ContactsFilterRepository::getFilterFields();
        $users_count    = DB::table('users')->count();
        $users_covered  = 0;

        return $app['blade']->view()->make('admin.messaging.segments.form', compact('app','filter_fields', 'users_count', 'users_covered'))->render();
    }

    /**
     * Segment: edit
     * @param Application $app
     * @param string $segment
     * @return mixed
     * @throws \Exception
     */
    public function editSegment(Application $app, $segment)
    {
        $segment = Segment::query()->where('id', $segment)->with('groups')->first();

        $filter_fields  = ContactsFilterRepository::getFilterFields();
        $users_count    = DB::table('users')->count();
        $users_covered  = collect($segment->groups)->sum('users_covered');

        return $app['blade']->view()->make('admin.messaging.segments.form', compact('app','filter_fields', 'segment', 'users_count', 'users_covered'))->render();
    }

    /**
     * Segment: list
     * @param Application $app
     * @return mixed
     */
    public function listSegments(Application $app)
    {
        $segments = Segment::with('groups')->get();

        return $app['blade']->view()->make('admin.messaging.segments.list', compact('app','segments'))->render();
    }

    /**
     * Segment: delete
     * @param Application $app
     * @param string $segment
     * @return RedirectResponse
     */
    public function deleteSegment(Application $app, $segment)
    {
        SegmentGroup::query()->where('segment_id', $segment)->delete();
        Segment::query()->where('id', $segment)->delete();

        return $app->redirect($app['url_generator']->generate('messaging.segments.list'));
    }

    public function deleteSegmentGroup(Application $app, Request $request) {
        try {
            SegmentGroup::query()->where('id', $request->get('id'))->delete();
            UsersSegments::query()->where('group_id', $request->get('id'))->update(['ended_at' => Carbon::now()]);
        } catch (\Exception $e) {

        }

        if ($request->isXmlHttpRequest()) {
            return $app->json(["success" => true]);
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * Segment: validate
     * @param Segment $segment
     * @param Collection $groups
     * @return array|bool
     */
    private function validateSegment($segment, $groups)
    {

        $active = function($el) {
            return empty($el->disabled);
        };

        $total_covered_users = $groups->filter($active)->sum(function ($el) {
            return $el->users_covered->count();
        });

        if ($total_covered_users !== $segment->users_count) {
            return [
                "covered" => $total_covered_users,
                "total" => $segment->users_count,
                "groups" => $groups->map(function ($el) {
                    return [
                        "covered" => $el->users_covered->count()
                    ];
                })->values()->toArray()
            ];
        }

        $overlapping_groups = $groups
            ->filter($active)
            ->map(function ($el) {
                return (object)[
                    "name" => $el->name,
                    "users" => $el->users_covered
                ];
            })
            ->pipe(function ($groups) {
                $unique = [];

                return $groups
                    ->reduce(function ($carry, $group) use ($groups) {
                        return $groups
                            ->map(function ($g) use ($group) {
                                return collect([
                                    $g->name,
                                    $group->name,
                                    $group->users->intersect($g->users)->count()
                                ]);
                            })
                            ->filter(function ($el) {
                                return $el[0] !== $el[1] and $el[2] !== 0;
                            })
                            ->merge($carry);

                    }, [])
                    ->filter(function ($el) use (&$unique) {

                        $string_value = $el->sort()->implode(',');

                        if (!in_array($string_value, $unique)) {

                            $unique[] = $string_value;
                            return true;
                        }
                        return false;
                    });
            })
            ->pipe(function ($result) {
                return $result->count() > 0 ? $result->toArray() : false;
            });

        if ($overlapping_groups) {
            return [
                "overlaps" => $overlapping_groups
            ];
        }

        return false;
    }

    /**
     * Segment: save
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function saveSegment(Application $app, Request $request)
    {
        $segment_id = $request->get('segment_id');

        if (!empty($segment_id = intval($segment_id))) {
            $segment = Segment::query()->where('id', $segment_id)->first();
        } else {
            $segment = new Segment();
        }

        $segment->name = $request->get('name');
        $segment->description = $request->get('description');
        $segment->users_count = DB::table('users')->count();

        $groups = collect($request->get('groups'))->map(function ($el) use ($segment, $request) {
            $group_disabled = !empty($el['disabled']);

            $sql_query = (new FilterClass(
                'users',
                $request,
                new FilterData(),
                $el['query_data'],
                [],
                false,
                false
            ))->setup();
            $group = [
                "sql_statement" => $sql_query->getSql(),
                "form_params" => json_encode($el['query_data']),
                "users_covered" => $group_disabled ? collect([]) : $sql_query->toQueryBuilder()->get()->pluck('id'),
                "name" => $el['group_name'],
                "disabled" => $group_disabled,
            ];

            if (empty($el['id'])) {
                return new SegmentGroup($group);
            } else {
                return SegmentGroup::query()->find($el['id'])->fill($group);
            }
        });

        $validation_errors = $this->validateSegment($segment, $groups);

        if ($validation_errors) {
            return $app->json([
                "success" => false,
                "errors" => $validation_errors
            ]);
        }

        if (empty($request->get('skip_save'))) {
            $segment->save();
            $groups->each(function ($group) use ($segment) {
                /** @var SegmentGroup $group */
                $group
                    ->setAttribute('segment_id', $segment->id)
                    ->setAttribute('users_covered', $group->getAttribute('users_covered')->count())
                    ->save();
            });
            $process = new Process(BASE_DIR . "/console nightly --only updateSegments");
            $process->start();
            return $app->json([
                "success" => true,
                "segment_id" => $segment->id
            ]);
        } else {
            return $app->json([
                "success" => true,
                "groups" => $groups->map(function ($el) {
                    return [
                        "covered" => $el->users_covered->count()
                    ];
                }),
                "covered" => $groups->sum(function ($el) {
                    return $el->users_covered->count();
                }),
                "total" => $segment->users_count
            ]);
        }
    }

    /**
     * Offline campaign: list
     * @param Application $app
     * @return mixed
     */
    public function getOfflineCampaigns(Application $app)
    {
        $campaigns = OfflineCampaigns::with('bonusTemplate')
            ->with('voucherTemplate')
            ->with('namedSearch')
            ->get();

        $exports = Export::query()
            ->whereIn('target_id', $campaigns->pluck('id')->toArray())
            ->where('type', '=', 'offline-campaigns')
            ->get()
            ->keyBy('target_id');

        return $app['blade']->view()->make('admin.messaging.offline-campaigns.list', compact('app', 'campaigns', 'exports'))->render();
    }

    /**
     * Offline campaign: create
     * @param Application $app
     * @return mixed
     */
    public function addOfflineCampaign(Application $app)
    {
        $named_searches = NamedSearch::all();
        $voucher_templates = VoucherTemplate::all();
        $bonus_templates = BonusTypeTemplate::all();

        return $app['blade']->view()->make('admin.messaging.offline-campaigns.new', compact('app', 'named_searches', 'voucher_templates', 'bonus_templates'))->render();
    }

    /**
     * Offline campaign: edit
     * Allowed to edit the campaign only when there's no export for it.
     * @param Application $app
     * @param string $campaign
     * @return mixed
     */
    public function editOfflineCampaign(Application $app, $campaign)
    {
        $export = Export::query()
            ->where('target_id', '=', $campaign)
            ->where('type', '=', 'offline-campaigns')
            ->first();

        if ($export) {
            return $app->redirect($app['url_generator']->generate('messaging.offline-campaigns'));
        }

        $named_searches = NamedSearch::all();
        $voucher_templates = VoucherTemplate::all();
        $bonus_templates = BonusTypeTemplate::all();

        $campaign = OfflineCampaigns::where('id', $campaign)->first();

        return $app['blade']->view()->make('admin.messaging.offline-campaigns.new', compact('app', 'named_searches', 'voucher_templates', 'bonus_templates', 'campaign'))->render();
    }

    /**
     * Offline campaign: save
     * Allowed to edit the campaign only when there's no export for it.
     * @param Application $app
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveOfflineCampaign(Application $app, Request $request)
    {
        $campaign = null;
        if ($request->request->has('campaign')) {
            $export = Export::query()
                ->where('target_id', '=', $campaign)
                ->where('type', '=', 'offline-campaigns')
                ->first();

            if ($export) {
                return $app->redirect($app['url_generator']->generate('messaging.offline-campaigns'));
            }

            $campaign = OfflineCampaigns::find($request->get('campaign'));
        }

        $campaign = $campaign ?? new OfflineCampaigns();
        $campaign["name"] = $request->get('name');
        $campaign["type"] = $type = $request->get('type');
        $campaign["named_search"] = $request->get('named_search_id');
        // when type is no_promotion, we'll not use the template_id so it makes sense to set default value to 0
        // when type is not no_promotion, we'll always have $type_template_id defined in the request
        $campaign["template_id"] = $request->get("{$type}_template_id", 0);
        $campaign->save();

        return new RedirectResponse($app['url_generator']->generate('messaging.offline-campaigns'));
    }

    /**
     * Offline campaign: delete
     * @param Application $app
     * @param $campaign
     * @return RedirectResponse
     * @throws \Exception
     */
    public function deleteOfflineCampaign(Application $app, $campaign)
    {
        OfflineCampaigns::query()->where('id', '=', $campaign)->delete();
        return $app->redirect($app['url_generator']->generate('messaging.offline-campaigns'));
    }

    /**
     * Offline campaign: export
     * @param Application $app
     * @param Request $request
     * @param $campaign
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportOfflineCampaign(Application $app, Request $request, $campaign) {
        $campaign = OfflineCampaigns::where('id', '=', $campaign)->with('namedSearch')->first();

        $contacts   = (new FilterClass(
            'users',
            $request,
            new FilterData(),
            json_decode($campaign->namedSearch->form_params),
            ['username','name','lastname','address','country','city','zipcode'],
            false,
            $campaign->namedSearch->language
        ))->setup();
        $data = DB::shsSelect('users', $contacts->getSql());

        $now = Carbon::now()->format("d_M_Y_H:i:s");
        $filename = "Offline_campaigns_{$now}";

        $header = ['Username', 'Full name', 'Last name', 'Address', 'City', 'Country', 'Zip code'];

        $excel = new PHPExcel();
        $excel->getProperties()->setCreator("System")->setTitle($filename);

        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->fromArray($header, null, 'A1');

        $i = 2;
        foreach ($data as $row) {
            $excel->getActiveSheet()
                ->setCellValue("A{$i}", $row->username)
                ->setCellValue("B{$i}", $row->name)
                ->setCellValue("C{$i}", $row->lastname)
                ->setCellValue("D{$i}", $row->address)
                ->setCellValue("E{$i}", $row->country)
                ->setCellValue("F{$i}", $row->city)
                ->setCellValue("G{$i}", $row->zipcode);
            $i++;
        }

        $excel_writer = PHPExcel_IOFactory::createWriter($excel, 'OpenDocument');

        $file_path = getenv('STORAGE_PATH') . "/offline_campaigns/";

        if (!file_exists($file_path)) {
            mkdir($file_path, 0777, true);
        }

        $excel_writer->save("{$file_path}{$filename}.ods");
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-Disposition: attachment; filename=\"{$filename}.ods\"");
        header("Cache-Control: max-age=0");
        return $excel_writer->save("php://output");
    }

}
