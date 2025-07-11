<?php

namespace App\Controllers\Api;

use App\Models\User;
use Exception;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WebhookController implements ControllerProviderInterface
{
    // The recipient is invalid.
    public const SPARKPOST_INVALID_RECIPIENT = 10;
    // No recipient could be determined for the message.
    public const SPARKPOST_NO_RECIPIENT = 30;
    // The message is an unsubscribe request.
    public const SPARKPOST_UNSUBSCRIBE = 90;

    public function connect(Application $app)
    {

        $factory = $app['controllers_factory'];

        $factory->post('/sparkpost/', 'App\Controllers\Api\WebhookController::sparkpost');

        return $factory;
    }

    /**
     * Handle sparkpost webhook
     *
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function sparkpost(Application $app, Request $request): JsonResponse
    {
        try {
            $messages = [];
            try {
                $messages = json_decode($request->getContent(), true);
            } catch (Exception $e) {
                $app['monolog']->addError($e->getMessage(), [$request->getContent()]);
            }
            $_SESSION['username'] = 'system';

            foreach ($messages as $msg) {
                $type = $msg['msys']['message_event']['type'];
                $class = $msg['msys']['message_event']['bounce_class'];
                $email = $msg['msys']['message_event']['rcpt_to'];

                if ($this->isHardBounce($type, $class)) {
                    $error = User::unsubscribe($email);
                    if (!empty($error)) {
                        $app['monolog']->addError($error);
                    }
                }
            }
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                "WebhookController::sparkpost Throwable [%s] on line %d: %s. Request body: %s",
                get_class($e),
                $e->getLine(),
                $e->getMessage(),
                $request->getContent()
            );
            $app['monolog']->addError($errorMessage);
        }

        return $app->json();
    }

    /**
     * Detect if event is a hard bounce
     * Full list of bounce codes can be found at:
     *  https://www.sparkpost.com/docs/deliverability/bounce-classification-codes
     * @param $type
     * @param $code
     * @return bool
     */
    private function isHardBounce($type, $code): bool
    {
        $hard_bounce = [
            self::SPARKPOST_INVALID_RECIPIENT,
            self::SPARKPOST_NO_RECIPIENT,
            self::SPARKPOST_UNSUBSCRIBE
        ];

        if ($type !== 'bounce') {
            return false;
        }

        return in_array((int)$code, $hard_bounce);
    }
}
