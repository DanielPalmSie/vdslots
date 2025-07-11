<?php
/**
 * @var Application $app
 */

use App\Controllers\AdminMainController;
use App\Controllers\CommentController;
use App\Controllers\FraudController;
use App\Controllers\PoolX\BetsController as PoolXBetsController;
use App\Controllers\Altenar\BetsController as AltenarBetsController;
use App\Controllers\Bingo\BetsController as BingoBetsAndWinsController;
use App\Controllers\Sportsbook\BetSettlementReportController;
use App\Controllers\Sportsbook\ManualBetSettlementController;
use App\Controllers\SportsbookController;
use App\Controllers\TranslateController;
use App\Controllers\UserComplaintController;
use App\Controllers\UserComplaintResponseController;
use App\Controllers\UserController;
use App\Controllers\UserDailyStatsController;
use App\Controllers\UserProfileController;
use App\Controllers\AccountingController;
use App\Controllers\TransactionsController;
use App\Controllers\LicensingController;
use App\Controllers\MessagingController;
use App\Controllers\PromotionsController;
use App\Controllers\BonusTypesController;
use App\Controllers\CmsController;
use App\Controllers\PaymentsController;
use App\Controllers\BinBlacklistController;
use App\Controllers\WheelOfJackpotsController;
use App\Controllers\TrophiesController;
use App\Controllers\JackpotController;
use App\Controllers\TrophyAwardsController;
use App\Controllers\RaceTemplatesController;
use App\Controllers\LocalizedStringsController;
use App\Controllers\GameController;
use App\Controllers\BonusController;
use App\Controllers\TournamentTemplateController;
use App\Controllers\TournamentController;
use App\Controllers\GamificationController;
use App\Controllers\SettingsController;
use App\Controllers\SettingsConfigController;
use App\Controllers\SettingsPermissionController;
use App\Controllers\SettingsGamesController;
use App\Controllers\SettingsOperatorsController;
use App\Controllers\SettingsTriggersController;
use App\Controllers\RgController;
use App\Controllers\MonitoringController;
use App\Controllers\TriggersController;
use App\Controllers\GameOverrideController;
use App\Models\Group;
use App\Models\User;
use App\Models\UserComment;
use App\Models\NamedSearch;
use App\Models\SMSTemplate;
use App\Models\EmailTemplate;
use App\Models\BonusType;
use App\Models\UserComplaint;
use App\Models\VoucherTemplate;
use App\Models\Trophy;
use App\Models\TrophyAwards;
use App\Models\TournamentTemplate;
use App\Models\Tournament;
use App\Models\RaceTemplate;
use App\Models\Config;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../global_functions.php';

// Simple route model "binding" for User instances
$app['userProvider'] = $app->protect(function ($username, $request) use ($app) {
    // Define these functions here to prevent global usage
    function redirect($url, $permanent = true)
    {
        header('Location: ' . $url, true, $permanent ? 301 : 302);
        exit();
    }

    function redirectUserFail($app, $message = "We couldn't find the user.")
    {
        $app['flash']->add('warning', $message);
        redirect($app['url_generator']->generate('user'), true);
    }

    if ($app['vs.config']['active.sections']['user.profile']) {
        $is_numeric_username = is_numeric($username);

        $user = $is_numeric_username ? User::where('id', $username)->first() : null;

        if ($request->attributes->get('_route') == 'admin.userprofile') {
            // To cover this case: $username = 000; // which is not an id
            if ($is_numeric_username && !$user) {
                $is_numeric_username = false;
            }

            if (!$is_numeric_username && $user = User::where('username', $username)->first()) {
                redirect($app['url_generator']->generate('admin.userprofile', ["user" => $user->id]));
            }
        }

        if (!isset($user)) {
            if ($app['vs.config']['archive.scale.back'] && $is_numeric_username) {
                try {
                    User::unarchive($username);
                    $user = User::where('id', $username)->firstOrFail();
                } catch (Exception $e) {
                    redirectUserFail($app, "User not found, actor tried to unarchived user: {$username}");
                }
            } else {
                redirectUserFail($app);
            }
        }
        /**
         * $url: [0 => "", 1 => "admin2",  2 => "userprofile",  3 => "<user_id>", ...]
         */
        $url = explode('/', $request->getPathInfo());

        if ($url[1] == env('BO_BASE_URL') && $url[2] == 'userprofile' && !$app['debug']) {
            $actor = UserRepository::getCurrentUser();

            ActionRepository::logAction(
                $user->id,
                "visited page {$request->getPathInfo()}",
                'privacy-see-details',
                $actor
            );
        }
        return $user->addToRecentList()->populateSettings();
    } else {
        $app->abort(404, 'Section not available');
    }
});

