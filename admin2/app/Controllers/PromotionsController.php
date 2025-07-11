<?php

namespace App\Controllers;

use App\Models\Race;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Classes\FormBuilder\FormBuilder;
use App\Classes\FormBuilder\Elements\ElementInterface;
use App\Repositories\RacesRepository;

class PromotionsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\PromotionsController::dashboard')
            ->bind('promotions.dashboard')
            ->before(function () use ($app) {
                if (!p('promotions.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/races/', 'App\Controllers\PromotionsController::races')
            ->bind('promotions.races.index')
            ->before(function () use ($app) {
                if (!p('promotions.races')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/races/edit/', 'App\Controllers\PromotionsController::raceEdit')
            ->bind('promotions.races.edit')
            ->before(function () use ($app) {
                if (!p('promotions.races')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/races/search/', 'App\Controllers\PromotionsController::racesSearch')
            ->bind('promotions.races.search')
            ->before(function () use ($app) {
                if (!p('promotions.races')) {
                    $app->abort(403);
                }
            })
            ->method('GET');

        return $factory;
    }

    public function dashboard(Application $app)
    {
        return $app['blade']->view()->make('admin.promotions.index', compact('app'))->render();
    }

    public function races(Application $app, Request $request)
    {
        $breadcrumb = 'List and Search';

        return $app['blade']->view()->make('admin.promotions.races.index', compact('app', 'breadcrumb'))->render();
    }

    public function racesSearch(Application $app, Request $request)
    {
        $races_repository = new RacesRepository($app);

        $races_data = $races_repository->getRaces('', (int) $request->get('start', 0), (int) $request->get('length', 50));

        $data = [];

        foreach ($races_data['data'] as $row) {
            $data[] = [
                '<a href="'.$app['url_generator']->generate('promotions.races.edit').'?id='.$row->id.'">'.$row->id.'</a>',
                $row->race_type,
                $row->display_as,
                $row->levels,
                substr($row->prizes, 0, 50).'...',
                $row->game_categories,
                $row->games,
                $row->start_time,
                $row->end_time,
                $row->created_at,
                $row->closed
            ];
        }

        // override games array with DataTables style array
        $races_data['data'] = $data;

        return json_encode($races_data);
    }

    public function raceEdit(Application $app, Request $request)
    {
        if ((int)$request->get('id') > 0) {
            $race = Race::find((int)$request->get('id'));
        } else {
            $race = new Race();
        }

        if ($request->isMethod('post') && !empty($request->get('race_type')) && !empty($request->get('display_as'))) {
            // create new race here
            if ($request->get('save') <= 0) {
                $race = new Race();
            }

            $race->race_type = $request->get('race_type');
            $race->display_as = $request->get('display_as');
            $race->levels = $request->get('levels');
            $race->prizes = $request->get('prizes');
            $race->game_categories = $request->get('game_categories');
            $race->games = $request->get('games');
            $race->start_time = $request->get('start_time');
            $race->end_time = $request->get('end_time');
            $race->save();
        }

        $types = [
            ['value' => 'spins', 'text' => 'Spins', 'attr' => []]
        ];

        $display_as = [
            ['value' => 'race', 'text' => 'Race', 'attr' => []]
        ];

        $oFormBuilder = new FormBuilder();

        $oFormBuilder->createSelect([
            'name' => 'race_type',
            'value' => !empty($race->type) ? $race->type : 'spins',
            'label' => [
                'text' => 'Race type',
                'wrap' => false,
                'after' => false
            ], // or 'firstname'
            'comment' => 'Spins (atm only spins is doable)',
            'attr' => [
                'class' => 'form-control'
            ],
            'options' => array_merge([['value' => '', 'text' => 'Please select', 'attr' => []]], $types),
            'rules' => [
                'required' => true
            ]
        ]);

        $oFormBuilder->createSelect([
            'name' => 'display_as',
            'value' => !empty($race->display_as) ? $race->display_as : 'race',
            'label' => [
                'text' => 'Display as',
                'wrap' => false,
                'after' => false
            ], // or 'firstname'
            'comment' => 'Race (atm only race is doable)',
            'attr' => [
                'class' => 'form-control'
            ],
            'options' => array_merge([['value' => '', 'text' => 'Please select', 'attr' => []]], $display_as),
            'rules' => [
                'required' => true
            ]
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'levels',
            'value' => $race->levels,
            'label' => [
                'text' => 'Levels',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'Single number, for instance 25 which then acts as a threshold, OR for instance 25:1|100:2|200:3 where a bet of 25 cents generates one spin, 100 generates 2 and so on.',
            'attr' => [
                'class' => 'form-control'
            ],
            'rules' => [
                'required' => true,
                'pattern' => '[0-9|:]*'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'prizes',
            'value' => $race->prizes,
            'label' => [
                'text' => 'Prizes',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'iPad:Galaxy S4 etc, OR 100:50:25. Note that this field controls how many people will show in the race box too.',
            'attr' => [
                'class' => 'form-control'
            ],
            'rules' => [
                'required' => true,
                'minlength' => 2, // max chars
                'min' => 3,
                'pattern' => '[a-zA-Z0-9:]*'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'game_categories',
            'value' => $race->game_categories,
            'label' => [
                'text' => 'Game categories',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'For example slots,videoslots note the comma separation.',
            'attr' => [
                'class' => 'form-control'
            ],
            'rules' => [
                'required' => true,
                'minlength' => 2, // max chars
                'min' => 3,
                'pattern' => '[a-z,]*'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'games',
            'value' => $race->games,
            'label' => [
                'text' => 'Games',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'The Ext game name of the game, separated with commas, for instance mgs_cops_and_robbers,mgs_billion_dollar_gran. If this field is set it will override Game categories completely, in fact if this field is set Game categories should be empty.',
            'attr' => [
                'class' => 'form-control'
            ],
            'rules' => [
                'required' => false,
                'pattern' => '[0-9a-z_,]*'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'start_time',
            'value' => $race->start_time,
            //'type' => 'date',
            'label' => [
                'text' => 'Start time',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'On the format yyyy-mm-dd hh:mm:ss, for example 2014-05-04 00:00:00',
            'attr' => [
                'class' => 'form-control datetimepicker'
            ],
            'rules' => [
                'required' => true,
                'maxlength' => 19,
                'min' => 19,
                'pattern' => '[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}]:[0-9]{2}*'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'end_time',
            'value' => $race->end_time,
            //'type' => 'date',
            'label' => [
                'text' => 'End time',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'On the format yyyy-mm-dd hh:mm:ss, for example 2014-05-05 23:59:59',
            'attr' => [
                'class' => 'form-control datetimepicker'
            ],
            'rules' => [
                'required' => false,
                'min' => 16, // max chars
                'maxlength' => 16, // max chars
                'pattern' => '[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}]:[0-9]{2}*'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'closed',
            'value' => $race->closed,
            //'type' => 'date',
            'label' => [
                'text' => 'Closed',
                'wrap' => false,
                'after' => false,
            ], // or 'firstname'
            'comment' => 'Used by the logic to mark which races have been paid or not, <strong>leave this one as it is, it is set automatically by the system</strong>',
            'attr' => [
                'class' => 'form-control',
                'readonly' => true
            ],
            'rules' => [
                'required' => false,
                'minlength' => 2, // max chars
                'min' => 3,
                'pattern' => 'alnum'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        $oFormBuilder->createInput([
            'name' => 'save',
            'value' => '',
            'attr' => [
                'id' => 'frm_save_val'
            ],
            'type' => 'hidden',
            'template' => 'snippets/html/my-input.html'
        ]);

        if ($oFormBuilder->valid()) {
            // only validated on client side now (HTML5 regular expression)
        }

        $id = $request->get('id', 0);

        $breadcrumb = ($id > 0) ? 'Edit' : 'Add New';

        return $app['blade']->view()->make('admin.promotions.races.edit', compact('app', 'oFormBuilder', 'breadcrumb', 'id'))->render();
    }

}