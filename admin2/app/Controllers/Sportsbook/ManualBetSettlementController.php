<?php

namespace App\Controllers\Sportsbook;

use App\Models\User;
use App\Repositories\BetsAndWinsRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ManualBetSettlementController implements ControllerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function connect(Application $app): ControllerCollection
    {
        $factory = $app['controllers_factory'];

        $factory->post('/manual-ticket-settlement/user/{user}/',
            'App\Controllers\Sportsbook\ManualBetSettlementController::processSettlement')
            ->convert('user', $app['userProvider'])
            ->bind('sportsbook.manual-ticket-settlement')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.manual-ticket-settle')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function processSettlement(Application $app, Request $request, User $user): RedirectResponse
    {
        $userBetDetails = $this->fetchBetSettleDetails($app, $request, $user);
        $validationError = $this->validateSettlementData($request, $userBetDetails);

        if (!empty($validationError)) {
            $app['flash']->add('danger', $validationError);
            return new RedirectResponse($request->headers->get('referer'));
        }

        $shouldChangeBalance = filter_var($request->get("changeBalance"), FILTER_VALIDATE_BOOLEAN);

        $response = $this->getSettlementResponse($app, $request, $shouldChangeBalance);;
        if (!$response["success"]) {
            $app['monolog']->addError("error", [$response["data"]]);

            $app['flash']->add('danger', $response["data"]);
            return new RedirectResponse($request->headers->get('referer'));
        }

        $this->getProcessedTicketsResponse($app, $response["data"]["settled"], $request->get('action'), true);
        $this->getProcessedTicketsResponse($app, $response["data"]["not_settled"], $request->get('action'), false);

        $action = $this->isReopen($app, $request->get('action')) ? 'ticket reopen' : 'ticket settlement';
        $description = "performed a manual {$action} on user with {$user->id} account with ";
        $description .= ($shouldChangeBalance) ? " BALANCE CHANGE" : " NO BALANCE CHANGE";
        $description .= " on ticket with id {$request->get("betId")}";
        phive('UserHandler')
            ->logAction(
                $user->id,
                $description,
                'sportsbook',
                true,
                null
            );

        return new RedirectResponse($request->headers->get('referer'));
    }

    private function fetchBetSettleDetails(Application $app, Request $request, User $user): array
    {
        $betsWins = new BetsAndWinsRepository($app, $user, $request);
        $betDetails = $betsWins->getSportsbookBets($request->get("betId"));
        $betSelectionDetails = $betsWins->getSportsbookBetDetails($request->get("betId"));

        return [$betDetails, $betSelectionDetails];
    }

    private function validateSettlementData(Request $request, array $userBetDetails): string
    {
        list($betDetails, $betSelectionDetails) = $userBetDetails;

        if (!$betDetails) {
            return "Invalid Bet ID. Bet Details not found.";
        }

        $requestEventExtIds = $request->get("eventExtId");
        if (count($requestEventExtIds) !== count($betSelectionDetails)) {
            return "Settlement not chosen for all tickets";
        }

        $betSelectionDetailsEventExtIds = $betSelectionDetails->pluck("event_ext_id")->toArray();

        if (array_diff($betSelectionDetailsEventExtIds, $requestEventExtIds)) {
            return "Selected Event Ext Ids do not match";
        }

        return "";
    }

    private function getSettlementResponse(Application $app, Request $request, bool $shouldChangeBalance): array
    {
        $ticketSettlementRequest = $app['sportsbook_manual_bet_settlement_service']
            ->setTicketSelectionEventExtIds($request->get("eventExtId"))
            ->setTicketSelectionStatus($request->get("settleStatus"))
            ->changeUserBalance($shouldChangeBalance);

        if ($this->isReopen($app, $request->get('action'))) {
            return $ticketSettlementRequest->reopenTicket($request->get("betId"));
        }

        return $ticketSettlementRequest->settleTicket($request->get("betId"));
    }

    private function isReopen(Application $app, string $action): bool
    {
        return $action === $app['sportsbook_manual_bet_settlement_service']::REOPEN;
    }

    private function getProcessedTicketsResponse(Application $app, ?array $tickets, string $action, bool $isSettled): void
    {
        if ($tickets) {
            $actionType = ($this->isReopen($app, $action)) ? "Reopened" : "Settled";
            $alertType = ($isSettled) ? "success" : "danger";
            $actionResponse = sprintf("%s ticket(s): %s", $actionType, join(',', $tickets));
            $app['flash']->add($alertType, $actionResponse);
        }
    }

}