// Simple route model "binding" for Group instances //todo look wtf is this doing here
$app['groupProvider'] = $app->protect(function ($group_id) use ($app) {
    try {
        return Group::where('group_id', $group_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Group not found');
    }
});

// Simple route model "binding" for UserComment instances //todo look wtf is this doing here
$app['commentProvider'] = $app->protect(function ($comment_id) use ($app) {
    try {
        return UserComment::where('id', $comment_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'User comment not found');
    }
});

$app['namedSearchProvider'] = $app->protect(function ($named_search_id) use ($app) {
    try {
        return NamedSearch::where('id', $named_search_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Named search not found');
    }
});

$app['smsTemplateProvider'] = $app->protect(function ($st_id) use ($app) {
    try {
        return SMSTemplate::where('id', $st_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'SMS template not found');
    }
});

$app['emailTemplateProvider'] = $app->protect(function ($st_id) use ($app) {
    try {
        return EmailTemplate::where('id', $st_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Email template not found');
    }
});

$app['smsTemplateScheduleProvider'] = $app->protect(function ($schedule_id) use ($app) {
    try {
        return SMSTemplateSchedule::where('id', $schedule_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'SMS template schedule not found');
    }
});

$app['bonusTypeProvider'] = $app->protect(function ($bonus_type_id) use ($app) {
    try {
        return BonusType::where('id', $bonus_type_id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Bonus type template not found');
    }
});

$app['voucherTemplateProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return VoucherTemplate::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Voucher template not found');
    }
});

$app['trophyProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return Trophy::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Trophy not found');
    }
});

$app['trophyAwardProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return TrophyAwards::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Trophy Award not found');
    }
});

$app['raceTemplateProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return RaceTemplate::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Race Templates not found');
    }
});

$app['tournamentTemplateProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return TournamentTemplate::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Tournament Template not found');
    }
});

$app['tournamentProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return Tournament::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Tournament not found');
    }
});

$app['bonusTypeProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return BonusType::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Bonus Type not found');
    }
});

$app['configProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return Config::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Config not found');
    }
});

$app['userComplaintProvider'] = $app->protect(function ($id) use ($app) {
    try {
        return UserComplaint::where('id', $id)->firstOrFail();
    } catch (Exception $e) {
        $app->abort(404, 'Complaint not found');
    }
});

// Test route before middleware: logging the current user before the request
// This is not in use now!!! //todo look wtf is this doing here
$userLogger = function (Request $req, Application $app) {
    $app['monolog']->addInfo("Request by user: " . cu()->getAttr('username'));
};

if (!$app['debug']) {
    $app->error(function (\Exception $e, Request $request, $code) use ($app) {
        $cu = cu()->getAttr('username');
        if ($code != 403) {
            $app['monolog']->addWarning("[BO-LOG] Actor: \"$cu\", error code: \"$code\", description: \"{$e->getMessage()}\", file: \"{$e->getFile()}\", line: \"{$e->getLine()}\"");
        }
        if (in_array($code, [403, 404, 500])) {
            return $app['blade']->view()->make("admin.error.$code", compact('app', 'code'))->render();
        } elseif ($code == 408) {
            $message = $e->getMessage();
            return $app['blade']->view()->make("admin.error.common", compact('app', 'code', 'message'))->render();
        } elseif ($code == 405) {
            return new Response('HTTP method not supported.', 405, array('X-Status-Code' => 405));
        } else {
            return $app['blade']->view()->make("admin.error.500", compact('app', 'code'))->render();
        }
    });
}

$app->mount("/$root_url/", new AdminMainController());

if ($app['vs.config']['active.sections']['user.profile']) {
    $app->mount("/$root_url/user/", new UserController());
    $app->mount("/$root_url/userprofile/", new UserProfileController())->before($userLogger);
    $app->mount("/$root_url/userprofile/", new UserComplaintController());
    $app->mount("/$root_url/userprofile/", new UserComplaintResponseController());
    $app->mount("/$root_url/comment/", new CommentController());
    $app->mount("/$root_url/userdailystats/", new UserDailyStatsController());
    $app->mount("/$root_url/half-year/", new \App\Controllers\HalfYearReportController());
    $app->mount("/$root_url/poolx-bets/", new PoolXBetsController($app, $app['poolx.bet_service']));
    $app->mount("/$root_url/altenar-bets/", new AltenarBetsController($app, $app['altenar.bet_service']));
    $app->mount("/$root_url/bingo-bets/", new BingoBetsAndWinsController($app, $app['bingo.bet_service']));
}

