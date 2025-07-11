<?php

namespace App\Controllers;

use App\Classes\BeBettor;
use App\Classes\Settings;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper;
use App\Models\Action;
use App\Models\Config;
use App\Models\Group;
use App\Models\IpLog;
use App\Models\TriggersLog;
use App\Models\User;
use App\Models\UserComment;
use App\Models\UserSetting;
use App\Repositories\ActionRepository;
use App\Repositories\BlockRepository;
use App\Repositories\LimitsRepository;
use App\Repositories\UserCommentRepository;
use App\Repositories\UserDailyStatsRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserSessionRepository;
use App\Repositories\UserSettingsRepository;
use App\Repositories\LiabilityRepository;
use App\Validator\Requests\UpdateCriminalRecordRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use JsonException;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use UserStatus;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\NegativeBalanceSinceDepositFlag;
use Videoslots\FraudDetection\FraudFlags\TooManyRollbacksFlag;
use Videoslots\FraudDetection\FraudFlags\TotalWithdrawalAmountLimitReachedFlag;
use Videoslots\FraudDetection\RevokeEvent;
use Videoslots\HistoryMessages\InterventionHistoryMessage;


class UserProfileController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        // Routing
        $factory->get('/{user}/debug/', 'App\Controllers\UserProfileController::debug')
            ->convert('user', $app['userProvider'])
            ->bind('admin.debug');

        $factory->get('/{user}/', 'App\Controllers\UserProfileController::getUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile')
            ->before(function () use ($app) {
                if (!p('users.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/', 'App\Controllers\UserProfileController::getUserProfile')
            ->bind('admin.userprofile.direct')
            ->before(function () use ($app) {
                if (!p('users.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/block/', 'App\Controllers\UserProfileController::blockUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-block')
            ->before(function () use ($app) {
                if (!(p('user.block') || !p('user.super.block'))) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/unblock/', 'App\Controllers\UserProfileController::unBlockUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-unblock')
            ->before(function () use ($app) {
                if (!(p('user.block') || p('user.super.block'))) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/unblock-extend-one-day/', 'App\Controllers\UserProfileController::extendBlockingOneDay')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-block-extend-one-day')
            ->before(function () use ($app) {
                if (!(p('user.block') || p('user.super.block'))) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/block-extend/', 'App\Controllers\UserProfileController::extendBlocking')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-block-extend')
            ->before(function () use ($app) {
                if (!(p('user.block') || !p('user.super.block'))) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/superblock/', 'App\Controllers\UserProfileController::superBlockUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-superblock')
            ->before(function () use ($app) {
                if (!p('user.super.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/lift-superblock/', 'App\Controllers\UserProfileController::liftSuperBlockUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-lift-superblock')
            ->before(function () use ($app) {
                if (!p('user.super.unlock')) {
                    $app->abort(403);
                }
            });


        $factory->get('/{user}/unrestrict/', 'App\Controllers\UserProfileController::unrestrict')
            ->convert('user', $app['userProvider'])
            ->bind('admin.unrestrict')
            ->before(function () use ($app) {
                if (!p('user.restrict')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/restrict/', 'App\Controllers\UserProfileController::restrict')
            ->convert('user', $app['userProvider'])
            ->bind('admin.restrict')
            ->before(function () use ($app) {
                if (!p('user.restrict')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/password-change-on-login/', 'App\Controllers\UserProfileController::togglePasswordChangeOnLogin')
            ->convert('user', $app['userProvider'])
            ->bind('admin.password-change-on-login')
            ->before(function () use ($app) {
                if (!p('user.force-password-change-on-login')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/account-closure/', 'App\Controllers\UserProfileController::closeAccount')
            ->convert('user', $app['userProvider'])
            ->bind('admin.account-closure');

        $factory->get('/{user}/toggle-au-bypass/', 'App\Controllers\UserProfileController::toggleAUBypass')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-toggle-au-bypass')
            ->before(function () use ($app) {
                if (!p('user.bypass-au-playcheck.flag')) {
                    $app->abort(403);
                }
            });

        // AML stuff start
        $toggle_flag = function ($flag) use ($factory, $app) {
            $factory->get("/{user}/toggle/{flag}/{action}/", 'App\Controllers\UserProfileController::toggleFlag')
                ->convert('user', $app['userProvider'])
                ->bind('admin.user-toggle-flag')
                ->before(function () use ($app) {
                    if (!p('fraud.section.remove.flag.manual')) {
                        $app->abort(403);
                    }
                });
        };

        $toggle_flag('sar-flag');
        $toggle_flag('pepsl-flag');
        $toggle_flag('amlmonitor-flag');
        $toggle_flag('agemonitor-flag');
        // AML stuff end

        $factory->get('/{user}/allowtodeposit/', 'App\Controllers\UserProfileController::allowToDeposit')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-allow-to-deposit')
            ->before(function () use ($app) {
                if (!p('user.deposit.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/disallowtodeposit/', 'App\Controllers\UserProfileController::disAllowToDeposit')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-disallow-to-deposit')
            ->before(function () use ($app) {
                if (!p('user.deposit.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/allowtowithdraw/', 'App\Controllers\UserProfileController::allowToWithdraw')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-allow-to-withdraw')
            ->before(function () use ($app) {
                if (!p('user.withdraw.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/disallowtowithdraw/', 'App\Controllers\UserProfileController::disAllowToWithdraw')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-disallow-to-withdraw')
            ->before(function () use ($app) {
                if (!p('user.withdraw.block')) {
                    $app->abort(403);
                }
            });

        $factory->match('/{user}/fraud-monitoring/', 'App\Controllers\MonitoringController::fraudMonitoring')
            ->convert('user', $app['userProvider'])
            ->bind('admin.fraud-monitoring')
            ->before(function () use ($app) {
                if (!p('fraud.section.fraud-monitoring') && !p('user.fraud-monitoring')) {
                    $app->abort(403);
                } else {
                    $user = User::findByUsername($app['request_stack']->getMasterRequest()->get('user'));
                    $actor = UserRepository::getCurrentUser();
                    ActionRepository::logAction($user, "loaded Fraud Monitoring for the username: {$user->username}", 'fraud-monitoring', true, $actor);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/fraud-aml-monitoring/', 'App\Controllers\MonitoringController::fraudAmlMonitoring')
            ->convert('user', $app['userProvider'])
            ->bind('admin.fraud-aml-monitoring')
            ->before(function () use ($app) {
                if (!p('fraud.section.fraud-aml-monitoring') && !p('user.fraud-aml-monitoring')) {
                    $app->abort(403);
                } else {
                    $user = User::findByUsername($app['request_stack']->getMasterRequest()->get('user'));
                    $actor = UserRepository::getCurrentUser();
                    ActionRepository::logAction($user, "loaded AML Monitoring for the username: {$user->username}", 'aml-monitoring', true, $actor);
                }
            })->method('GET|POST');

        $factory->match('/{user}/responsible-gaming-monitoring/', 'App\Controllers\MonitoringController::rgMonitoring')
            ->convert('user', $app['userProvider'])
            ->bind('admin.responsible-gaming-monitoring')
            ->before(function () use ($app) {
                if (!p('fraud.section.responsible-gaming-monitoring') && !p('user.responsible-gaming-monitoring')) {
                    $app->abort(403);
                } else {
                    $user = User::findByUsername($app['request_stack']->getMasterRequest()->get('user'));
                    $actor = UserRepository::getCurrentUser();
                    ActionRepository::logAction($user, "loaded Responsible Gaming Monitoring for the username: {$user->username}", 'rg-monitoring', true, $actor);
                }
            })->method('GET|POST');


        $factory->match('/{user}/grsScoreReport/', 'App\Controllers\RiskProfileRatingController::grsScoreReport')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user.grsScore')
            ->before(function () use ($app) {
                if (!p('user.section.grsScore') ) {
                    $app->abort(403);
                } else {
                    $user = User::findByUsername($app['request_stack']->getMasterRequest()->get('user'));
                    $actor = UserRepository::getCurrentUser();
                    ActionRepository::logAction($user, "loaded Global Score for the username: {$user->username}", 'grs-monitoring', true, $actor);
                }
            })->method('GET|POST');

        $factory->get('/{user}/allowtoplay/', 'App\Controllers\UserProfileController::allowToPlay')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-allow-to-play')
            ->before(function () use ($app) {
                if (!p('user.play.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/disallowtoplay/', 'App\Controllers\UserProfileController::disAllowToPlay')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-disallow-to-play')
            ->before(function () use ($app) {
                if (!p('user.play.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/verifyemail/', 'App\Controllers\UserProfileController::verifyEmail')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-verify-email');

        $factory->get('/{user}/unverifyemail/', 'App\Controllers\UserProfileController::unVerifyEmail')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-unverify-email');

        $factory->get('/{user}/verifyphone/', 'App\Controllers\UserProfileController::verifyPhone')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-verify-phone');

        $factory->get('/{user}/unverifyphone/', 'App\Controllers\UserProfileController::unVerifyPhone')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-unverify-phone');

        $factory->get('/{user}/verifyprofile/', 'App\Controllers\UserProfileController::verifyUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-verify');

        $factory->get('/{user}/unverifyprofile/', 'App\Controllers\UserProfileController::unVerifyUserProfile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-unverify');

        $factory->get('/{user}/set-phoned-date-to-now/', 'App\Controllers\UserProfileController::setPhonedDateToNow')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-set-phoned-date-to-now');

        $factory->get('/{user}/delete-phoned-date/', 'App\Controllers\UserProfileController::deletePhonedDate')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-delete-phoned-date');

        $factory->get('/{user}/clear-ip-log/', 'App\Controllers\UserProfileController::clearIPLog')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-clear-ip-log');

        $factory->get('/{user}/show-bank/', 'App\Controllers\UserProfileController::showBank')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-show-bank');

        $factory->get('/{user}/show-euteller/', 'App\Controllers\UserProfileController::showEuteller')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-show-euteller');

        $factory->get('/{user}/show-entercash/', 'App\Controllers\UserProfileController::showEnterCash')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-show-entercash');

        $factory->get('/{user}/update-fifo-date/', 'App\Controllers\UserProfileController::updateFifoDate')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-update-fifo-date');

        $factory->get('/{user}/active-prevent-ccard-flag/', 'App\Controllers\UserProfileController::activePreventCCardFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-active-prevent-ccard-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.permanently')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/prevent-liability-flag/', 'App\Controllers\UserProfileController::preventLiabilityFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-prevent-liability-flag')
            ->before(function () use ($app) {
                if (!p('user.liability.prevent.flag')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-prevent-ccard-flag/', 'App\Controllers\UserProfileController::deactivePreventCreditCardFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-prevent-ccard-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.permanently')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-ccard-fraud-flag/', 'App\Controllers\UserProfileController::deactiveCreditCardFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-ccard-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-majority-fraud-flag/', 'App\Controllers\UserProfileController::deactiveMajorityFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-majority-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.majority')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/allow-to-chat/', 'App\Controllers\UserProfileController::allowToChat')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-allow-to-chat')
            ->before(function () use ($app) {
                if (!p('user.chat.allow')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/block-to-chat-permanent/', 'App\Controllers\UserProfileController::blockToChatPermanent')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-block-to-chat-permanent')
            ->before(function () use ($app) {
                if (!p('user.chat.block.permanent')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/block-to-chat-7-days/', 'App\Controllers\UserProfileController::blockToChatDays')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-block-to-chat-days')
            ->before(function () use ($app) {
                if (!p('user.chat.block')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-bonus-fraud-flag/', 'App\Controllers\UserProfileController::deactiveBonusFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-bonus-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/active-manual-fraud-flag/', 'App\Controllers\UserProfileController::activateManualFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-active-manual-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.manual')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/active-too-many-rollbacks-fraud-flag/', 'App\Controllers\UserProfileController::activateTooManyRollbacksFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-active-too-many-rollbacks-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.too-many-rollbacks')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/active-total-withdrawal-amount-limit-reached-fraud-flag/', 'App\Controllers\UserProfileController::activateTotalWithdrawalAmountLimitReachedFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-active-total-withdrawal-amount-limit-reached-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.total-withdrawal-amount-limit-reached')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/active-suspicious-email-fraud-flag/', 'App\Controllers\UserProfileController::activateSuspiciousEmailFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-active-suspicious-email-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.suspicious-email')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/active-negative-balance-since-deposit-fraud-flag/', 'App\Controllers\UserProfileController::activateNegativeBalanceSinceDepositFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-active-negative-balance-since-deposit-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.negative-balance-since-deposit')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-manual-fraud-flag/', 'App\Controllers\UserProfileController::deactivateManualFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-manual-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.manual')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-too-many-rollbacks-fraud-flag/', 'App\Controllers\UserProfileController::deactivateTooManyRollbacksFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-too-many-rollbacks-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.too-many-rollbacks')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-total-withdrawal-amount-limit-reached-fraud-flag/', 'App\Controllers\UserProfileController::deactivateTotalWithdrawalAmountLimitReachedFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-total-withdrawal-amount-limit-reached-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.total-withdrawal-amount-limit-reached')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-suspicious-email-fraud-flag/', 'App\Controllers\UserProfileController::deactivateSuspiciousEmailFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-suspicious-email-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.suspicious-email')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-negative-balance-since-deposit-fraud-flag/', 'App\Controllers\UserProfileController::deactivateNegativeBalanceSinceDepositFraudFlag')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-negative-balance-since-deposit-fraud-flag')
            ->before(function () use ($app) {
                if (!p('fraud.section.remove.flag.negative-balance-since-deposit')) {
                    $app->abort(403);
                }
            });

        // Edit user data
        $factory->get('/{user}/edit/', 'App\Controllers\UserProfileController::getEditUserForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-edit');

        $factory->post('/{user}/edit-basic/', 'App\Controllers\UserProfileController::postEditUserForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-basic-update')
            ->before(function () use ($app) {
                if (!p('change.contact.info')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/edit-settings/', 'App\Controllers\UserProfileController::postEditUserSettingsForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-settings-update')
            ->before(function () use ($app) {
                if (!p('change.contact.info')) {
                    $app->abort(403);
                }
            });
        $factory->post('/{user}/edit-privacy-settings/', 'App\Controllers\UserProfileController::postEditUserPrivacySettingsForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-privacy-settings-update')
            ->before(function () use ($app) {
                if (!p('user.edit.privacy.settings')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/edit-payment-update/', 'App\Controllers\UserProfileController::postEditUserSettingsForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-payment-update')
            ->before(function () use ($app) {
                if (!p('user.inout.defaults')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/edit-deposits-methods-update/', 'App\Controllers\UserProfileController::postEditUserSettingsForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-deposits-methods-update')
            ->before(function () use ($app) {
                if (!p('user.disable.deposit.methods')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/edit-other-settings-update/', 'App\Controllers\UserProfileController::postEditUserSettingsForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-other-settings-update')
            ->before(function () use ($app) {
                if (!p('user.casino.settings')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/edit-forums-update/', 'App\Controllers\UserProfileController::postEditUserSettingsForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.userprofile-forums-update')
            ->before(function () use ($app) {
                if (!p('edit.forums')) {
                    $app->abort(403);
                }
            });

        // handling allowed countries
        $factory->post('/{user}/allowedcountries/', 'App\Controllers\UserProfileController::postAllowedCountriesForm')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-allowed-countries')
            ->before(function () use ($app) {
                if (!p('login.country.manage')) {
                    $app->abort(403);
                }
            });
        $factory->post('/{user}/deleteallowedcountry/', 'App\Controllers\UserProfileController::deleteAllowedCountry')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-delete-allowed-country')
            ->before(function () use ($app) {
                if (!p('login.country.manage')) {
                    $app->abort(403);
                }
            });

        // user actions
        $factory->match('/{user}/actions/', 'App\Controllers\ActionsController::userActions')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-actions')
            ->before(function (Request $request) use ($app) {
                if (!p('view.account.actions')) {
                    $app->abort(403);
                }
                if (!empty($request->get('by-admin')) && !p('view.account.admin.actions')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        // show user permissions page
        $factory->get('/{user}/permissions/', 'App\Controllers\UserProfileController::userPermissions')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-permissions')
            ->before(function () use ($app) {
                if (!p('view.user.permissions') && !p('permission.edit.%') && !p('permission.view.%')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/add-group/', 'App\Controllers\UserProfileController::addUserToGroup')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-add-group')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group');
                if (!p('edit.permissions') && !p('permission.edit.' . $group_id)) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-group/', 'App\Controllers\UserProfileController::removeUserFromGroup')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-group')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group');
                if (!p('edit.permissions') && !p('permission.edit.' . $group_id)) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/add-permission/', 'App\Controllers\UserProfileController::addPermissionToUser')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-add-permission')
            ->before(function () use ($app) {
                if (!p('edit.permissions') && !p('view.permissions.indiv')) {
                    $app->abort(403);
                }
            });

        $factory->post('/group/{group}/permissions/', 'App\Controllers\UserProfileController::listGroupPermissions')
            ->convert('group', $app['groupProvider'])
            ->assert('group', '\d+')
            ->bind('admin.user-list-group-permission')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group');
                if (!p('view.user.group.permissions') && !p('permission.edit.' . $group_id) && !p('permission.view.' . $group_id)) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/remove-permission/', 'App\Controllers\UserProfileController::removePermissionFromUser')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-permission')
            ->before(function () use ($app) {
                if (!p('edit.permissions') && !p('view.permissions.indiv')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/account/test/', 'App\Controllers\UserProfileController::testAccount')
            ->convert('user', $app['userProvider'])
            ->bind('admin.account.test')
            ->before(function () use ($app) {
                if (!p('user.account.test')) {
                    $app->abort(403);
                }
            });


        /**
         * Other controllers
         */

        //Notifications
        $factory->get('/{user}/notifications/', 'App\Controllers\NotificationController::listUserNotificationHistory')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-notifications')
            ->before(function () use ($app) {
                if (!p('view.account.notification-history')) {
                    $app->abort(403);
                }
            });

        // Handling user transactions in TransactionsController
        $factory->get('/{user}/transactions/', 'App\Controllers\TransactionsController::listUserDeposits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            });

        $factory->match('/{user}/transactions/deposit/', 'App\Controllers\TransactionsController::listUserDeposits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions-deposit')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/transactions/failed-deposit/', 'App\Controllers\TransactionsController::listUserFailedDeposits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions-failed-deposit')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/{user}/transactions/manual/', 'App\Controllers\TransactionsController::listUserManualDeposits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions-manual')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/{user}/transactions/withdrawal/', 'App\Controllers\TransactionsController::listUserWithdrawals')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions-withdrawal')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/{user}/transactions/other/', 'App\Controllers\TransactionsController::listUserOtherTransactions')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions-other')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/{user}/transactions/closed-loop/', 'App\Controllers\TransactionsController::listUserClosedLoops')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transactions-closed-loop')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->get('/{user}/transactions/cancel-withdrawal/', 'App\Controllers\TransactionsController::cancelWithdrawal')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-cancel-withdrawal')
            ->before(function () use ($app) {
                if (!p('cancel.approved.withdrawal')) {
                    $app->abort(403);
                }
            });

        //todo permissions
        $factory->get('/{user}/transactions/cancel-pending-withdrawal/', 'App\Controllers\TransactionsController::cancelPendingWithdrawal')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-cancel-pending-withdrawal');

        // routes for trophies
        $factory->get('/{user}/trophies/', 'App\Controllers\TrophiesController::listUserTrophies')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-trophies')
            ->before(function () use ($app) {
                if (!p('view.account.trophies')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/trophies/', 'App\Controllers\TrophiesController::addTrophyToUser')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-add-trophy')
            ->before(function () use ($app) {
                if (!p('give.trophies')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/get_trophies_for_category/{category}/', 'App\Controllers\TrophiesController::getTrophiesForCategory')
            ->convert('user', $app['userProvider'])
            ->value('category', 'activity')
            ->bind('admin.user-trophy-list');
        //todo check and add permissions

        // ID3gloabl response page
        $factory->get('/{user}/id3global-result/', 'App\Controllers\FraudController::showId3Data')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-id3global')
            ->before(function () use ($app) {
                if (!p('user.id3global-result')) {
                    $app->abort(403);
                }
            });

        // user Battles/TournamentEntries
        $factory->get('/{user}/battles/', 'App\Controllers\BattlesController::listUserBattles')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-battles')
            ->before(function () use ($app) {
                if (!p('user.battles')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/battle-result/', 'App\Controllers\BattlesController::showBattleResults')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-battle-result')
            ->before(function () use ($app) {
                if (!p('user.battles')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/battle-bets-and-wins/', 'App\Controllers\BattlesController::showBattleBetsAndWins')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-battle-bets-and-wins')
            ->before(function () use ($app) {
                if (!p('user.battles')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/battle-betsandwins/list-all/', 'App\Controllers\BetsAndWinsController::listAll')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-battle-betsandwins-all')
            ->before(function () use ($app) {
                if (!p('user.battles.betswins')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/user-sessions/', 'App\Controllers\UserSessionsController::index')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-sessions')
            ->before(function () use ($app) {
                if (!p('view.account.sessions')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/user-sessions/historical/', 'App\Controllers\UserSessionsController::listHistorical')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-sessions-historical')
            ->before(function () use ($app) {
                if (!p('view.account.user-sessions')) {
                    $app->abort(403);
                }
            });


        // user Game Sessions

        $factory->get('/{user}/game-sessions/historical/', 'App\Controllers\GameSessionsController::listHistorical')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-game-sessions-historical')
            ->before(function () use ($app) {
                if (!p('view.account.game-sessions')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/game-sessions/inprogress/', 'App\Controllers\GameSessionsController::listInProgress')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-game-sessions-inprogress')
            ->before(function () use ($app) {
                if (!p('view.account.game-sessions')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/game-sessions/logged/', 'App\Controllers\GameSessionsController::listLogged')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-game-sessions-logged')
            ->before(function () use ($app) {
                if (!p('view.account.game-sessions')) {
                    $app->abort(403);
                }
            });

        // user Bets and Wins

        $factory->get('/{user}/bets-wins/', 'App\Controllers\BetsAndWinsController::index')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        $factory->match('/{user}/xp-history/', 'App\Controllers\BetsAndWinsController::xpProgress')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-xp-history')
            ->before(function () use ($app) {
                if (!p('view.account.xp-history')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/{user}/bets-wins/list-bets/', 'App\Controllers\BetsAndWinsController::listBets')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins-bets')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bets-wins/list-wins/', 'App\Controllers\BetsAndWinsController::listWins')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins-wins')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bets-wins/list-transactions/', 'App\Controllers\BetsAndWinsController::listTransactions')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins-transactions')
            ->before(function () use ($app) {
                if (!p('view.account.account-history')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bets-wins/list-all/', 'App\Controllers\BetsAndWinsController::listAll')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins-all')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bets-wins/sportsbook-details/{bet_id}/', 'App\Controllers\BetsAndWinsController::getSportsbookBetDetails')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins-sportsbook')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bets-wins/sportsbook-settlement-details/{betId}/',
            'App\Controllers\BetsAndWinsController::getSportsbookSettlementBetDetails')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-betsandwins-sportsbook-settle-details')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        // user Game History
        $factory->match('/{user}/game-history/', 'App\Controllers\BetsAndWinsController::gameHistory')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-game-history')
            ->before(function () use ($app) {
                if (!p('view.account.game-history')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        // user Game Statistics
        $factory->get('/{user}/game-statistics/', 'App\Controllers\BetsAndWinsController::gameStatistics')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-game-statistics')
            ->before(function () use ($app) {
                if (!p('view.account.game-info')) {
                    $app->abort(403);
                }
            });

        // Casino CashBack
        $factory->match('/{user}/casino-weekend-booster/', 'App\Controllers\BetsAndWinsController::casinoCashBack')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-casino-cashback')
            ->before(function () use ($app) {
                if (!p('view.account.cashbacks')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        //Wheel of jackpots spins
        $factory->match('/{user}/wheel-of-jackpot-history/', 'App\Controllers\WheelOfJackpotsController::wheelHistory')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-wheel-of-jackpot-history')
            ->before(function () use ($app) {
                if (!p('view.account.wheel-of-jackpot-history')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        // Casino Races
        $factory->get('/{user}/casino-races/', 'App\Controllers\BetsAndWinsController::casinoRaces')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-casino-races')
            ->before(function () use ($app) {
                if (!p('view.account.casino-races')) {
                    $app->abort(403);
                }
            });

        // Gaming Limits
        $factory->get('/{user}/gaming-limits/', 'App\Controllers\LimitsController::gamingLimits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-gaming-limits')
            ->before(function () use ($app) {
                if (!p('view.account.limits')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/in-out-limits/', 'App\Controllers\LimitsController::inOutLimits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-inout-limits')
            ->before(function () use ($app) {
                if (!p('view.account.limits.in.out')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/block-management/', 'App\Controllers\LimitsController::blockManagement')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-block-management')
            ->before(function () use ($app) {
                if (!p('view.account.limits.block')) {
                    $app->abort(403);
                }
            });

        /* In out payment methods limits */
        $factory->post('/{user}/set-inout-limits/', 'App\Controllers\LimitsController::setInOutLimits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-set-inout-limits')
            ->before(function () use ($app) {
                if (!p('edit.account.limits.in.out')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/remove-inout-limits/', 'App\Controllers\LimitsController::removeInOutLimits')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-remove-inout-limits')
            ->before(function () use ($app) {
                if (!p('remove.account.limits.in.out')) {
                    $app->abort(403);
                }
            });

        /* RG limits */

        $factory->match('/{user}/edit-gaming-limits/', 'App\Controllers\LimitsController::editGamingLimit')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-edit-gaming-limits')
            ->before(function () use ($app) {
                if (!(p('edit.gaminglimits') || p('edit.account.limits.block') || p('view.gaminglimits'))) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        // User bonuses
        $factory->get('/{user}/bonuses/', 'App\Controllers\TrophiesController::listNotActivatedRewards')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-bonuses')
            ->before(function () use ($app) {
                if (!p('view.account.bonuses')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bonuses/rewards/', 'App\Controllers\BonusController::listRewards')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-bonuses-rewards')
            ->before(function () use ($app) {
                if (!p('view.account.bonuses')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bonuses/transactions/', 'App\Controllers\BonusController::listRewardsTransactions')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-bonuses-transactions')
            ->before(function () use ($app) {
                if (!p('view.account.bonuses')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/bonuses/rewards-history/', 'App\Controllers\TrophiesController::listRewardHistory')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-reward-history')
            ->before(function () use ($app) {
                if (!p('view.account.reward-history')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/addbonus/', 'App\Controllers\BonusController::addBonus')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-bonuses-add-bonus')
            ->before(function () use ($app) {
                if (!p('add.bonus')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/addbonus/', 'App\Controllers\BonusController::addBonus')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-bonuses-add-bonus-post')
            ->before(function () use ($app) {
                if (!p('add.bonus')) {
                    $app->abort(403);
                }
            });

        $factory->match('/{user}/addreward/', 'App\Controllers\TrophiesController::addReward')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-bonuses-add-reward')
            ->before(function () use ($app) {
                if (!p('give.reward')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/{user}/deletebonusentry/', 'App\Controllers\BonusController::deleteBonusEntry')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-delete-bonus-entry')
            ->before(function () use ($app) {
                if (!p('account.removebonus')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/deleteawardentry/', 'App\Controllers\TrophiesController::deleteAwardEntry')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-delete-award-entry')
            ->before(function () use ($app) {
                if (!p('account.removebonus')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/reactivatebonusentry/', 'App\Controllers\BonusController::reActivateBonusEntry')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-reactivate-bonus-entry')
            ->before(function () use ($app) {
                if (!p('reactivate.bonus')) {
                    $app->abort(403);
                }
            });

        $factory->match('/{user}/vouchers/', 'App\Controllers\BonusController::vouchers')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-vouchers')
            ->before(function () use ($app) {
                if (!p('view.account.vouchers')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/adddeposit/', 'App\Controllers\TransactionsController::addDeposit')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-add-deposit')
            ->before(function () use ($app) {
                if (!p('user.add.deposit')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/adddeposit/verify/', 'App\Controllers\TransactionsController::addDepositVerify')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-add-deposit-verify')
            ->before(function () use ($app) {
                if (!p('user.add.deposit')) {
                    $app->abort(403);
                }
            })
            ->method('POST');

        $factory->match('/{user}/create-withdrawal/', 'App\Controllers\TransactionsController::createWithdrawal')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-insert-withdrawal')
            ->before(function () use ($app) {
                if (!p('user.create.withdrawal')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/transfercash/verify/', 'App\Controllers\TransactionsController::transferCashVerify')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transfer-cash-verify')
            ->before(function () use ($app) {
                if (!p('user.transfer.cash')) {
                    $app->abort(403);
                }
            })
            ->method('POST');

        $factory->match('/{user}/transfercash/', 'App\Controllers\TransactionsController::transferCash')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-transfer-cash')
            ->before(function () use ($app) {
                if (!p('user.transfer.cash')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/liability/', 'App\Controllers\AccountingController::playerLiability')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-liability')
            ->before(function () use ($app) {
                if (!p('user.liability')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/liability-adjust/', 'App\Controllers\AccountingController::playerLiabilityAdjust')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-liability-adjust')
            ->before(function () use ($app) {
                if (!p(LiabilityRepository::PERMISSION_LIABILITY_ADJUST)) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/risk-score/', 'App\Controllers\RgController::minFraud')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-risk-score')
            ->before(function () use ($app) {
                if (!p('user.risk-score')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');
        $factory->post('/{user}/getgamesajax/', 'App\Controllers\UserProfileController::getGamesAjax')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-get-games-data-ajax');

        $factory->post('/{user}/getfinancialajax/', 'App\Controllers\UserProfileController::getFinancialAjax')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-get-financial-data-ajax');

        $factory->post('/{user}/update-follow-up/', 'App\Controllers\UserProfileController::updateFollowUp')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-update-follow-up')
            ->before(function () use ($app) {
                if (!p('user.edit.follow.up')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/update-criminal-record/', 'App\Controllers\UserProfileController::updateCriminalRecord')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-update-criminal-record')
            ->before(function (Request $request) use ($app) {

                if (!p('fraud.section.fraud-aml-monitoring') && !p('user.fraud-aml-monitoring')) {
                    $app->abort(403);
                }
                (new UpdateCriminalRecordRequest())->validate();
            });

        // Documents
        $factory->get('/{user}/documents/', 'App\Controllers\DocumentController::viewUserDocuments')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-documents')
            ->before(function () use ($app) {
                if (!(p('view.account.documents'))) {
                    $app->abort(401);
                }
            });

        $factory->match('/{user}/documents/updatestatus/', 'App\Controllers\DocumentController::updateDocumentStatus')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-documents-updatestatus')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/{user}/documents/updatefilestatus/', 'App\Controllers\DocumentController::updateFileStatus')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-documents-updatefilestatus')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->get('/{user}/documents/delete/', 'App\Controllers\DocumentController::deleteDocument')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-documents-delete')
            ->before(function () use ($app) {
                if (!(p('user.delete.idpic') || p('ccard.delete'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/deletefile/', 'App\Controllers\DocumentController::deleteFile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-documents-deletefile')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            });

        $factory->post('/{user}/documents/replacefile/', 'App\Controllers\DocumentController::replaceFile')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-documents-replacefile')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            });

        $factory->post('/{user}/documents/addmultiplefiles/', 'App\Controllers\DocumentController::addMultipleFilesToDocument')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-document-add-multiple-files')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            });

        $factory->post('/{user}/documents/updateexpirydate/', 'App\Controllers\DocumentController::updateExpiryDate')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-document-update-expiry-date')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            });

        $factory->post('/{user}/documents/rerender/', 'App\Controllers\DocumentController::rerenderDocument')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-rerender-document')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/rerenderverifyaccount/', 'App\Controllers\DocumentController::rerenderVerifyAccountPartial')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-rerender-verifyaccount')
            ->before(function () use ($app) {
                if (!(p('account.admin'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/create-source-of-funds/', 'App\Controllers\DocumentController::createSourceOfFunds')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-create-source-of-funds')
            ->before(function () use ($app) {
                if (!(p('documents.create.sourceoffunds'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/create-source-of-income/', 'App\Controllers\DocumentController::createSourceOfIncome')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-create-source-of-income')
            ->before(function () use ($app) {
                if (!(p('documents.create.sourceofincome'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/create-internal-document/', 'App\Controllers\DocumentController::createInternalDocument')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-create-internal-document')
            ->before(function () use ($app) {
                if (!(p('documents.create.internal.document'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/create-proofofwealth-document/', 'App\Controllers\DocumentController::createProofOfWealthDocument')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-create-proofofwealth-document')
            ->before(function () use ($app) {
                if (!(p('documents.create.proofofwealth.document'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/documents/create-proofofsourceoffunds-document/', 'App\Controllers\DocumentController::createProofOfSourceOfFundsDocument')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-create-proofofsourceoffunds-document')
            ->before(function () use ($app) {
                if (!(p('documents.create.proofofsourceoffunds.document'))) {
                    $app->abort(401);
                }
            });

        $factory->match('/{user}/risk-score/', 'App\Controllers\RgController::minFraud')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-p-risk-score')
            ->before(function () use ($app) {
                if (!p('user.risk-score')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/personal-details/show/', 'App\Controllers\UserProfileController::personalDetailsShow')
            ->bind('admin.show_personal_details_field')
            ->method('GET|POST');

        $factory->get('/{user}/account/forget/', 'App\Controllers\UserProfileController::forgetAccount')
            ->convert('user', $app['userProvider'])
            ->bind('admin.account.forget')
            ->before(function () use ($app) {
                if (!p('user.account.forget')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/account/delete/', 'App\Controllers\UserProfileController::accountDelete')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user.delete')
            ->before(function () use ($app) {
                if (!(p('user.account.delete'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/{user}/forcedepositlimit/', 'App\Controllers\UserProfileController::forceDepositLimit')
                ->convert('user', $app['userProvider'])
                ->bind('admin.user-force-deposit-limit')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.force-deposit-limit')) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/set-rg-monitoring-setting/{setting}/', 'App\Controllers\UserProfileController::setRgMonitoringSetting')
                ->convert('user', $app['userProvider'])
                ->bind('admin.user-set-rg-monitoring-setting')
                ->before(function () use ($app) {
                    $has_permission = false;
                    $permissions = ['ask_play_too_long', 'ask_bet_too_high', 'ask_gamble_too_much'];
                    foreach($permissions as $permission) {
                        if(p('rg.monitoring.'.$permission)) {
                            $has_permission = true;
                            break;
                        }
                    }

                    if (!$has_permission) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/forceselfassessmenttest/', 'App\Controllers\UserProfileController::forceSelfassessmentTest')
                ->convert('user', $app['userProvider'])
                ->bind('admin.user-force-self-assessment-test')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.force-self-assessment-test')) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/ask-rg-tools/', 'App\Controllers\UserProfileController::askRgTools')
                ->convert('user', $app['userProvider'])
                ->bind('admin.ask-rg-tools')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.ask-rg-tools')) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/contactedviaphone/', 'App\Controllers\UserProfileController::contactedViaPhone')
                ->convert('user', $app['userProvider'])
                ->bind('admin.contacted-via-phone')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.contacted-via-phone')) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/contactedviaemail/', 'App\Controllers\UserProfileController::contactedViaEmail')
                ->convert('user', $app['userProvider'])
                ->bind('admin.contacted-via-email')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.contacted-via-email')) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/rg-review/', 'App\Controllers\UserProfileController::rgReview')
                ->convert('user', $app['userProvider'])
                ->bind('admin.rg-review')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.review')) {
                        $app->abort(403);
                    }
                });

        $factory->get('/{user}/rg-no-action/', 'App\Controllers\UserProfileController::rgDailyAction')
            ->convert('user', $app['userProvider'])
            ->bind('admin.rg-daily-action')
            ->before(function () use ($app) {
                if (!p('rg.monitoring.daily-action')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/rg-no-action/', 'App\Controllers\UserProfileController::rgFollowUpAction')
            ->convert('user', $app['userProvider'])
            ->bind('admin.rg-follow-up-action')
            ->before(function () use ($app) {
                if (!p('rg.monitoring.follow-up-action')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/rg-no-action/', 'App\Controllers\UserProfileController::rgEscalationAction')
            ->convert('user', $app['userProvider'])
            ->bind('admin.rg-escalation-action')
            ->before(function () use ($app) {
                if (!p('rg.monitoring.escalation-action')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/rg-force-self-exlusion/', 'App\Controllers\UserProfileController::rgForceExclusionLock')
                ->convert('user', $app['userProvider'])
                ->bind('admin.rg-force-self-exlusion')
                ->before(function () use ($app) {
                    if (!p('rg.monitoring.force-self-exclusion')) {
                        $app->abort(403);
                    }
                });

        $factory->post('/{user}/manual-flag/', 'App\Controllers\UserProfileController::manualFlag')
                ->convert('user', $app['userProvider'])
                ->bind('user.account.flag.manual')
                ->before(function (Request $request) use ($app) {
                    if (!$this->hasPermissionToAddManualFlag($request->get('flag'))) {
                        $app->abort(403);
                    }
                });

        $factory->post('/{user}/transfer/', 'App\Controllers\UserProfileController::transfer')
            ->convert('user', $app['userProvider'])
            ->bind('user.account.transfer')
            ->before(function () use ($app) {
                if (!p('user.account.transfer')) {
                    $app->abort(403);
                }
            });


        $factory->post('/{user}/affordability-check/', 'App\Controllers\UserProfileController::affordabilityCheck')
            ->convert('user', $app['userProvider'])
            ->bind('user.account.affordability.check')
            ->before(function () use ($app) {
                if (!p('rg.monitoring.affordability-check')) {
                    $app->abort(403);
                }
            });

        $factory->post('/{user}/vulnerability-check/', 'App\Controllers\UserProfileController::vulnerabilityCheck')
            ->convert('user', $app['userProvider'])
            ->bind('user.account.vulnerability.check')
            ->before(function () use ($app) {
                if (!p('rg.monitoring.vulnerability-check')) {
                    $app->abort(403);
                }
            });

        $factory->get('/{user}/responsible-gaming-monitoring/rg-test-confirmation/', 'App\Repositories\ResponsibilityCheckRepository::rgTestConfirmation')
            ->convert('user', $app['userProvider'])
            ->bind('admin.rg-test-confirmation')
            ->before(function () use ($app) {
                if (!(p('rg.monitoring.rg-test-confirmation'))) {
                    $app->abort(401);
                }
            });

        return $factory;
    }

    public function debug(Application $app, User $user, Request $request)
    {
        return $app['blade']->view()->make('admin.user.debug', compact('app', 'user'))->render();
    }

    /**
     * @param Application $app
     * @param Group $group
     * @param Request $request
     * @return JsonResponse
     */
    public function listGroupPermissions(Application $app, Group $group, Request $request)
    {
        $group_permissions = $group->permission_groups()->get();
        return $app->json(['html' => $app['blade']->view()->make('admin.settings.permission.partials.permissions-list', compact('app', 'group', 'group_permissions'))->render()]);
    }

    /**
     * Adding permission to User
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return string
     */
    public function addPermissionToUser(Application $app, User $user, Request $request)
    {
        if (empty($request->get('permission'))) {
            $app['flash']->add('warning', "Field cannot empty. You need to select a permission from the list.");
            return new RedirectResponse($request->headers->get('referer'));
        }
        if ($user->permissions()->where(['tag' => $request->get('permission'), 'permission' => 'grant'])->exists()) {
            $app['flash']->add('warning', "User $user->username already have the permission <b>{$request->get('permission')}</b> linked.");
        } elseif ($user->permissions()->create(['tag' => $request->get('permission'), 'permission' => 'grant'])) {
            $app['flash']->add('success', "Permission <b>{$request->get('permission')}</b> has been successfully added to {$user->username}.");
        } else {
            $app['flash']->add('warning', "There was an error and the permission was not added.");
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * Removing permission from User
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return string
     */
    public function removePermissionFromUser(Application $app, User $user, Request $request)
    {
        if (!$user->permissions()->where(['tag' => $request->get('tag'), 'permission' => 'grant'])->exists()) {
            $app['flash']->add('warning', "User $user->username does not have the permission <b>{$request->get('tag')}</b> linked.");
        } elseif ($user->permissions()->where('tag', $request->query->get('tag'))->delete()) {
            $app['flash']->add('success', "Permission <b>{$request->get('tag')}</b> has been successfully removed from {$user->username}.");
        } else {
            $app['flash']->add('warning', "There was an error and the permission was not removed.");
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * Add user to a group
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function addUserToGroup(Application $app, User $user, Request $request)
    {
        if (empty($request->get('group'))) {
            $app['flash']->add('warning', "Field cannot empty. You need to select a group from the list.");
            return new RedirectResponse($request->headers->get('referer'));
        }
        $group = Group::find($request->get('group'));
        if (!$user->groups->contains($group)) {
            $user->groups()->save($group);
            $actor = UserRepository::getCurrentUser();
            $description = "added member {$request->get('username')} to {$group->name}";
            ActionRepository::logAction($user->id, $description, 'add_member', true, $actor->id);
            IpLog::logIp(UserRepository::getCurrentId(), $user->id, IpLog::TAG_GROUP, $description);
            $app['flash']->add('success', "User $user->username added to <b>$group->name</b> successfully.");
        } else {
            $app['flash']->add('warning', "User $user->username is already in <b>$group->name</b>.");
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * Remove user from a specified group
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function removeUserFromGroup(Application $app, User $user, Request $request)
    {
        $group = Group::find($request->query->get('group'));
        if ($user->groups->contains($group)) {
            $user->groups()->detach($request->query->get('group'));
            $actor = UserRepository::getCurrentUser();
            $description = "removed member {$request->get('username')} from {$group->name}";
            ActionRepository::logAction($user->id, $description, 'remove_member', true, $actor->id);
            IpLog::logIp(UserRepository::getCurrentId(), $user->id, IpLog::TAG_GROUP, $description);
            $app['flash']->add('success', "User $user->username removed from <b>$group->name</b> successfully.");
        } else {
            $app['flash']->add('warning', "User $user->username is not in <b>$group->name</b>.");
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * Manage user permissions
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function userPermissions(Application $app, User $user, Request $request)
    {
        $can_see_all_groups = p('view.user.permissions');

        if (!$can_see_all_groups) {
            $current_user = UserRepository::getCurrentUser();
            $manageable_groups = [];
            foreach ($current_user->groups()->get() as $group) {
                $manageable_groups = array_merge($manageable_groups, $group->manageable_groups());
            }
        }

        $groups_query = DB::table('groups AS g')
            ->select('g.group_id', 'g.name')
            ->leftJoin('groups_members as gm', function ($leftJoin) use ($user) {
                $leftJoin->on('gm.group_id', '=', 'g.group_id')->where('gm.user_id', '=', $user->getKey());
            })
            ->where('gm.user_id', '=', null);

        if (!$can_see_all_groups) {
            $groups_query->whereIn('g.group_id', $manageable_groups);
        }

        $groups = $groups_query->get()->toArray();

        $can_show_groups = $can_see_all_groups || !empty($manageable_groups);

        if (p('view.permissions.indiv')) {
            $permission_tags = DB::select("SELECT pt.* FROM permission_tags pt
                                        LEFT JOIN permission_users pu ON pu.tag = pt.tag AND pu.user_id = :user_id
                                      WHERE pu.user_id IS NULL", ['user_id' => $user->getKey()]);
        }

        return $app['blade']->view()->make('admin.user.permissions',
            compact('app', 'user', 'groups', 'permission_tags', 'can_show_groups'))->render();
    }

    /**
     * Return one user
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function getUserProfile(Application $app, User $user)
    {
        $monthsParam = $app['request_stack']->getCurrentRequest()->get('months');
        $months = is_numeric($monthsParam) && $monthsParam > 1 ? $monthsParam : 3;

        $uds_repo = new UserDailyStatsRepository();
        $session_repo = new UserSessionRepository();

        $self_exclusion_options = LimitsRepository::getUserSelfExclusionTimeOptions($user);

        $graphData['bets'] = $uds_repo->getBetsByMonth($user, $months);
        $graphData['gross'] = $uds_repo->getGrossByMonth($user, $months);
        $graphData['wins'] = $uds_repo->getWinsByMonth($user, $months);
        $graphData['deposits'] = $uds_repo->getDepositsByMonth($user, $months);
        $graphData['rewards'] = $uds_repo->getRewardsByMonth($user, $months);
        $graphData['withdrawals'] = $uds_repo->getWithdrawalsByMonth($user, $months);
        $graphData['site_prof'] = $uds_repo->getSiteProfitByMonth($user, $months);
        $graphData['logins'] = $session_repo->getLoginsByMonth($user, $months);
        $graphData['cashbacks'] = $uds_repo->getCashBacksByMonth($user, $months);
        $graphData['ngr'] = $uds_repo->getNgrByMonth($user, $months);

        return $app['blade']->view()->make('admin.user.show', compact('app', 'user', 'graphData', 'self_exclusion_options'))->render();
    }

    /**
     * Block user profile
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     *
     * @return string
     * @throws JsonException
     */
    public function blockUserProfile(Application $app, User $user, Request $request): string
    {
        try {
            if ($user->block_repo->addBlock($app, 3)) {
                $user->repo->deleteSetting(['unlock-date', 'lock-date', 'lock-hours']);
                $this->logIntervention($user, $request);

                $success = true;
                $msg = 'User successfully blocked.';
            } else {
                $success = false;
                $msg = 'User was not blocked due to an internal error or the user is not active.';
            }
        } catch (Exception $e) {
            $success = false;
            $msg = 'User not blocked due to a technical issue.';
        }

        return $this->jsonResponse($success, $msg);
    }

    /**
     * Unblock user profile
     *
     * @param Application $app
     * @param User $user
     * @return string
     * @throws JsonException
     */
    public function unBlockUserProfile(Application $app, User $user): string
    {
        try {
            $result = $user->block_repo->removeBlock($app);

            if ($result === true) {
                $success = true;
                $msg = 'User successfully unlocked.';
            } else {
                $success = false;
                $msg = is_string($result) ? $result : 'User not unlocked due to a technical issue.';
            }
        } catch (Exception $e) {
            $success = false;
            $msg = 'User not unlocked due to a technical issue.';
        }

        return $this->jsonResponse($success, $msg);
    }

    public function extendBlockingOneDay(Application $app, User $user)
    {
        $res = $user->block_repo->extendBlock(1, 'Y-m-d H:i:s');
        if ($res === false) {
            return json_encode(['success' => false, 'message' => "User not unlocked due to an internal error, user already active, super blocked or you do not have sufficient privileges."]);
        } else {
            return json_encode(['success' => true, 'message' => "This account will be automatically unlocked on {$res}."]);
        }
    }

    public function extendBlocking(Application $app, User $user)
    {
        $self_lock_cool_off_days = lic('getSelfLockCoolOffDays', [], $user->getKey());

        if ($user->block_repo->extendBlock($self_lock_cool_off_days)) {
            $response = ['success' => true, 'message' => "Block extended {$self_lock_cool_off_days} days."];
        } else {
            $response = ['success' => false, 'message' => 'Can not change the date due to is super block or not blocked at all.'];
        }

        return json_encode($response, JSON_THROW_ON_ERROR);

    }

    /**
     * Irreversible(??) Super Block one user profile
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function superBlockUserProfile(Application $app, User $user, Request $request)
    {
        if ((int)$user->repo->getCurrentId() != (int)$user->id) {
            $this->logIntervention($user, $request);
            return $user->block_repo->superBlock()->asJson($app);
        } else {
            return json_encode(['success' => true, 'message' => "You cannot super block yourself."]);
        }
    }

    /**
     * Remove super-block on a user
     *
     * @param Application $app
     * @param User $user
     * @return false|string
     */
    public function liftSuperBlockUserProfile(Application $app, User $user)
    {
        if ($user->block_repo->liftSuperBlock()) {
            return json_encode(['success' => true, 'message' => 'Super block lifted with a 7 days cooling off period.']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated"]);
        }
    }

    // General logic to toggle flags (values in users settings that are on (exists) or off (does not exist))
    public function toggleFlag(Request $request, User $user)
    {
        $flag = $request->get('flag');
        $action = $request->get('action');
        if ($action == 'off')
            $user->repo->deleteSetting($flag);
        else
            $user->repo->setSetting($flag, 1);
        return json_encode(['success' => true, 'message' => "$flag turned $action."]);
    }

    public function togglePasswordChangeOnLogin(Request $request, User $user)
    {
        $action = $request->get('action');
        if ($action == 'off')
            $user->repo->deleteSetting('pwd-change-on-next-login');
        else
            $user->repo->setSetting('pwd-change-on-next-login', 1);
        return json_encode(['success' => true, 'message' => "pwd-change-on-next-login turned $action."]);
    }

    public function toggleAUBypass(Request $request, User $user)
    {
        $flag = 'bypass-au-playcheck';
        $action = $request->get('action');
        if ($action == 'off') {
            $user->repo->deleteSetting($flag);
        } else {
            $user->repo->setSetting($flag, 1);
        }
        return json_encode(['success' => true, 'message' => "AU bypass turned $action."]);
    }

    /**
     * Error response for actions limit exceeded
     *
     * @return string
     * @throws JsonException
     */
    private function actionLimitReachedMsg(): string
    {
        return $this->jsonResponse(false, 'Number of actions on accounts limit exceeded');
    }

    /**
     * TODO do not use phive for that
     * Allowing user to play
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function allowToPlay(Application $app, User $user)
    {
        if (!UserRepository::allowedToDoAction('play_block')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->deleteSetting('play_block')) {
            return json_encode(['success' => true, 'message' => 'User allowed to play']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated"]);
        }
    }

    public function allowToDeposit(Application $app, User $user)
    {
        if (!UserRepository::allowedToDoAction('deposit_block')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->deleteSetting('deposit_block')) {
            return json_encode(['success' => true, 'message' => 'User allowed to deposit']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated"]);
        }
    }

    /**
     * @param Application $app
     * @param User        $user
     *
     * @return string
     * @throws JsonException
     */
    public function allowToWithdraw(Application $app, User $user): string
    {
        if (!UserRepository::allowedToDoAction('withdrawal_block')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->deleteSetting('withdrawal_block')) {
            return json_encode(['success' => true, 'message' => 'User allowed to withdraw']);
        }

        return json_encode(['success' => false, 'message' => "Data not updated"]);
    }

    /**
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     *
     * @return string
     * @throws JsonException
     */
    public function disAllowToWithdraw(Application $app, User $user, Request $request): string
    {
        if (!UserRepository::allowedToDoAction('withdrawal_block')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->setSetting('withdrawal_block', 1)) {
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => 'User not allowed to withdraw']);
        }

        return json_encode(['success' => false, 'message' => "Data not updated."]);
    }

    /**
     * @param Application $app
     * @param User $user
     *
     * @return string
     * @throws JsonException
     */
    public function unrestrict(Application $app, User $user): string
    {
        if (!UserRepository::allowedToDoAction('restrict')) {
            return $this->actionLimitReachedMsg();
        }

        if ($user->getSetting('restriction_reason') === 'cdd_check') {
            $user->repo->deleteSetting('cdd_withdrawal_block');
        }

        $users_settings = ['restrict', 'restriction_reason'];
        $result = $user->repo->deleteSetting($users_settings);

        if (!$result) {
            return $this->jsonResponse(false, 'Data not updated');
        }

        if ($user->repo->isEnabledStatusTracking()) {
            $status = $user->repo->getAllowedUserStatus($users_settings);
            $user->repo->trackUserStatusChanges($status);
        }

        return $this->jsonResponse(true, 'User unrestricted');
    }

    public function closeAccount(Request $request, User $user)
    {
        $reason = $request->get('reason');
        $hasPermission = p('user.account-closure.' . $reason);
        $isClosedAccount = $user->repo->hasSetting('closed_account');

        if ($hasPermission && !$isClosedAccount) {
            $user->repo->setSetting('closed_account', 1);
            $user->repo->setSetting('closed_account_date', Carbon::now());
            $user->repo->setSetting('closed_account_reason', $reason);
            $user->update(['active' => 0]);

            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_SUSPENDED);
            $this->logIntervention($user, $request);

            $comment = [
                'user_id' => $user->id,
                'tag' => 'account-closure',
                'comment' => "User account closed, reason: " . $user->repo->getAccountClosureReason() . ' // '. UserRepository::getCurrentUsername(),
            ];

            UserCommentRepository::createComment($comment);
            return json_encode(['success' => true, 'message' => 'User account closed.']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Denying user to play
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function disAllowToPlay(Application $app, User $user, Request $request)
    {
        if (!UserRepository::allowedToDoAction('play_block')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->setSetting('play_block', 1)) {
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => 'User not allowed to play']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    public function disAllowToDeposit(Application $app, User $user, Request $request)
    {
        if (!UserRepository::allowedToDoAction('deposit_block')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->setSetting('deposit_block', 1)) {
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => 'User not allowed to deposit']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    public function restrict(Application $app, User $user, Request $request)
    {
        if (!UserRepository::allowedToDoAction('restrict')) {
            return $this->actionLimitReachedMsg();
        }
        if ($user->repo->setSetting('restrict', 1)) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_RESTRICTED);
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => 'User restricted']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Verifying user email
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function verifyEmail(Application $app, User $user)
    {
        if ($user->repo->setSetting('email_code_verified', 'yes')) {
            return json_encode(['success' => true, 'message' => "User email verified successfully."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * UnVerifying user email
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function unVerifyEmail(Application $app, User $user)
    {
        if ($user->repo->deleteSetting('email_code_verified')) {
            return json_encode(['success' => true, 'message' => 'User email unverified successfully.']);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated"]);
        }
    }

    /**
     * Verifying user profile
     *
     * @param Application $app
     * @param User $user
     *
     * @return string
     * @throws JsonException
     */
    public function verifyUserProfile(Application $app, User $user): string
    {
        try {
            DB::transaction(function () use ($user, $app) {
                $old_setting_value = $user->repo->getSetting('verified');
                $user->repo->setSetting('verified', 1);
                $user_settings = [
                    'restrict',
                    'experian_block',
                    'tmp_deposit_block',
                    'temporal_account',
                    'restriction_reason'
                ];

                if ($user->repo->getSetting('restriction_reason') === 'cdd_check') {
                    $user_settings[] = 'cdd_withdrawal_block';
                }
                $user->repo->deleteSetting($user_settings);

                if ($user->repo->isEnabledStatusTracking()) {
                    $old_status = $this->getUserStatusString($user);
                    $status = $user->repo->getAllowedUserStatus($user_settings);
                    $user->repo->trackUserStatusChanges($status);

                    if ($status !== UserStatus::STATUS_ACTIVE && $this->getUserStatusString($user) === $old_status) {
                        $app['monolog']->addWarning(static::class . ' verifyUserProfile: User status has not been changed.',
                            [
                                'old_status' => $old_status,
                                'allowed_status' => $status,
                                'verified' => $user->repo->getSetting('verified'),
                                'user_id' => $user->id
                            ]
                        );
                    }

                }

                $user = phive('UserHandler')->getUser($user->getKey());
                lic('onVerify', [$user, $old_setting_value], $user);

                if (!empty($old_setting_value)) {
                    return;
                }

                if (phive()->moduleExists("MailHandler2")) {
                    phive("MailHandler2")->sendMail('account.verified', $user);
                }
                if (phive()->moduleExists('Trophy')) {
                    phive('Trophy')->onEvent('verify', $user);
                }
            });

            return $this->jsonResponse(true, 'User verified successfully.');
        } catch (Exception $e) {
            return $this->jsonResponse(false, "Data not updated.");
        }
    }

    /**
     * Un verify user profile
     *
     * @param Application $app
     * @param User $user
     * @return string
     * @throws Exception
     */
    public function unVerifyUserProfile(Application $app, User $user): string
    {
        if ($user->repo->deleteSetting('verified')) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_PENDING_VERIFICATION);
            $success = true;
            $msg = 'User unverified successfully.';
        } else {
            $success = false;
            $msg = 'Filed not updated';
        }

        return $this->jsonResponse($success, $msg);
    }

    /**
     * Verifying user phone
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function verifyPhone(Application $app, User $user)
    {
        if ($user->update(['verified_phone' => 1])) {
            return json_encode(['success' => true, 'message' => 'User phone verified successfully']);
        } else {
            return json_encode(['success' => false, 'message' => 'Data not updated.']);
        }
    }

    /**
     * UnVerifying user phone
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function unVerifyPhone(Application $app, User $user)
    {
        if ($user->update(['verified_phone' => 0])) {
            return json_encode(['success' => true, 'message' => 'User phone unverified successfully']);
        } else {
            return json_encode(['success' => false, 'message' => 'Data not updated.']);
        }
    }

    /**
     * Set last phoned date to now
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function setPhonedDateToNow(Application $app, User $user)
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        if ($user->repo->setSetting('phoned-date', Carbon::now()->format('Y-m-d H:i:s'))) {
            return json_encode(['success' => true, 'message' => "User phoned date set up successfully to {$now}."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Delete last phoned date
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function deletePhonedDate(Application $app, User $user)
    {
        if ($user->repo->deleteSetting('phoned-date')) {
            return json_encode(['success' => true, 'message' => 'User phoned date deleted successfully.']);
        } else {
            return json_encode(['success' => false, 'message' => "Filed not updated"]);
        }
    }

    public function clearIPLog(Application $app, User $user)
    {
        try {
            DB::update("UPDATE users SET reg_ip = '' WHERE reg_ip = :ip", ['ip' => $user->reg_ip]);
        } catch (Exception $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        return json_encode(['success' => true, 'message' => 'Reg IP cleared successfully']);
    }

    /**
     * Show bank deposit option for trusted users
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function showBank(Application $app, User $user)
    {
        if ($user->repo->setSetting('show_bank', 1)) {
            return json_encode(['success' => true, 'message' => "Show bank set up successfully."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Show Euteller deposit option for trusted users
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function showEuteller(Application $app, User $user)
    {
        if ($user->repo->setSetting('show_euteller', 1)) {
            return json_encode(['success' => true, 'message' => "Show Euteller set up successfully."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Show EnterCash deposit option for trusted users
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function showEnterCash(Application $app, User $user)
    {
        if ($user->repo->setSetting('show_entercash', 1)) {
            return json_encode(['success' => true, 'message' => "Show EnterCash set up successfully."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Updating user fifo date to now
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function updateFifoDate(Request $request, User $user)
    {
        $now = empty($request->get('fifo-date')) ? Carbon::now()->format('Y-m-d H:i:s') : $request->get('fifo-date');
        if ($user->repo->setSetting('fifo_date', $now)) {
            return json_encode(['success' => true, 'message' => "Updated fifo date to $now."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function activePreventCCardFlag(Application $app, User $user)
    {
        if ($user->repo->setSetting('no-ccard-fraud-flag', 1) && $user->repo->deleteSetting('ccard-fraud-flag')) {
            return json_encode(['success' => true, 'message' => "Prevent credit card fraud flag activated."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function deactivePreventCreditCardFlag(Application $app, User $user)
    {
        //TODO we need to use CasinoCashier->removeCardFraudFlag($user) here.
        if ($user->repo->deleteSetting('no-ccard-fraud-flag')) {
//            $phive_user = phive('UserHandler')->getUser($user->id);
//            phive('Cashier')->removeCardFraudFlag($phive_user);
            return json_encode(['success' => true, 'message' => "Prevent credit card fraud flag removed."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return string
     */
    public function preventLiabilityFlag(Application $app, Request $request, User $user)
    {
        switch ($request->get('action')) {
            case 'active':
                $user->repo->setSetting('liability-flag-prevent', Carbon::now()->toDateString());
                return json_encode(['success' => true, 'message' => "Prevent liability fraud flag set to " . Carbon::now()->toDateString()]);
                break;
            case 'delete':
                $user->repo->deleteSetting('liability-flag-prevent');
                return json_encode(['success' => true, 'message' => "Prevent liability fraud flag removed."]);
                break;
            default:
                return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function deactiveCreditCardFraudFlag(Application $app, User $user)
    {
        if ($user->repo->deleteSetting('ccard-fraud-flag')) {
            return json_encode(['success' => true, 'message' => "Credit card fraud flag removed."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function deactiveMajorityFraudFlag(Application $app, User $user)
    {
        if ($user->repo->deleteSetting('majority-fraud-flag')) {
            return json_encode(['success' => true, 'message' => "Majority switch fraud flag removed."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function allowToChat(Application $app, User $user)
    {
        if ($user->repo->deleteSetting(['mp-chat-block', 'mp-chat-block-unlock-date', 'mp-chat-block-unlock'])) {
            return json_encode(['success' => true, 'message' => "Allowed to chat done."]);
        } else {
            return json_encode(['success' => false, 'message' => "Internal error: chat not unlocked."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function blockToChatPermanent(Application $app, User $user, Request $request)
    {
        if ($user->repo->setSetting('mp-chat-block', 1)) {
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => "Block to chat done."]);
        } else {
            return json_encode(['success' => false, 'message' => "Internal error: chat not unlocked."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function blockToChatDays(Application $app, User $user, Request $request)
    {
        if ($user->repo->setSetting('mp-chat-block', 1) && $user->repo->setSetting('mp-chat-block-unlock-date', Carbon::now()->addDays(7)->toDateTimeString())) {
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => "Block to chat done for 7 days."]);
        } else {
            return json_encode(['success' => false, 'message' => "Internal error: chat not unlocked."]);
        }
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function deactiveBonusFraudFlag(Application $app, User $user)
    {
        if ($user->repo->deleteSetting('bonus-fraud-flag')) {
            return json_encode(['success' => true, 'message' => "Bonus fraud flag removed."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * @param Application $app
     * @param User $user
     *
     * @return string
     * @throws JsonException
     */
    public function deactivateManualFraudFlag(Application $app, User $user): string
    {
        $users_settings = ['manual-fraud-flag'];
        $user->repo->deleteSetting($users_settings);

        if ($user->repo->isEnabledStatusTracking()) {
            $status = $user->repo->getAllowedUserStatus($users_settings);
            $user->repo->trackUserStatusChanges($status);
        }

        return $this->jsonResponse(true, 'Manual fraud flag removed.');
    }

    public function deactivateTooManyRollbacksFraudFlag(Application $app, User $user): string
    {
        TooManyRollbacksFlag::create()->revoke(cu($user->id), RevokeEvent::MANUAL);

        if ($user->repo->isEnabledStatusTracking()) {
            $status = $user->repo->getAllowedUserStatus(['too_many_rollbacks-fraud-flag']);
            $user->repo->trackUserStatusChanges($status);
        }

        return $this->jsonResponse(true, 'Too many rollbacks fraud flag removed.');
    }

    public function deactivateTotalWithdrawalAmountLimitReachedFraudFlag(Application $app, User $user): string
    {
        TotalWithdrawalAmountLimitReachedFlag::create()->revoke(cu($user->id), RevokeEvent::MANUAL);

        if ($user->repo->isEnabledStatusTracking()) {
            $status = $user->repo->getAllowedUserStatus(['total-withdrawal-amount-limit-reached-fraud-flag']);
            $user->repo->trackUserStatusChanges($status);
        }

        return $this->jsonResponse(true, 'Total withdrawal amount limit reached fraud flag removed.');
    }

    public function deactivateSuspiciousEmailFraudFlag(Application $app, User $user): string
    {
        $users_settings = ['suspicious-email-fraud-flag'];
        $user->repo->deleteSetting($users_settings);

        if ($user->repo->isEnabledStatusTracking()) {
            $status = $user->repo->getAllowedUserStatus($users_settings);
            $user->repo->trackUserStatusChanges($status);
        }

        return $this->jsonResponse(true, 'Suspicious email fraud flag removed.');
    }

    public function deactivateNegativeBalanceSinceDepositFraudFlag(Application $app, User $user): string
    {
        NegativeBalanceSinceDepositFlag::create()->revoke(cu($user->id), RevokeEvent::MANUAL);

        if ($user->repo->isEnabledStatusTracking()) {
            $status = $user->repo->getAllowedUserStatus(['negative-balance-since-deposit-fraud-flag']);
            $user->repo->trackUserStatusChanges($status);
        }

        return $this->jsonResponse(true, 'Negative balance since deposit fraud flag removed.');
    }

    /**
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function activateManualFraudFlag(Application $app, User $user)
    {
        if ($user->repo->setSetting('manual-fraud-flag', 1)) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_UNDER_INVESTIGATION);
            return json_encode(['success' => true, 'message' => "Manual fraud flag activated."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    public function activateTooManyRollbacksFraudFlag(Application $app, User $user)
    {
        $flagAssigned = TooManyRollbacksFlag::create()->assign(cu($user->id), AssignEvent::MANUAL);

        if ($flagAssigned) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_UNDER_INVESTIGATION);
            return json_encode(['success' => true, 'message' => "Too many rollbacks fraud flag activated."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    public function activateTotalWithdrawalAmountLimitReachedFraudFlag(Application $app, User $user)
    {
        $flagAssigned = TotalWithdrawalAmountLimitReachedFlag::create()->assign(cu($user->id), AssignEvent::MANUAL);

        if ($flagAssigned) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_UNDER_INVESTIGATION);
            return json_encode(['success' => true, 'message' => "Total withdrawal amount limit reached fraud flag activated."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    public function activateSuspiciousEmailFraudFlag(Application $app, User $user)
    {
        if ($user->repo->setSetting('suspicious-email-fraud-flag', 1)) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_UNDER_INVESTIGATION);
            return json_encode(['success' => true, 'message' => "Suspicious email fraud flag activated."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    public function activateNegativeBalanceSinceDepositFraudFlag(Application $app, User $user)
    {
        $flagAssigned = NegativeBalanceSinceDepositFlag::create()->assign(cu($user->id), AssignEvent::MANUAL);

        if ($flagAssigned) {
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_UNDER_INVESTIGATION);
            return json_encode(['success' => true, 'message' => "Negative balance since deposit fraud flag activated."]);
        } else {
            return json_encode(['success' => false, 'message' => "Data not updated."]);
        }
    }

    /**
     * Post allowed countries form
     * Performs add new, delete
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return string
     */
    public function postAllowedCountriesForm(Application $app, User $user, Request $request)
    {
        if (empty($request->get('iso'))) {
            return json_encode(['success' => false, 'message' => 'No login country was selected']);
        } else {
            $repo = new UserSettingsRepository($user);
            $repo->addAllowedLoginCountry($user, $request->get('iso'));
            return json_encode(['success' => true, 'message' => 'New login country has been added successfully']);
        }
    }

    /**
     * Deleting user allowed country
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return string
     */
    public function deleteAllowedCountry(Application $app, User $user, Request $request)
    {
        $repo = new UserSettingsRepository($user);
        $repo->deleteAllowedLoginCountry($user, 'login-allowed-' . $request->get('iso'));
        return json_encode(['success' => true, 'message' => 'Login country has been deleted successfully']);
    }

    /**
     * todo port $u
     * @param Application $app
     * @param User $user
     * @return string
     */
    public function getEditUserForm(Application $app, User $user)
    {
        $u = cu($user->username);

        /** @var UserSettingsRepository settings_repo */
        $user->settings_repo = new UserSettingsRepository($user);
        $user->settings_repo->populateSettings();
        $settings = $user->settings_repo->getSettings();

        return $app['blade']->view()->make('admin.user.edit', compact('user', 'app', 'u', 'settings'))->render();
    }

    /**
     * If country is to be updated then update the master and add the change into an action and the changes table
     *
     * TODO next one that does any validation on User has to move this into a model with the validator
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function postEditUserForm(Application $app, User $user, Request $request)
    {
        if (!$request->isMethod('POST')) {
            $app->abort(405);
        }
        try {
            $updatingElements = $request->request->all();

            $checkDuplicate = function ($field) use ($app, $user, $updatingElements) {
                if ($user->{$field} !== $updatingElements[$field]) {
                    $existingUser = User::shs()->where($field, '=', $updatingElements[$field])->first();
                    if (!empty($existingUser) && $existingUser->id !== $user->id) {
                        $error_message = "Field {$field} is already taken by another customer.";
                        $app['flash']->add('warning', $error_message);
                        throw new Exception($error_message);
                    }
                }
            };

            $checkDuplicate('email');
            $checkDuplicate('alias');

            $originalValues = $user->getOriginal();
            $changes = $user->getDirty();

            $this->updateUserSettings($updatingElements, $user, 'main_province', $originalValues, $changes);
            $this->updateUserSettings($updatingElements, $user, 'birth_country', $originalValues, $changes);
            $this->updateUserSettings($updatingElements, $user, 'nationality', $originalValues, $changes);
            $this->updateUserSettings($updatingElements, $user, 'place_of_birth', $originalValues, $changes);

            $user->fill($updatingElements);

            if (array_key_exists('country', $changes)) {
                ActionRepository::logAction($user->getKey(), "Country updated from {$user->getOriginal('country')} to {$changes['country']}", 'user-country-update');
            }

            if (array_key_exists('email', $changes)) {
                if (filter_var($user->username, FILTER_VALIDATE_EMAIL)) {
                    $user->username = $user->email;
                    $changes['username'] = $user->username;
                }
            }

            if ($user->save([], true)) {
                ActionRepository::logAction(
                    $user->getKey(),
                    "Updated profile with: " . json_encode($changes),
                    "profile-update-by-admin",
                    true
                );
                lic('onAccountUpdate', [$user->getKey()], $user->getKey());

                lic('onUserCreatedOrUpdated', [$user->getKey(), $changes, $originalValues]);

                //Updating the user in the monitoring sytem(to know if the user is PEP /SL,Acuris)
                phive('Cashier/Fr')->updateUserOnExternalKycMonitoring($user->getKey());
            }

            $app['flash']->add('success', "User data updated successfully.");
            return new RedirectResponse($request->headers->get('referer'));

        } catch (Exception $e) {

            $app['flash']->add('warning', "User data update failed.");
            $app['monolog']->addError("Error updating the user data: {$e->getMessage()}");
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function postEditUserSettingsForm(Application $app, User $user, Request $request)
    {
        if (!$request->isMethod('POST')) {
            $app->abort(405);
        }

        $form_settings = new Settings($request->request->all(), Settings::FORM_SOURCE);
        $settingsRepo = new UserSettingsRepository($user);
        $settingsRepo->populateSettings();
        $settingsRepo->updateAll($app, $form_settings);

        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function postEditUserPrivacySettingsForm(Application $app, User $user, Request $request): RedirectResponse
    {
        if (!$request->isMethod('POST')) {
            $app->abort(405);
        }

        phive('DBUserHandler/PrivacyHandler')->saveFormData(
            $request->request->get('settings') ?? [],
            $user->id,
            array_keys(phive('DBUserHandler/PrivacyHandler')->getPrivacySettings($user->id))
        );

        $app['flash']->add('success', "User privacy settings updated successfully.");
        return new RedirectResponse($request->headers->get('referer'));
    }

    public function getGamesAjax(Application $app, User $user, Request $request)
    {
        $view = $app['blade']->view()->make('admin.user.partials.boxes.game-data', ['start_date' => Carbon::parse($request->get('start_date'))->startOfDay(), 'end_date' => Carbon::parse($request->get('end_date'))->endOfDay(), 'user' => $user])->render();

        return $app->json(['html' => $view]);
    }

    public function getFinancialAjax(Application $app, User $user, Request $request)
    {
        if (!empty($request->get('start_date')) && !empty($request->get('end_date'))) {
            $start_date = Carbon::parse($request->get('start_date'))->startOfDay();
            $end_date = Carbon::parse($request->get('end_date'))->endOfDay();
        } else {
            $start_date = $end_date = null;
        }

        $view = $app['blade']->view()->make('admin.user.partials.boxes.financial-data', [
            'initial_state' => !empty($request->get('initial_state')),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user' => $user,
            'deposits' => $user->repo->getDepositsList($start_date, $end_date),
            'withdrawals' => $user->repo->getWithdrawalsList($start_date, $end_date)
        ])->render();

        return $app->json(['html' => $view]);
    }

    public function updateFollowUp(Application $app, User $user, Request $request)
    {
        if (!$request->isMethod('POST')) {
            $app->abort(405);
        }

        try {
            $form_settings = new Settings($request->request->all(), Settings::FORM_SOURCE);
            $user->settings_repo->populateSettings();
            $user->settings_repo->updateAll($app, $form_settings);
            $tag = "{$form_settings->category}-risk-group";

            $user_comment = new UserComment();
            $user_comment->tag = $tag;
            $user_comment->user_id = $user->id;
            $user_comment->comment = $form_settings->comment;
            $user_comment->save();

            $actor = UserRepository::getCurrentUser();
            ActionRepository::logAction($user->getKey(), "{$actor->username} set comment to {$form_settings->comment}", $tag);

        } catch (Exception $e) {
            return $app->json(['success' => false, 'message' => "Error: {$e->getMessage()}"]);
        }

        return $app->json(['success' => true, 'message' => 'Follow up information updated successfully']);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCriminalRecord(Application $app, User $user, Request $request)
    {
        if (!$request->isMethod('POST') && !$request->isXmlHttpRequest()) {
            $app->abort(405);
        }

        try {
            $result = $user->repo->setSetting('criminal_record', $request->get('status'));
        } catch (Exception $e) {
            return $app->json(['success' => false, 'message' => "Error: {$e->getMessage()}"]);
        }

        if ($result) {
            return $app->json(['success' => true, 'message' => 'The Criminal Record status was successfully updated.']);
        } else {
            return $app->json(['success' => false, 'message' => "The Criminal Record status wasn't updated."]);
        }
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function personalDetailsShow(Application $app, Request $request) {
        $data = $request->request->all();
        $response = [
            'success' => false
        ];
        if (empty($data)) {
            $data = [
                'field' => $request->get('field'),
                'user_id' => $request->get('user_id')
            ];
        }
        if (empty($data['field']) or empty($data['user_id'])) {
            return $app->json($response);
        }

        /** @var User $user */
        $user = User::query()->where('id', '=', $data['user_id'])->first();

        if (!$user) {
            return $app->json($response);
        }

        $user->settings_repo->populateSettings();
        $user->block_repo->populateSettings();

        if ($user->isForgotten()) {
            return $app->json($response);
        }

        if ($this->isAboveDailyLimits($app, $data)) {
            $response = [
              'success' => false,
              'msg' => 'You have reached the daily limit for viewing personal data.',
            ];
            return $app->json($response);
        }

        $user = $this->fixUserObject($user, $data['field']);
        $description = "Show button on {$data['field']}";
        $tag = 'privacy-see-details';
        if (is_string($data['field'])) {
            if (array_has($user->toArray(), $data['field'])) {
                $response['success'] = true;
                $response['key'] = $data['field'];

                if ($data['field'] == 'show_contact_form') {
                    $_SESSION['show_contact_form'] = true;
                    $response['redirect'] = $request->headers->get('referer');
                 } else {
                    $response['value'] = $user->{$data['field']};
                }
            }
        } elseif (is_array($data['field'])) {
            $response['fields'] = [];
            foreach ($data['field'] as $field) {
                if (array_has($user->toArray(), $field)) {
                    $response['success'] = true;
                    $response['fields'][] = [
                        'field' => $field,
                        'value' => $user->{$field}
                    ];
                }
            }
            $description = 'Show all personal details.';
            $tag = 'privacy-see-details-all';
        }

        ActionRepository::logAction($data['user_id'], $description, $tag);
        return $app->json($response);
    }

    /**
     * Check if the current user has exceeded he's limit for showing personal data
     * if so we send emails and log this action
     *
     * @param $app
     * @param $data
     *
     * @return bool
     * @throws Exception
     */
    private function isAboveDailyLimits($app, $data)
    {
        $is_daily_limit_active = Config::getValue('is_daily_limit_active', 'personal_data', 'no');
        $daily_limit = Config::getValue('display_daily_limit', 'limits', "0");
        $email_list = Config::getValue('personal_data_limit_email_list', 'emails', '', false, true);

        if ($is_daily_limit_active === 'no' || $daily_limit === "0" || empty($email_list[0])){
            return false;
        }

        $current_user = UserRepository::getCurrentUser();
        $start_current_day = Carbon::now()->startOfDay()->toDateTimeString();
        $end_current_day = Carbon::now()->endOfDay()->toDateTimeString();

        $actor_day_view_count = DB::table('actions')
          ->where('actor', $current_user->getKey())
          ->whereBetween('created_at', [$start_current_day, $end_current_day])
          ->where(function ($query) {
              $query->where('tag', 'privacy-see-details')
                    ->orWhere('tag', 'privacy-see-details-all');
          })
          ->where('descr', 'NOT LIKE', '%visited page%')
          ->count();

        $limit_already_reached = DB::table('actions')
            ->where('actor', $current_user->getKey())
            ->whereBetween('created_at', [$start_current_day, $end_current_day])
            ->where('tag', 'privacy-see-details-limit-reached')
            ->count();

        if ($actor_day_view_count >= $daily_limit) {

            if ($limit_already_reached > 0) {
                return true;
            }

            (new UserProfileRepository)->sendDisplayLimitReachedMail($email_list, $current_user);

            ActionRepository::logAction($current_user, 'Personal data display limit was reached', 'privacy-see-details-limit-reached');

            return true;
        }

        // handle the case when the daily limit changed during the same day
        else if ($limit_already_reached > 0) {
            DB::table('actions')
                ->where('actor', $current_user->getKey())
                ->whereBetween('created_at', [$start_current_day, $end_current_day])
                ->where('tag', 'privacy-see-details-limit-reached')
                ->delete();
        }

        return false;
    }

    /**
     * @param User $user
     * @param $fields
     *
     * @return mixed
     */
    private function fixUserObject($user, $fields) {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $citizenship = '';
        if ($user->repo->hasSetting('citizenship')) {
            $formatted = DataFormatHelper::formatCountries([$user->repo->getSetting('citizenship')]);
            $citizenship = array_values($formatted)[0];
        }

        $fields_map = [
            'full_name' => $user->firstname . ' ' . $user->lastname,
            'address_city' => $user->address . ', ' . $user->city,
            'newsletter' => $user->newsletter == 1 ? "Yes" : "No",
            'affiliate_username' => $user->affe_id,
            'ext_kyc_age_check' => $user->repo->getDobCheckResult(),
            'ext_kyc_pep_check' => $user->repo->getPepCheckResult(),
            'nid' => $user->getNid(true),
            'sms' => $user->repo->getSetting('sms') == 1 ? "Yes" : "No",
            'play_block' => $user->repo->getSetting('play_block') == 1 ? "No" : "Yes",
            'occupation' => $user->repo->getSetting('occupation'),
            'industry'   => $user->repo->getSetting('industry'),
            'intended_gambling' => $user->repo->getIntendedGamblingLimit(),
            'spending_amount' => DataFormatHelper::nf($user->repo->getSetting('spending_amount')) . ' ' . $user->currency,
            'current_status' => $this->getUserStatusPretty($user),
            'affiliate' => $user->affe_id,
            'show_contact_form' => 'edit profile: contact information',
            'birth_country' => $user->repo->getSetting('birth_country'),
            'nationality' => $user->repo->getSetting('nationality'),
            'residence_country' => $user->repo->getSetting('residence_country'),
            'lastname_second' => $user->repo->getSetting('lastname_second'),
            'fiscal_region' => $user->repo->getSetting('fiscal_region'),
            'fiscal_code' => $user->repo->getSetting('fiscal_code'),
            'firstname_initials' => $user->repo->getSetting('firstname_initials'),
            'birth_place' => $user->repo->getSetting('birth_place'),
            'citizen_service_number' => $user->repo->getSetting('citizen_service_number'),
            'main_province' => $user->repo->getSetting('main_province'),
            'place_of_birth' => $user->repo->getSetting('place_of_birth'),
            'building' => $user->repo->getSetting('building'),
            'doc_type' => lic('getDocumentTypeList', [], $user->id)[$user->repo->getSetting('doc_type')],
            'doc_number' => $user->repo->getSetting('doc_number'),
            'doc_date' =>  Carbon::createFromDate($user->repo->getSetting('doc_year'), $user->repo->getSetting('doc_month'), $user->repo->getSetting('doc_date'))->format('Y m d'),
            'doc_issued_by' => $user->repo->getSetting('doc_issued_by'),
            'citizenship' => $citizenship,
            'company_name' => $user->repo->getSetting('company_name'),
            'company_address' => $user->repo->getSetting('company_address'),
            'company_phone_number' => $user->repo->getSetting('company_phone_number'),
            'jurisdiction' => $user->repo->getSetting('jurisdiction'),
        ];

        foreach ($fields as $field) {
            if (in_array($field, array_keys($fields_map))) {
                $user->{$field} = $fields_map[$field];
            }
        }

        return $user;
    }

    /**
     * Return internal status as pretty string for the user.
     * If a specific override exist for a Jurisdiction we return "EXTERNAL: INTERNAL"
     *
     * @param User $user
     * @return string
     */
    private function getUserStatusPretty(User $user): string
    {
        $status = (string) $this->getUserStatusString($user);

        $status_exploded = array_filter(explode(' - ', $status));

        if (empty($status_exploded)) {
            return '';
        }

        $status_pretty = str_replace('_', ' ', head($status_exploded));

        // fix typo `Canceled` into `Cancelled` for BO
        if ($status_pretty === UserStatus::STATUS_CANCELED) {
            $status_pretty = 'Cancelled';
        }

        $status_pretty = ucwords(strtolower($status_pretty));

        if (count($status_exploded) > 1) {
            $status_pretty = last($status_exploded) . ': ' . $status_pretty;
        }

        return $status_pretty;
    }

    /**
     * Return internal status string for the user.
     * If a specific override exist for a Jurisdiction we return "INTERNAL - EXTERNAL"
     *
     * @param User $user
     * @return false|mixed|string
     */
    private function getUserStatusString(User $user)
    {
        $internal_status = $user->repo->getSetting('current_status');
        $external_status = lic('getExternalUserStatusMapping', [$internal_status], $user->getKey());
        return $internal_status === $external_status ? $internal_status : $internal_status . ' - ' . $external_status;
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function forgetAccount(User $user)
    {
        if ($user->repo->setSetting('forgotten', 1)) {
            $user->repo->setSetting('sms', 0);
            $user->newsletter = 0;
            $user->save();
            // TODO See how we can handle this logic via CRON for Spain (ES) - automatic after 2 years of inactivity
            $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_SUSPENDED);
            return json_encode([
                'success' => true,
                'message' => "The user account has been forgotten."
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => "Internal error: the user account has not been forgotten."
            ]);
        }
    }
    /**
     * Delete user account
     *
     * @param Application $app
     * @param User        $user
     *
     * @return string
     * @throws Exception
     */
    public function accountDelete(Application $app, User $user)
    {
        $five_years_ago = Carbon::now()->subYears(5);
        $forgotten = UserSetting::query()
            ->where('user_id', $user->id)
            ->where('setting', 'forgotten')
            ->first();

        $can_delete = $forgotten && $five_years_ago->greaterThan(new Carbon($forgotten->created_at));

        if (!$can_delete) {
            $app['flash']->add('error', "Can't delete accounts which are not forgotten for at least 5 years.");

            return json_encode([
                'success' => true,
                'redirect' => $app['url_generator']->generate('admin.userprofile', ['user' => $user->id])
            ]);
        }

        try {
            $username = UserProfileRepository::deleteAccount($app, $user);
        } catch (Exception $e) {
            $app['flash']->add('error', "Can't delete this account. Internal error.");

            return json_encode([
                'success' => true,
                'redirect' => $app['url_generator']->generate('admin.userprofile', ['user' => $user->id])
            ]);
        }

        // notify that everything went smoothly
        $app['flash']->add('success', "User {$username} was deleted.");

        // redirect to main users list
        return json_encode([
            'success' => true,
            'redirect' => $app['url_generator']->generate('user')
        ]);
    }

    public function forceDepositLimit(Application $app, User $user, Request $request)
    {
        $this->logIntervention($user, $request);
        return json_encode([
            'success' => (bool)$user->repo->setSetting(Action::TAG_FORCE_DEPOSIT_LIMIT, 1),
            'message' => 'User forced to set deposit limit.'
        ]);
    }

    public function testAccount(Application $app, User $user)
    {
        if (!empty($user->repo->getSetting('test_account'))) {
            $success = $user->repo->deleteSetting(['test_account']);
        } else {
            $success = $user->repo->setSetting('test_account', 1);
        }

        return json_encode([
            'success' => (bool)$success,
            'message' => 'User account type changed.'
        ]);
    }

    /**
     * Common wrapper to add a setting to a specific User for RG monitoring purposes.
     *
     * @param Application $app
     * @param User $user
     * @param $setting
     * @return false|string
     */
    public function setRgMonitoringSetting(Application $app, Request $request, User $user)
    {
        $setting = $request->get('setting');
        $success = false;
        $message = 'Failed to force RG Monitoring for ';

        if ($user->repo->setSetting($setting, 1)) {
            $success = true;
            $message = 'Successfully forced RG Monitor for ';
        }

        $message .= dfh()::getRgMonitoringActions($setting)['message'];

        return json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }

    public function forceSelfAssessmentTest(Application $app, User $user, Request $request)
    {
        $this->logIntervention($user, $request);
        return json_encode([
            'success' => (bool)$user->repo->setSetting(Action::TAG_FORCE_SELF_ASSESSMENT_TEST, 1),
            'message' => 'User forced to fill self-assessment test.'
        ]);
    }

    public function askRgTools(Application $app, User $user, Request $request)
    {
        $this->logIntervention($user, $request);
        return json_encode([
            'success' => (bool)$user->repo->setSetting('ask-rg-tools', 1),
            'message' => 'Ask RG tools submitted.'
        ]);
    }

    public function contactedViaPhone(Application $app, User $user, Request $request)
    {
        ActionRepository::logAction(
            $user,
            "User has been contacted via telephone.",
            Action::TAG_CALL_TO_USER
        );
        $this->logIntervention($user, $request);
        return json_encode(['success' => true, 'message' => 'Action has been successful.']);
    }

    /**
     * Logs intervention type and cause in actions table
     *
     * @param User $user
     * @param Request $request
     *
     * return void
     */
    private function logIntervention(User $user, Request $request)
    {
        $intervention_type = $request->get('intervention_type');
        $intervention_cause = $request->get('intervention_cause');
        if (
            lic('showInterventionTypes', [], $user->id) &&
            $intervention_type &&
            $intervention_cause
        ) {
            $log_data = implode("|", [
                $intervention_type,
                $intervention_cause
            ]);
            $action = ActionRepository::logAction($user->id, $log_data, 'intervention');
            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', [
                'intervention_done',
                new InterventionHistoryMessage([
                    'id'             => $action->id,
                    'user_id'        => $user->id,
                    'begin_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                    'end_datetime'   => '',
                    'type'           => $intervention_type,
                    'cause'          => $intervention_cause,
                    'event_timestamp'  => Carbon::now()->timestamp
                ])
            ], $user->id);
        }
    }

    public function contactedViaEmail(Application $app, User $user, Request $request)
    {
        ActionRepository::logAction(
            $user,
            "User has been contacted via email.",
            Action::TAG_EMAIL_TO_USER);

        $this->logIntervention($user, $request);
        return json_encode(['success' => true, 'message' => 'Action has been successful.']);
    }

    public function rgReview(Application $app, User $user)
    {
        ActionRepository::logAction(
            $user,
            "Users profile has been reviewed.",
            Action::TAG_REVIEWED);

        return json_encode(['success' => true, 'message' => 'Action has been successful.']);
    }

    public function rgDailyAction(Application $app, User $user)
    {
        ActionRepository::logAction(
            $user,
            "Users marked for Daily action.",
            Action::TAG_DAILY);

        return json_encode(['success' => true, 'message' => 'Action has been successful.']);
    }

    public function rgFollowUpAction(Application $app, User $user)
    {
        ActionRepository::logAction(
            $user,
            "Users marked for follow up action.",
            Action::TAG_FOLLOW_UP);

        return json_encode(['success' => true, 'message' => 'Action has been successful.']);
    }

    public function rgEscalationAction(Application $app, User $user)
    {
        ActionRepository::logAction(
            $user,
            "Users marked for escalation action.",
            Action::TAG_ESCALATION);

        return json_encode(['success' => true, 'message' => 'Action has been successful.']);
    }

    public function rgForceExclusionLock(Application $app, User $user, Request $request)
    {
        $block_repo = new BlockRepository($user);
        $block_repo->populateSettings();

        if (!$block_repo->isSelfExcluded()) {
            $block_repo->selfExclude($app, 183);
            $user->repo->setSetting(Action::TAG_FORCE_SELF_EXCLUSION, 1);
            phive('MailHandler2')->sendMailToEmail(Action::TAG_FORCE_SELF_EXCLUSION, $user->email);
            phive('UserHandler')->logoutUser($user->id, Action::TAG_FORCE_SELF_EXCLUSION);
            $this->logIntervention($user, $request);
            return json_encode(['success' => true, 'message' => 'User forced to set self exclusion lock for 6 months.']);
        } else {
            return json_encode(['success' => false, 'message' => 'User is already self excluded!']);
        }
    }

    /**
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     *
     * @return false|string
     * @throws Exception
     */
    public function manualFlag(Application $app, User $user, Request $request)
    {
        if (empty($flag = $request->get('flag'))) {
            return json_encode(['success' => false, 'message' => 'Flag type is required.']);
        }

        if (empty($description = $request->get('description'))) {
            return json_encode(['success' => false, 'message' => 'Comment is required.']);
        }

        $userObject = cu($user->id);

        if (in_array($flag, $app["play_block_triggers"])) {
            $userObject->playBlock();
        }

        if (in_array($flag, $app["deposit_block_triggers"])) {
            $userObject->depositBlock();
        }

        if (in_array($flag, $app["withdrawal_block_triggers"])) {
            $userObject->withdrawBlock();
        }

        TriggersLog::create([
            'user_id' => $user->id,
            'trigger_name' => $flag,
            'descr' => phive()->ellipsis($description, 255)
        ]);
        $intervention = ActionRepository::logAction($user->id, "set-flag|mixed - Triggered flag {$flag}", 'intervention');

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
                'intervention_done',
                new InterventionHistoryMessage([
                    'id'             => $intervention->id,
                    'user_id'        => $user->id,
                    'begin_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                    'end_datetime'   => '',
                    'type'           => 'set-flag',
                    'flag'           => $flag,
                    'event_timestamp'  => Carbon::now()->timestamp
                ])
            ],
            $user->id
        );

        $this->commentForManualFlagAdded($user, $flag, $description);
        $db_user = cu($user->id);
        lic('triggerGrsRecalculation', [$flag, $db_user], $db_user);

        return json_encode(['success' => true, 'message' => 'User was flagged.']);
    }


    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return false|string
     */
    public function transfer(Application $app, User $user, Request $request)
    {
        $brand = $request->get('brand');

        if (empty($brand)) {
            return json_encode(['success' => false, 'message' => 'Brand is required.']);
        }

        $response = phive('Distributed')->copyUserToBrand($brand, cu($user->id)->getUsername());

        return json_encode(['success' => $response['success'], 'message' => $response['result']]);
    }

    /**
     * Perform the affordability check to BeBettor
     *
     * @param Application $app
     * @param User $user
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function affordabilityCheck(Application $app, User $user)
    {
        $be_bettor = new BeBettor($app, $user);
        $affordability_check_response = $be_bettor->performScreeningCheck($user, 'AFFORDABILITY');

        $response_error = $affordability_check_response['error'];
        if($response_error != false) {
            return $app->json([
                'success' => false,
                'error' => $response_error['code'],
                'validation_errors' => $response_error['validationErrors'] ?? [],
            ], 400);
        }

        $affordability_scores = [
            'A' => '2,000+',
            'B' => '1,001 - 2,000',
            'C' => '751 - 1,000',
            'D' => '501 - 750',
            'E' => '251 - 500',
            'F' => '0 - 250',
        ];

        $brand = phive('Distributed')->getSetting('local_brand');
        $insert_data['user_id'] = $affordability_check_response['customerId'];
        $insert_data['fullname'] = "{$user['firstname']} {$user['lastname']}";
        $insert_data['country'] = $user->country;
        $insert_data['brand'] = $brand;
        $insert_data['requested_at'] = Carbon::now()->format('Y-m-d H:i:s');
        $insert_data['status'] = "{$affordability_check_response['checkResult']['score']}";
        $insert_data['type'] = 'affordability';
        $insert_data['solution_provider'] = 'BeBettor';

        $inserted_data = phive('SQL')->sh($user['id'])->insertArray('responsibility_check', $insert_data);

        if($inserted_data) {
            $score = "{$affordability_check_response['checkResult']['score']} - {$affordability_scores[$affordability_check_response['checkResult']['score']]}";
            return $app->json([
              'success' => true,
              'score' => $score,
              'score_category' => $affordability_check_response['checkResult']['score'],
            ]);
        }

        return $app->json([
          'success' => false,
          'error' => 'UNEXPECTED_ERROR'
        ], 500);
    }

    /**
     * Perform the vulnerability check to BeBettor
     *
     * @param Application $app
     * @param User $user
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function vulnerabilityCheck(Application $app, User $user)
    {
        loadPhive();
        $be_bettor = new BeBettor($app, $user);
        $affordability_check_response = $be_bettor->performScreeningCheck($user, 'VULNERABILITY');

        $response_error = $affordability_check_response['error'];
        if($response_error != false) {
          return $app->json([
            'success' => false,
            'error' => $response_error['code']
          ], 400);
        }

        $brand = phive('Distributed')->getSetting('local_brand');
        $insert_data['user_id'] = $affordability_check_response['customerId'];
        $insert_data['fullname'] = "{$user['firstname']} {$user['lastname']}";
        $insert_data['country'] = $user->country;
        $insert_data['brand'] = $brand;
        $insert_data['requested_at'] = Carbon::now()->format('Y-m-d H:i:s');
        $insert_data['status'] = $affordability_check_response['checkResult']['score'];
        $insert_data['type'] = 'vulnerability';
        $insert_data['solution_provider'] = 'BeBettor';

        $inserted_data = phive('SQL')->sh($user['id'])->insertArray('responsibility_check', $insert_data);

        if($inserted_data) {
          return $app->json([
            'success' => true,
            'score' => $affordability_check_response['checkResult']['score'],
            'flags' => $affordability_check_response['checkResult']['flags'],
            'check_id' => $affordability_check_response['checkId'],
          ]);
        }
        return $app->json([
          'success' => false,
          'error' => 'UNEXPECTED_ERROR'
        ], 500);
    }

    /**
     * @param bool $success
     * @param string $message
     *
     * @return string
     * @throws JsonException
     */
    private function jsonResponse(bool $success, string $message): string
    {
        return json_encode(['success' => $success, 'message' => $message], JSON_THROW_ON_ERROR);
    }

    private function updateUserSettings(array $updatingElements, User $user, string $key, array &$originalValues, array &$changes)
    {
        if (array_key_exists($key, $updatingElements)) {
            $originalSettingValue = $user->repo->getSetting($key);
            $newSettingValue = $updatingElements[$key];

            if ($originalSettingValue != $newSettingValue) {
                $user->repo->setSetting($key, $newSettingValue);
            }

            $originalValues[$key] = $originalSettingValue;
            $changes[$key] = $newSettingValue;
            unset($updatingElements[$key]);
        }
    }

    /**
     * @param string $flag
     *
     * @return bool
     */
    private function hasPermissionToAddManualFlag(string $flag): bool
    {
        if (!p('user.account.flag.manual')) {
            return false;
        }

        switch ($flag) {
            case 'AML60':
                return p('user.account.flag.manual.aml60');
            case 'AML61':
                return p('user.account.flag.manual.aml61');
            default:
                return true;
        }
    }

    private function commentForManualFlagAdded(User $user, string $flag, string $description): void
    {
        switch ($flag) {
            case 'AML60':
                UserCommentRepository::createComment([
                    'user_id' => $user->id,
                    'tag' => 'amlfraud',
                    'comment' => "AML60 Extreme risk player triggered at " . Carbon::now()->toDateTimeString() . ". " . $description
                ]);
                break;
            default:
                UserCommentRepository::createComment([
                    'user_id' => $user->id,
                    'tag' => 'manual-flags',
                    'comment' => $description
                ]);
        }
    }
}
