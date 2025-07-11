<?php

namespace App\Services\Sportsbook;

use App\Classes\Sportsbook;
use GuzzleHttp\Exception\GuzzleException;

class BetSettlementReportService extends Sportsbook
{
    private bool $shouldDownloadBetReport = false;
    private ?int $brandId;

    public function setBrand(?int $brandId): self
    {
        $this->brandId = $brandId;

        return $this;
    }

    public function downloadReport(bool $shouldDownload): self
    {
        $this->shouldDownloadBetReport = $shouldDownload;
        return $this;
    }

    /**
     * @param string|null $startDate
     * @param string $endDate
     * @param string $ticketState
     * @return array|mixed
     */
    public function generateBetSettlementReport(?string $startDate, string $endDate, string $ticketState)
    {
        $_SESSION['reports_bets_from'] = $startDate;
        $_SESSION['reports_bets_to'] = $endDate;
        $_SESSION['reports_bets_state'] = $ticketState;
        $_SESSION['brand_id'] = $this->brandId;

        try {
            $response = $this->client
                ->request(
                    'GET',
                    "{$this->sportsbookBaseUri}sports/generate-bet-settlement-report",
                    $this->headers() +
                    ['json' => [
                        "from" => $startDate,
                        "to" => $endDate,
                        "type" => $ticketState,
                        "should_download" => $this->shouldDownloadBetReport,
                        "brand_id" => $this->brandId,
                        "is_admin" => true,
                    ]
                    ]
                );

            $responseBody = json_decode($response->getBody(), true);

            if ($response->getStatusCode() > 200 || !$responseBody["success"]) {
                $this->app['monolog']->addError('Bet Settlement Report Generate Error', [$responseBody]);

                return ['success' => false, "data" => [$responseBody["errors"]["message"] ?? "Something went wrong"]];
            }

        } catch (GuzzleException $e) {
            $this->app['monolog']->addError('Bet Settlement Report Generate Error', [$e->getMessage()]);

            return ['success' => false, "data" => [$e->getMessage()]];
        }

        return json_decode($response->getBody(), true);

    }

    private function headers(): array
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'X-API-KEY' => getenv('USER_SERVICE_SPORTS_API_KEY')
            ]
        ];
    }

}
