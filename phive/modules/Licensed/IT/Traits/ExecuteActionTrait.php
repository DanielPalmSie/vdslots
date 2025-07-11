<?php
namespace IT\Traits;

use IT\Abstractions\AbstractResponse;
use IT\Loaders\LoaderAction;
use Exception;

/**
 * Trait ExecuteActionTrait
 * @package IT\Traits
 */
trait ExecuteActionTrait
{
    /**
     * @var LoaderAction
     */
    private LoaderAction $loader_action;

    /**
     * @return LoaderAction
     * @throws Exception
     */
    protected function getLoaderAction(): LoaderAction
    {
        if (empty($this->loader_action)) {
            $this->loader_action = new LoaderAction();
        }

        return $this->loader_action;
    }

    /**
     * @param string $action_name
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function execAction(string $action_name, array $data): array
    {
        if(phive('Licensed')->getSetting('IT')['pacg']['is_disabled'] || phive('Licensed')->getSetting('IT')['pgda']['is_disabled']){
            // Service calls have been disabled so we just return mock data as response, used for local testing.
            return [
                'code' => 0,
                'response' => [
                    'session_id' => uniqid()
                ]
            ];
        }

        if ($this->mockAction($action_name)) {
            return $this->mockAction($action_name);
        }

        $this->getLoaderAction();
        $action = $this->loader_action->getAction($action_name);
        try {
            return $this->response($action->execute($data));
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception);
        }
    }

    private function mockAction($action_name)
    {
        $pacg_settings = phive('Licensed')->getSetting('IT')['pacg'];
        if (isset($pacg_settings['mock_actions'][$action_name])) {
            return $pacg_settings['mock_actions'][$action_name];
        }
    }

    /**
     * @param Exception $exception
     * @return array
     */
    protected function exceptionResponse(Exception $exception): array
    {
        $code = 500;
        $message = $exception->getMessage();

        if ($message === 'Internal Error (from client)') {
            $code = 1501;
            $message = 'Server response: ' . $message . '. This can mean a required field was not sent, or there was an issue with encrypting the message, or something else. ';
        } elseif (strpos($message, 'java.rmi.RemoteException') !== false) {
            $code = 1502;
            $message = 'RemoteException: ' . $message;
        }

        return [
            'code' => $code,
            'message' => $message,
            'trace' => $exception->getTrace()
        ];
    }

    /**
     * @param AbstractResponse $response
     * @return array
     */
    protected function response(AbstractResponse $response): array
    {
        return $response->toArray();
    }
}
