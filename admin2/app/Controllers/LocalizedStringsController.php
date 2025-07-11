<?php

namespace App\Controllers;

use App\Models\LocalizedStrings;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class LocalizedStringsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/update/', 'App\Controllers\LocalizedStringsController::upsertLocalizedStrings')
            ->bind('localizedstrings.update')
            ->before(function () use ($app) {
                if (!p('localizedstrings.update')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function upsertLocalizedStrings(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {

            foreach ($request->request->all() as $alias => $languages) {
                foreach ($languages as $lang => $text) {
                    $text = trim($text);
                    if (strlen($text) > 0) {
                        try {
                            LocalizedStrings::updateOrCreate(['alias' => 'trophyname.'.$alias, 'language' => $lang], ['value' => $text]);
                            phive('Localizer')->memSet('trophyname.'.$alias, $lang, $text);
                        } catch (\Exception $e) {
                            return $app->json(['success' => false, 'errormsg' => 'Failure updating/inserting LocalizedStrings: $alias $lang : '.$e->getMessage()]);
                        }
                    } else {
                        LocalizedStrings::where('alias', '=', 'trophyname.'.$alias)->where('language', '=', $lang)->delete();
                    }
                }
            }

            return $app->json(['success' => true]);
        }
    }

}
