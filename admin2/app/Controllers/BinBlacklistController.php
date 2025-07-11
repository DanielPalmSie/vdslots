<?php

namespace App\Controllers;

use App\Classes\Mts;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;


class BinBlacklistController implements ControllerProviderInterface
{
    const BIN_STATUS = [
        '1' => 'Blocked',
        '0' => 'Allowed'
    ];

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\BinBlacklistController::index')
            ->bind('bin-blacklist.index')
            ->before(function () use ($app) {
                if (!p('admin.payments.bin-blacklist')) {
                    $app->abort(403);
                }
            });

        $factory->get('/create/', 'App\Controllers\BinBlacklistController::create')
            ->bind('bin-blacklist.create')
            ->before(function () use ($app) {
                if (!p('admin.payments.bin-blacklist.create')) {
                    $app->abort(403);
                }
            });

        $factory->post('/store/', 'App\Controllers\BinBlacklistController::store')
            ->bind('bin-blacklist.store')
            ->before(function () use ($app) {
                if (!p('admin.payments.bin-blacklist.create')) {
                    $app->abort(403);
                }
            });

        $factory->get('/edit/{id}/', 'App\Controllers\BinBlacklistController::edit')
            ->bind('bin-blacklist.edit')
            ->before(function () use ($app) {
                if (!p('admin.payments.bin-blacklist.update')) {
                    $app->abort(403);
                }
            });

        $factory->post('/update/', 'App\Controllers\BinBlacklistController::update')
            ->bind('bin-blacklist.update')
            ->before(function () use ($app) {
                if (!p('admin.payments.bin-blacklist.update')) {
                    $app->abort(403);
                }
            });


        return $factory;
    }

    public function index(Application $app, Request $request)
    {
        $params = array_filter([
            'bin' => $request->get('bin'),
            'status' => $request->get('status')
        ], fn($value) => !is_null($value) && $value !== '');

        $mts = new Mts($app);

        $blacklistedBins = $mts->getBlacklistedBins($params);

        return $app['blade']->view()
            ->make('admin.payments.bin-blacklist.index', compact('app', 'blacklistedBins', 'params'))
            ->render();
    }

    public function create(Application $app)
    {
        return $app['blade']->view()->make('admin.payments.bin-blacklist.create', compact('app'))->render();
    }

    public function store(Application $app, Request $request)
    {
        $mts = new Mts($app);

        try {
            $blacklistedBin = $mts->createBlacklistedBin([
                'bin' => $request->get('bin'),
                'status' => $request->get('status'),
                'comment' => $request->get('comment'),
                'created_by' => UserRepository::getCurrentUserId(),
            ]);

            $this->logAction($blacklistedBin);

            $app['flash']->add('success', 'Blacklisted BIN was created successfully.');

            return $app->redirect($app['url_generator']->generate('bin-blacklist.index'));

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            return $app['blade']->view()
                ->make('admin.payments.bin-blacklist.create', compact('app', 'errorMessage'))->render();
        }
    }

    public function edit(Application $app, Request $request)
    {
        $mts = new Mts($app);
        $blacklistedBin = $mts->getBlacklistedBinById($request->get('id'));

        return $app['blade']->view()
            ->make('admin.payments.bin-blacklist.create', compact('app', 'blacklistedBin'))->render();
    }

    public function update(Application $app, Request $request)
    {
        $updateParams = [
            'bin' => $request->get('bin'),
            'status' => $request->get('status'),
            'comment' => $request->get('comment'),
            'updated_by' => UserRepository::getCurrentUserId(),
        ];

        $mts = new Mts($app);

        try {
            $blacklistedBin = $mts->getBlacklistedBinById($request->get('id'));

            $mts->updateBlacklistedBin($request->get('id'), $updateParams);

            $this->logAction($updateParams, $blacklistedBin);

            $app['flash']->add('success', 'Bin blacklist item was updated successfully.');

            return $app->redirect($app['url_generator']->generate('bin-blacklist.index'));

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            return $app['blade']->view()
                ->make('admin.payments.bin-blacklist.create', compact('app', 'blacklistedBin', 'errorMessage'))
                ->render();
        }
    }

    private function logAction(array $bin, array $oldBin = null)
    {
        $binOldStatus = self::BIN_STATUS[$oldBin['status']];
        $binNewStatus = self::BIN_STATUS[$bin['status']];

        if (!$oldBin) {
            $description = $binNewStatus . " BIN code {$bin['bin']}.";
        } else {
            $description = 'Updated BIN blacklist.';

            $description .= $bin['bin'] != $oldBin['bin'] ?
                " BIN code from {$oldBin['bin']} to {$bin['bin']}." :
                " BIN code: {$bin['bin']}.";

            $description .= $binNewStatus != $binOldStatus ?
                " Status from {$binOldStatus} to {$binNewStatus}." :
                " Status: {$binNewStatus}.";
        }

        $description .= " Reason: {$bin['comment']}";

        ActionRepository::logAction(
            UserRepository::getCurrentUserId(),
            $description,
            $oldBin ? 'update_bin_blacklist' : 'add_bin_blacklist',
            true,
            UserRepository::getCurrentUserId()
        );
    }
}
