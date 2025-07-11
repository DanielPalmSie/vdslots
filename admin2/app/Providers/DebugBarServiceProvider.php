<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/20/17
 * Time: 5:30 PM
 */

namespace App\Providers;

use App\Extensions\DebugBar\FDebugBar;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DebugBarServiceProvider implements ServiceProviderInterface
{
    protected $app;

    /**
     * Register the DebugBar service.
     *
     * @param Container $app
     * @return void
     **/
    public function register(Container $app)
    {
        $this->app = $app;

        $app['debugbar.path'] = '/phive/admin/debugbar/';
        $app['debugbar.assets.dir'] = '/var/www/admin2/phive_admin/debugbar/';

        if (!isset($app['debugbar'])) {
            $app['debugbar'] = function () {
                return new FDebugBar();
            };
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }
        if ($request->isXmlHttpRequest()) {
            return;
        }

        if ($response->isRedirection()
            || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
            || 'html' !== $request->getRequestFormat()
        ) {
            return;
        }

        $render = $this->app['debugbar']->getJavascriptRenderer($this->app['debugbar.path']);

        ob_start();
        echo $render->renderHead();
        echo $render->render();
        $debug_content = ob_get_contents();
        ob_end_clean();

        $content = $response->getContent();

        if (false === strpos($content, '</body>')) {
            $content .= $debug_content;
        } else {
            $content = str_replace("</body>", $debug_content . '</body>', $content);
        }
        $event->getResponse()->setContent($content);
    }


    /**
     * Boot the DebugBar service.
     *
     * @param Application $app
     * @return void
     **/
    public function boot(Application $app)
    {
        if (!file_exists($app['debugbar.assets.dir'])) {
            dd("You need to create the symlink to the debugbar assets on phive admin.");
        }

        $app['dispatcher']->addListener(KernelEvents::RESPONSE, [$this, 'onKernelResponse'], -1000);
    }
}