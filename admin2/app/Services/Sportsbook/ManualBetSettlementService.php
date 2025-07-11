<?php

namespace App\Services\Sportsbook;

use App\Classes\Sportsbook;
use GuzzleHttp\Exception\GuzzleException;

class ManualBetSettlementService extends Sportsbook
{

    public const OPEN = 'open';
    public const VOID = 'void';
    public const WIN = 'win';
    public const LOSS = 'loss';
    public const BET = 'bet';
    public const LOST = 'lost';
    public const REOPEN = 'reopen';
    public const SETTLE = 'settle';
    private array $eventExtIds;
    private array $settleStatuses;
    private bool $changeUserBalance;

    public function settleTicket(int $betId): array
    {
        return $this->processTicketAction($betId, self::SETTLE);
    }

    private function processTicketAction(int $betId, string $action): array
    {
        if (!$this->eventExtIds || !$this->settleStatuses) {
            return $this->patternResponse(false, "Missing Ext Ids or Statuses");
        }

        if (count($this->eventExtIds) !== count($this->settleStatuses)) {
            return $this->patternResponse(false, "Event Ext Ids must match settlement statuses");
        }

        $settlementPayload = $this->buildSettlementPayload();

        $uri = "{$this->sportsbookBaseUri}sports/manual-bet-settlement/{$betId}/{$action}";

        $options = [
            'headers' => $this->headers(),
            'json' => [
                "ticket_settlement_details" => $settlementPayload,
                "should_change_user_balance" => $this->changeUserBalance
            ],
        ];

        try {
            $response = $this->client->request('POST', $uri, $options);
            $contents = $response->getBody()->getContents();
            $responseBody = json_decode($contents, true);

            if ($response->getStatusCode() > 200 || !$responseBody["success"]) {
                $this->app['monolog']->addError("Manual Bet Settlement Error", [$responseBody]);
                return $this->patternResponse(false, $responseBody["errors"]["message"] ?? "Something went wrong");
            }

        } catch (GuzzleException $e) {
            $this->app['monolog']->addError("Manual Bet Settlement Error", [$e->getMessage()]);
            return $this->patternResponse(false, $e->getMessage());
        }

        return $responseBody;
    }

    private function buildSettlementPayload(): array
    {
        $settlementPayload = [];
        foreach ($this->eventExtIds as $key => $eventExtId) {
            $settlementPayload[] = [
                "ticket_event_ext_id" => $eventExtId,
                "selection_result" => strtolower($this->settleStatuses[$key])
            ];
        }

        return $settlementPayload;
    }

    private function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
            'X-API-KEY' => getenv('USER_SERVICE_SPORTS_API_KEY')
        ];
    }

    public function reopenTicket(int $betId): array
    {
        return $this->processTicketAction($betId, self::REOPEN);
    }

    public function setTicketSelectionEventExtIds(array $ticketSelectionEventExtIds): self
    {
        $this->eventExtIds = $ticketSelectionEventExtIds;
        return $this;
    }

    public function setTicketSelectionStatus(array $settleStatus): self
    {
        $this->settleStatuses = $settleStatus;
        return $this;
    }

    public function changeUserBalance(bool $changeBalance = false): self
    {
        $this->changeUserBalance = $changeBalance;
        return $this;
    }

}