if ($app['vs.config']['active.sections']['fraud']) {
    $app->mount("/$root_url/fraud/", new FraudController());
}
if ($app['vs.config']['active.sections']['accounting']) {
    $app->mount("/$root_url/accounting/", new AccountingController());
}
if ($app['vs.config']['active.sections']['licensing']) {
    $app->mount("/$root_url/licensing/", new LicensingController());
}
if ($app['vs.config']['active.sections']['messaging']) {
    $app->mount("/$root_url/messaging/", new MessagingController());
    $app->mount("/$root_url/messaging/", new \App\Controllers\Messaging\PromotionsTemplateController());
    $app->mount("/$root_url/messaging/", new \App\Controllers\Messaging\SmsController());
    $app->mount("/$root_url/messaging/", new \App\Controllers\Messaging\EmailController());
    $app->mount("/$root_url/messaging/", new \App\Controllers\Messaging\CampaignController());
}
if ($app['vs.config']['active.sections']['cms']) {
    $app->mount("/$root_url/cms/", new CmsController());
}
if ($app['vs.config']['active.sections']['promotions']) {
    $app->mount("/$root_url/promotions/", new PromotionsController());
}
if ($app['vs.config']['active.sections']['payments']) {
    $app->mount("/$root_url/payments/", new PaymentsController());
    $app->mount("/$root_url/payments/bin-blacklist/", new BinBlacklistController());
}

if ($app['vs.config']['active.sections']['gamification']) {
    $app->mount("/$root_url/gamification/", new GamificationController());
    $app->mount("/$root_url/gamification/trophies/", new TrophiesController());
    $app->mount("/$root_url/gamification/localizedstrings/", new LocalizedStringsController());
    $app->mount("/$root_url/gamification/trophyawards/", new TrophyAwardsController());
    $app->mount("/$root_url/gamification/racetemplates/", new RaceTemplatesController());
    $app->mount("/$root_url/gamification/tournamenttemplates/", new TournamentTemplateController());
    $app->mount("/$root_url/gamification/tournaments/", new TournamentController());
    $app->mount("/$root_url/gamification/bonustypes/", new BonusTypesController());
    $app->mount("/$root_url/game/", new GameController());
    $app->mount("/$root_url/bonus/", new BonusController());
    $app->mount("/$root_url/gamification/wheelofjackpots/", new WheelOfJackpotsController());
    $app->mount("/$root_url/gamification/jackpot/", new JackpotController());
}

if ($app['vs.config']['active.sections']['settings']) {
    $app->mount("/$root_url/settings/", new SettingsController());
    $app->mount("/$root_url/settings/config/", new SettingsConfigController());
    $app->mount("/$root_url/settings/permissions/", new SettingsPermissionController());
    $app->mount("/$root_url/settings/games/", new SettingsGamesController());
    $app->mount("/$root_url/settings/triggers/", new SettingsTriggersController());
    $app->mount("/$root_url/settings/", new \App\Controllers\RiskProfileRatingController());
}
if ($app['vs.config']['active.sections']['rg']) {
    $app->mount("/$root_url/rg/", new RgController());
}
if ($app['vs.config']['active.sections']['monitoring']) {
    $app->mount("/$root_url/monitoring/", new MonitoringController());
}
if ($app['vs.config']['active.sections']['triggers']) {
    $app->mount("/$root_url/triggers/", new TriggersController());
}
if ($app['vs.config']['active.sections']['games']) {
    $app->mount("/$root_url/games/operators/", new SettingsOperatorsController());
    $app->mount("/$root_url/games/", new GameController());
    $app->mount("/$root_url/games/games-override/", new GameOverrideController());
}
if ($app['vs.config']['active.sections']['sportsbook']) {
    $app->mount("/$root_url/sportsbook/", new SportsbookController());
    $app->mount("/$root_url/sportsbook/", new BetSettlementReportController());
    $app->mount("/$root_url/sportsbook/", new ManualBetSettlementController());
}
if ($app['vs.config']['active.sections']['translate']) {
    $app->mount("/$root_url/translate/", new TranslateController());
}

$app->mount("/$root_url/export/", new \App\Controllers\ExportController());

$app->mount("/$root_url/transactions/", new TransactionsController());

try {
    $app->run();
} catch (HttpException $e) {
    var_dump($e->getCode());
    die();
}
