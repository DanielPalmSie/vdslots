<?php

namespace App\Services\Sportsbook;

use App\Classes\Sportsbook;


class CleanEventService extends Sportsbook
{
    /**
     * Get sport event Via ID
     *
     * @param string $event_id
     * @return NULL|array
     */
    public function getSportEventViaId(string $event_id): ?array
    {
        $query = '
           query SportEventQuery($ext_id: String) {
            sb_sport_event(ext_id: $ext_id) {
              id
              ext_id
              name
              sport_ext_id
            }
          }
      ';

        $variables = [
            'ext_id' => "sr:match:$event_id",
        ];

        $queryParameters = [
            'query' => $query,
            'variables' => json_encode($variables)
        ];

        try {
            $response = $this->client
                ->request(
                    'GET',
                    "$this->sportsbookBaseUri" . "graphql?" . http_build_query($queryParameters)
                );

            $response_body = json_decode($response->getBody(), true);

            return $response_body['data']['sb_sport_event'] ?? NULL;
        } catch (\Exception $e) {

            $this->app['monolog']->addError("Error while searching for an event: " . $e->getMessage());
            return NULL;
        }
    }

    /**
     * Remove sport event Via ID
     *
     * @param string $event_id
     * @return bool
     */
    public function removeSportEventViaId(string $event_id): bool
    {
        try {
            $response = $this->client
                ->request(
                    'POST',
                    "$this->sportsbookBaseUri" . "sports/clean-events",
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json',
                            'X-API-KEY' => getenv('USER_SERVICE_SPORTS_API_KEY')
                        ],
                        'json' => [
                            'events' => $event_id
                        ]
                    ]
                );

            $response_body = json_decode($response->getBody(), true);

            if (isset($response_body['success']) && !$response_body['success']) {
                return false;
            }

            return true;
        } catch (\Exception $e) {

            $this->app['monolog']->addError("Error while attempting to remove event: " . $e->getMessage());
            return false;
        }
    }
}
