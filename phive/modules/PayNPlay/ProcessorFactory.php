<?php
namespace PayNPlay;

use PayNPlay\BankIDProcessor;
use PayNPlay\MtsClientProcessor;
use Videoslots\Mts\MtsClient;

/**
 * Factory for providers
 */
class ProcessorFactory
{
    /**
     *
     */
    private const STRATEGY_TRUSTLY = 'strategy_trustly';
    /**
     *
     */
    private const STRATEGY_SWISH = 'strategy_swish';

    /**
     * @var null
     */
    private static $instance = null;

    /**
     *
     */
    private function __construct() {}

    /**
     * @return ProcessorFactory|null
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ProcessorFactory();
        }

        return self::$instance;
    }

    /**
     * @param string $strategy
     * @param int $step
     * @return ProcessorInterface
     */
    public function create(string $strategy, int $step = 0): ProcessorInterface
    {
        $mtsClient = new MtsClient(
            phive('Cashier')->getSetting('mts'),
            phive('Logger')->channel('payments')
        );

        if ($strategy == self::STRATEGY_TRUSTLY) {
            $client = new MtsClientProcessor(
                $mtsClient,
                \Supplier::Trustly
            );

        } else if($strategy == self::STRATEGY_SWISH && $step == 1) {
            $client = new BankIDProcessor($strategy, $step);
            $client->setVerificationSupplier(phive('DBUserHandler/BankId'));
        } else if($strategy == self::STRATEGY_SWISH && $step == 2) {
            $client = new MtsClientProcessor(
                $mtsClient,
                \Supplier::Swish
            );
        } else {
            throw new \InvalidArgumentException("Invalid strategy: $strategy");
        }

        return $client;
    }
}
