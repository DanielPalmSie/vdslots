<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../global_functions.php';

use App\Helpers\Common;
use App\Middleware\SparkpostAuthorization;

$app->on(\Symfony\Component\HttpKernel\KernelEvents::REQUEST, function (\Symfony\Component\HttpKernel\Event\GetResponseEvent $event) use ($app) {

    if (\Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
        return;
    }
    $request = $event->getRequest();

    $sparkpost = new SparkpostAuthorization($app, $request);
    if ($sparkpost->allow()) {
        return;
    }
    $valid_mandrill = env('MANDRILL_AUTH_ENABLE')
                    ? Common::generateSignature(env('MANDRILL_WEBHOOK_KEY'), env('MANDRILL_WEBHOOK_URL'), $_POST) === $request->headers->get("X-Mandrill-Signature")
                    : true;

    if ($request->headers->has("X-Mandrill-Signature") and $valid_mandrill) {
        return;
    }

    if (!empty($app['api.key']) && $request->headers->get('X-BO-KEY') == $app['api.key']) {
        return;
    }
    $not_authenticated_response = new \Symfony\Component\HttpFoundation\Response("No valid credentials", 403);
    $event->setResponse($not_authenticated_response);

}, \Silex\Application::EARLY_EVENT);

/*
 *  Load phive to be able to use lic(), cu() and other phive base functions.
 *
 *  We can't load phive at the top of api.php file because phive's vendor classes interfere with Silex core classes from admin2 vendor folder
 *  (We have incompatible versions of symfony/http-foundation packages in phive and admin2).
 *
 *  But we can load phive after Silex bootstrap processes are finished (and classes from symfony/http-foundation are not used anymore).
 *  So we are loading it before controller code is executed
 */
$app->on(\Symfony\Component\HttpKernel\KernelEvents::CONTROLLER, function (\Symfony\Component\HttpKernel\Event\FilterControllerEvent $event) {
    loadPhive();
});

if (!$app['debug']) {
    $app->error(function (\Exception $e, $code) use ($app) {
        $app['monolog']->addWarning("[BO-API-LOG] Error code: \"$code\", description: \"{$e->getMessage()}\", file: \"{$e->getFile()}\", line: \"{$e->getLine()}\"");
    });
}

$app->mount("/api", new \App\Controllers\Api\ApiMainController());
$app->mount("/api/", new \App\Controllers\Api\EmailController());
$app->mount("/api/", new \App\Controllers\Api\TestController());
$app->mount("/api/", new \App\Controllers\Api\MandrillNotificationsController());
$app->mount("/api/", new \App\Controllers\Api\BeBettorController());
$app->mount("/api/webhook/", new \App\Controllers\Api\WebhookController());

$app->run();

