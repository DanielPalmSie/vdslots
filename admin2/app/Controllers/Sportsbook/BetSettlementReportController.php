<?php

namespace App\Controllers\Sportsbook;

use App\Services\Sportsbook\Constants\TicketStates;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

class BetSettlementReportController implements ControllerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function connect(Application $app): ControllerCollection
    {
        $factory = $app['controllers_factory'];

        $factory->get('/bet-settlement-report/',
            'App\Controllers\Sportsbook\BetSettlementReportController::index')
            ->bind('sportsbook.unsettled-tickets-report')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.download-not-settled-tickets')) {
                    $app->abort(403);
                }
            });

        $factory->get('/bet-settlement-report/unsettled-bets/',
            'App\Controllers\Sportsbook\BetSettlementReportController::getUnsettledBets')
            ->bind('sportsbook.generate-unsettled-tickets-report')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.download-not-settled-tickets')) {
                    $app->abort(403);
                }
            });

        $factory->get('/bet-settlement-report/download-unsettled-bets/',
            'App\Controllers\Sportsbook\BetSettlementReportController::downloadUnsettledBets')
            ->bind('sportsbook.download-unsettled-tickets-report')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.download-not-settled-tickets')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function index(Application $app, Request $request)
    {
        return $app['blade']
            ->view()
            ->make('admin.sportsbook.unsettled-tickets-report', compact('app'))
            ->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function getUnsettledBets(Application $app, Request $request)
    {
        $fromDate = $request->get('fromDate');
        $toDate = $request->get('toDate');
        $brandId = $request->get('brandId') ?: null;

        $bets = $app['sportsbook_bet_settlement_service']
            ->downloadReport(false)
            ->setBrand($brandId)
            ->generateBetSettlementReport(
                $fromDate,
                $toDate,
                TicketStates::ACCEPTED
            );

        $data = $bets["data"];

        if (!$bets["success"]) {
            $error = true;
            $data = $data[0];
            return $app['blade']
                ->view()
                ->make('admin.sportsbook.unsettled-tickets-report', compact('app', 'error', 'data'))
                ->render();
        }

        if (!count($data)) {
            $data = "No records found";
        }

        return $app['blade']
            ->view()
            ->make('admin.sportsbook.unsettled-tickets-report', compact('app', 'data'))
            ->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function downloadUnsettledBets(Application $app, Request $request)
    {
        $bets = $app['sportsbook_bet_settlement_service']
            ->downloadReport(true)
            ->setBrand($_SESSION['brand_id'])
            ->generateBetSettlementReport(
                $_SESSION['reports_bets_from'],
                $_SESSION['reports_bets_to'],
                TicketStates::ACCEPTED
            );
        $data = $bets["data"][0];

        if (!$bets["success"]) {
            $error = true;
            return $app['blade']
                ->view()
                ->make('admin.sportsbook.unsettled-tickets-report', compact('app', 'error', 'data'))
                ->render();
        }

        $downloadUrl = getenv('USER_SERVICE_SPORT_URL') . $data;
        $data = "Download Successful. <a href='$downloadUrl'> Click here to get your download </a>";

        return $app['blade']
            ->view()
            ->make('admin.sportsbook.unsettled-tickets-report', compact('app', 'data'))
            ->render();
    }
}
