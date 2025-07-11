<?php

namespace GamesRecommendations\Traits;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

trait ApiClientTrait
{
    private GuzzleClient $client;
    private string $apiUrl;

    /**
     * Initialize API client
     */
    private function initializeApiClient(): void
    {
        if (!isset($this->client)) {
            $this->client = new GuzzleClient();
        }
    }

    public function setApiUrl(string $url): void
    {
        $this->apiUrl = $url;
    }

    /**
     * Get game recommendations for unlogged users
     */
    public function getUnloggedRecommendations(array $params = [])
    {
        return $this->sendGetRequest("{$this->apiUrl}/api/players/unlogged/game-recommendations", $params);
    }

    /**
     * Get game recommendations for a specific player
     */
    public function getPlayerRecommendations(int $playerId, array $params = [])
    {
        return $this->sendGetRequest("{$this->apiUrl}/api/players/{$playerId}/game-recommendations", $params);
    }

    /**
     * Get section recommendations for a specific player
     */
    public function getSectionRecommendations(int $playerId)
    {
        return $this->sendGetRequest("{$this->apiUrl}/api/sections/{$playerId}/section-recommendations");
    }

    /**
     * Send a GET request with caching
     */
    private function sendGetRequest(string $url, array $params = [])
    {
        $cacheKey = $this->generateCacheKey($url, $params);
        $cachedData = phMgetArr($cacheKey);

        if ($cachedData) {
            return $cachedData;
        }

        try {
            $this->initializeApiClient();
            $response = $this->client->request('GET', $url, [
                'query' => $params,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            // Cache the data
            phMsetArr($cacheKey, $data);
            return $data;
        } catch (ClientException | ConnectException | RequestException | GuzzleException $e) {
            error_log("API Request failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a cache key for the API request
     */
    private function generateCacheKey(string $url, array $params): string
    {
        return 'zb' . md5($url . json_encode($params));
    }
}
