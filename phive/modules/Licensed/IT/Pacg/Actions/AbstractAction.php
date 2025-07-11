<?php
namespace IT\Pacg\Actions;

/**
 * Class AbstractAction
 */
abstract class AbstractAction
{
    /**
     * @var \SoapClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $settings;


    /**
     * AbstractAction constructor.
     * @param \SoapClient $client
     * @param array $settings
     * @throws \Exception
     */
    public function __construct(\SoapClient $client, array $settings)
    {
        $this->client = $client;
        $this->settings = $settings;
    }


    /**
     * @return mixed
     */
    abstract public function execute($data);
}