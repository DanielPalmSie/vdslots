<?php

namespace App\Repositories;

use Silex\Application;
use Videoslots\MtsSdkPhp\Endpoints\Accounts\GetAccounts;
use Videoslots\MtsSdkPhp\MtsClient;
use Videoslots\MtsSdkPhp\MtsClientFactory;

class AccountsRepository
{
    private MtsClient $mtsClient;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->mtsClient = MtsClientFactory::create(
            $app['mts.config']['base.uri'],
            getenv('MTS_API_KEY'),
            'admin2',
            phive('Logger')->channel('payments')
        );
    }

    public function getUserAccounts(int $userId, string $supplier = null): array
    {
        $endpoint = (new GetAccounts())
            ->forUser($userId)
            ->withStatus(1, 0, -1);

        if ($supplier) {
            $endpoint->forSupplier($supplier);
        }

        $response = $this->mtsClient->call($endpoint);

        return $response->collection;
    }
}
