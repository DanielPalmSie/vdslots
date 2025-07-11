<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 12/11/16
 * Time: 18:24
 */

namespace App\Repositories;

use App\Classes\Wiraya;
use App\Extensions\Database\FManager as DB;
use App\Helpers\Common;
use App\Models\BonusType;
use App\Models\BonusTypeTemplate;
use App\Models\Config;
use App\Models\Currency;
use App\Models\MailerQueueCrm;
use App\Models\MessagingCampaign;
use App\Models\MessagingCampaignTemplates;
use App\Models\MessagingCampaignUsers;
use App\Models\NamedSearch;
use App\Models\SMSQueue;
use App\Models\SMSTemplate;
use App\Models\EmailTemplate;
use App\Models\TrophyAwards;
use App\Models\User;
use App\Models\UserFlag;
use App\Models\Voucher;
use App\Models\VoucherTemplate;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Mandrill;

class MessagingRepository
{
    public $simulation;

    public $data = [];

    /**
     * bonus_limits config tag
     * @var $conf
     */
    private $conf;
    /**
     * currency codes
     * @var $currencies
     */
    private $currencies;

    /**
     * @var array $mailblock_countries
     */
    private $mailblock_countries;

    /**
     * MessagingRepository constructor.
     * @param bool $sim
     */
    public function __construct($sim = false)
    {
        $this->simulation = $sim;
        $this->resetStats();
        $this->setupConfAndCurrencies();

        $this->mailblock_countries = Config::where('config_tag', '=', 'countries')
            ->where('config_name', '=', 'deposit-blocked')
            ->first();
        $this->mailblock_countries = $this->mailblock_countries ? explode(' ', $this->mailblock_countries->config_value) : [];
    }

    /**
     * Resets the count and stats.
     */
    private function resetStats()
    {
        $this->data['count'] = 0;
        $this->data['stats']['fail'] = [
            'users_not_active' => 0,
            'users_sms_off' => 0,
            'users_email_off' => 0,
            'country_not_valid' => 0,
            'bonus_block' => 0,
            'replacer_error' => 0,
            'user_self_excluded' => 0,
            'super_blocked' => 0,
            'play_block' => 0,
            'deposit_block' => 0,
            'under_age_verification_failed' => 0,
            'forgotten' => 0,
            'no_sms_consent' => 0,
            'no_email_consent' => 0,
            'external_marketing_blocked' => 0,
            'user_not_found' => 0,
            'no_interactive_voice_consent' => 0,
            'is_pep_or_sanction' => 0
        ];
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return array|bool
     * @throws \Exception
     */
    public function generateTestCampaign($app, $request, $user)
    {
        $replacer_repo = new ReplacerRepository();
        $fake_campaign_temp = new MessagingCampaignTemplates();

        $res = $data = [];
        $action = $request->get('action');
        foreach ($request->get('form') as $form_element) {
            $data[$form_element['name']] = $form_element['value'];
        }

        if (!empty($request->get('test_list'))) {
            $data['named_search_id'] = $request->get('test_list');
        }

        $fake_campaign_temp->fill($data);

        if ($fake_campaign_temp->isEmail()) {
            $template = EmailTemplate::find($fake_campaign_temp->template_id);
            $msg = $template->html;
        } elseif ($fake_campaign_temp->isSMS()) {
            $template = SMSTemplate::find($fake_campaign_temp->template_id);
            $msg = $template->template;
        } else {
            return $res;
        }
        if (in_array($action, ['username', 'contact', 'show'])) {
            $res['title'] = 'Test send successfully';
            if ($action == 'username') {
                $users_list[] = $user;
                $this->data['is_username'] = true;
            } elseif ($action == 'contact') {
                $users_list = $this->getContactsList($fake_campaign_temp);
            } else {
                $users_list = [];
                if (empty($user) && !empty($fake_campaign_temp->named_search_id)) {
                    $contacts = $this->getContactsList($fake_campaign_temp);
                    if (!empty($contacts)) {
                        $user = User::find(reset($contacts)->id);
                    }
                }
                $res['title'] = 'Test message generated successfully';
            }
            $this->data['sim_contacts'] = $users_list;
            $this->data['env'] = $app['env'];

            if ($fake_campaign_temp->isBonus()) {
                $bonus_template = BonusTypeTemplate::find($fake_campaign_temp->bonus_template_id);
                $reward = !empty($bonus_template->award_id) ? TrophyAwards::find($bonus_template->award_id) : null;
                $fake_bonus = $this->generateBonusFromTemplate($bonus_template);
                $fake_bonus = is_string($fake_bonus) ? null : $fake_bonus;
                $app['monolog']->addError("[TEST-CAMPAIGN] Fake bonus " . json_encode($fake_bonus));
                if (empty($users_list)) {
                    $res['msg'] = $replacer_repo->replaceKeywords($msg, array_merge($replacer_repo->getDefaultReplacers($user), $replacer_repo->getBonusReplacers($fake_bonus, $reward, $user)));
                    $app['monolog']->addError("[TEST-CAMPAIGN] msg result after replace: " . json_encode($res['msg']));
                } else {
                    $this->process($app, $fake_campaign_temp, null, $fake_bonus, $reward);
                    $app['monolog']->addError("[TEST-CAMPAIGN] Data content: " . json_encode($this->data));
                    $res['msg'] = isset($this->data['msg']) ? $this->data['msg'] : "Message sent to {$this->data['count']} contacts.";
                }
            } elseif ($fake_campaign_temp->isVoucher()) {
                /** @var VoucherTemplate $voucher_template */
                $voucher_template = VoucherTemplate::find($fake_campaign_temp->voucher_template_id);
                if (!empty($voucher_template->bonus_type_template_id)) {
                    $fake_bonus = $this->generateBonusFromTemplate($voucher_template->bonusTypeTemplate()->first());
                    $fake_bonus = is_string($fake_bonus) ? null : $fake_bonus;
                    $voucher = $this->createVoucherSeries($voucher_template, $fake_bonus, count($users_list), $test = true, $persist = ($action !== 'show'));
                    if (!is_string($voucher)) {
                        if (empty($users_list)) {
                            $replacers = array_merge($replacer_repo->getDefaultReplacers($user), $replacer_repo->getVoucherReplacers($voucher, null, $fake_bonus, $user));
                            $res['msg'] = $replacer_repo->replaceKeywords($msg, $replacers);
                        } else {
                            $this->process($app, $fake_campaign_temp, null, $fake_bonus, null, $voucher);
                            $res['msg'] = isset($this->data['msg']) ? $this->data['msg'] : "Message sent to {$this->data['count']} contacts.";
                        }
                    }
                } elseif (!empty($voucher_template->trophy_award_id)) {
                    $voucher = $this->createVoucherSeries($voucher_template, null, count($users_list), $test = true, $persist = ($action !== 'show'));
                    if (!is_string($voucher)) {
                        $reward = TrophyAwards::find($voucher_template->trophy_award_id);

                        if (empty($users_list)) {
                            $replacers = array_merge($replacer_repo->getDefaultReplacers($user), $replacer_repo->getVoucherReplacers($voucher, $reward, null, $user));
                            $res['msg'] = $replacer_repo->replaceKeywords($msg, $replacers);
                        } else {
                            $this->process($app, $fake_campaign_temp, null, null, $reward, $voucher);
                        }
                    }
                }
            } else { //Message without promotion
                if (empty($users_list) && $fake_campaign_temp->isSMS() || $action == 'show') {
                    $res['msg'] = $replacer_repo->replaceKeywords($msg, $replacer_repo->getDefaultReplacers($user));
                } else {
                    if (!$fake_campaign_temp->isEmail() && !$fake_campaign_temp->isSMS()) {
                        return $res;
                    }

                    $this->process($app, $fake_campaign_temp, null, null, null, null);
                    $res['msg'] = isset($this->data['msg']) ? $this->data['msg'] : "Message sent to {$this->data['count']} contacts.";
                }
            }
        }

        return $res;
    }

    /**
     * @param BonusTypeTemplate $bonus_template
     * @param bool $persist
     * @return BonusType|string
     */
    public function generateBonusFromTemplate($bonus_template, $persist = true)
    {
        $persist = $persist && !$this->isSimulation();

        try {
            $bonus_template->doReplacers();
        } catch (\Exception $e) {
            if ($persist) {
                return "Bonus template replacers error.";
            }
        }

        $bonus_template = collect($bonus_template)
            ->except(['created_at', 'updated_at', 'id', 'template_name'])
            ->put('excluded_countries', $bonus_template->excluded_countries ?? '')
            ->put('included_countries', $bonus_template->included_countries ?? '')
            ->put('game_id', $bonus_template->game_id ?? '')
            ->put('ext_ids', $bonus_template->ext_ids ?? '')
            ->toArray();

        $bonus_type = new BonusType($bonus_template);

        if ($persist) {
            $bonus_type->save();
        }

        return ($errors = $bonus_type->getLastError()) ? $errors[0] : $bonus_type;
    }

    /**
     * @param VoucherTemplate $template
     * @param BonusType|null $bonus_type
     * @param bool $override_count
     * @param bool $test
     * @param bool $persist
     * @return Voucher|string
     */
    public function createVoucherSeries(VoucherTemplate $template, BonusType $bonus_type = null, $override_count = false, $test = false, $persist = false)
    {
        try {
            $template->doReplacers();
        } catch (\Exception $e) {
            if ($persist) {
                return "Bonus template replacers error.";
            }
        }

        $suffix = $test ? '_TEST' : '';
        $voucher = Voucher::query()
            ->where('voucher_code', '=', $template->voucher_code . $suffix)
            ->first();

        if ($persist and !empty($voucher)) {
            return "The voucher has already been used";
        }

        $voucher = new Voucher([
            'voucher_code' => (!empty($template->voucher_code) ? $template->voucher_code : phive()->randCode(8)) . $suffix,
            'exclusive' => !is_null($template->exclusive) ? $template->exclusive : 1,
            'award_id' => !empty($template->trophy_award_id) ? $template->trophy_award_id : 0,
            'bonus_id' => !empty($bonus_type->id) ? $bonus_type->id : 0,
            'count' => $override_count !== false ? min($template->count, $override_count) : $template->count,

            'requirements' => json_encode([
                'user_on_forums' => $template->user_on_forums,
                'deposit_amount' => $template->deposit_amount,
                'game_operators' => $template->game_operator,
                'deposit_start' => $template->deposit_start,
                'wager_amount' => $template->wagar_amount,
                'wager_start' => $template->wager_start,
                'expire_time' => $template->expire_time,
                'deposit_end' => $template->deposit_end,
                'wager_end' => $template->wager_end,
                'games' => $template->games,
            ]),
        ]);

        if ($persist) {
            $voucher->save();
        }

        return $voucher;
    }

    /**
     * Detect if user is allowed to receive email or sms.
     * * We should never send in these situations:
     *      User Active = 0
     *      Setting self-excluded = 1
     *      users_settings.super-blocked = 1
     *      users_settings.bonus_block = 1
     *      DBUser -> isBlocked
     *      DBUser -> isBonusBlocked
     *      DBUser -> isPlayBlocked
     *      DBUser -> isDepositBlocked
     *
     * @param $user
     * @param $bonus_type
     *
     * @return bool
     * @throws \Exception
     */
    public function allowSend($user, $bonus_type = null)
    {
        /** @var User $user */
        $check_bonus_block = true;

        // make sure that bets and rewards are values from users_lifetime_stats
        if (!is_int($user->bets) or !is_int($user->rewards)) {
            // when the user just registered and he has no users_lifetime_stats, there's no reason to check bonus block
            $check_bonus_block = $user->setupBonusAndRewards() != false;
        }

        // is bonus blocked
        if ($check_bonus_block) {
            $ratio = $user->rewards / $user->bets;
            $wager_thold = $this->conf['wager_thold'] / $this->currencies[$user->currency];

            if (($user->bets < $wager_thold && (float)$this->conf['low_wager_bblock_ratio'] < $ratio) ||
                ($user->bets >= $wager_thold && (float)$this->conf['high_wager_bblock_ratio'] < $ratio)
            ) {
                $this->data['stats']['fail']['bonus_block']++;
                return false;
            }
        }
        // is user active
        if ($user->block_repo->isUserBlocked()) {
            $this->data['stats']['fail']['users_not_active']++;
            return false;
        }
        // is bonus fraud flagged
        if (!empty($bonus_type) && $user->block_repo->isBonusFraudFlagged()) {
            $this->data['stats']['fail']['bonus_block']++;
            return false;
        }
        // is invalid country
        if (!empty($bonus_type) && !$this->isValidCountry($bonus_type, $user->country)) {
            $this->data['stats']['fail']['country_not_valid']++;
            return false;
        }
        // is setting bonus_block
        if ($user->block_repo->isBonusBlocked() == 1) {
            $this->data['stats']['fail']['bonus_block']++;
            return false;
        }
        // is self excluded
        if ($user->block_repo->isSelfExcluded() || $user->block_repo->isExternalSelfExcluded()) {
            $this->data['stats']['fail']['user_self_excluded']++;
            return false;
        }
        // super-blocked
        if ($user->block_repo->isSuperBlocked()) {
            $this->data['stats']['fail']['super_blocked']++;
            return false;
        }
        // is play blocked
        if ($user->block_repo->isPlayBlocked()) {
            $this->data['stats']['fail']['play_block']++;
            return false;
        }
        // is bonus blocked
        if ($user->block_repo->isDepositBlocked()) {
            $this->data['stats']['fail']['deposit_block']++;
            return false;
        }
        // under age verification failed
        if ($user->country == 'GB' and !$user->block_repo->underAgeVerificationPassed()) {
            $this->data['stats']['fail']['under_age_verification_failed']++;
            return false;
        }

        if ($user->block_repo->isPepOrSanction()) {
            $this->data['stats']['fail']['is_pep_or_sanction']++;
            return false;
        }

        // is user forgotten
        if ($user->isForgotten()) {
            $this->data['stats']['fail']['forgotten']++;
            return false;
        }

        return true;
    }

    /**
     * setup bonus_limits config tag
     * cache the currency codes
     */
    private function setupConfAndCurrencies()
    {
        $this->conf = Config::query()->where('config_tag', 'bonus_limits')->get()
            ->reduce(function ($carry, $element) {
                $carry[$element->config_name] = $element->config_value;
                return $carry;
            }, []);

        $this->currencies = Currency::query()->get()->keyBy('code');
    }

    /**
     * @param array $users_ids // array of user ids
     * @param null $bonus_type
     * @return Collection
     */
    public function basicAllowSendToUsers($users_ids, $bonus_type = null)
    {
        $contacts = collect();
        foreach (array_chunk($users_ids, 1000) as $users_ids_chunk) {
            $contacts = User::query()
                ->whereIn("users.id", $users_ids_chunk)
                ->leftJoin('users_lifetime_stats', 'users_lifetime_stats.user_id', '=', 'users.id')
                ->selectRaw('users.*, users_lifetime_stats.bets, users_lifetime_stats.rewards')
                ->get()
                ->tap(function($contacts) {
                    /** @var Collection $contacts */
                    $this->data['count'] += $contacts->count();
                })
                ->map(function($user) {
                    /** @var User $user */
                    $user = $user->populateSettings();
                    // cache the users_settings to be used in filterMarketingBlockedUsers
                    $user->settings = (array)$user->block_repo->settings;
                    return $user;
                })
                ->filter(function($user) use ($bonus_type) {
                    /** @var User $user */
                    return $this->allowSend($user, $bonus_type);
                })
                ->pipe(function($users) {
                    return $this->filterMarketingBlockedUsers($users);
                })
                ->merge($contacts);
        }
        return $contacts;
    }

    /**
     * @param mixed $app
     * @param MessagingCampaignTemplates $campaign_template
     * @param MessagingCampaign|null $campaign
     * @param BonusType|null $bonus_type
     * @param TrophyAwards|null $reward
     * @param array|null $voucher
     * @throws \Exception
     */
    public function process($app, $campaign_template, $campaign = null, $bonus_type = null, $reward = null, $voucher = null)
    {
        Common::dumpTbl("processRepo", $campaign_template->id);
        Common::dumpTbl("isSimulation", $this->isSimulation());
        Common::dumpTbl("isSMS", $campaign_template->isSMS());
        $this->resetStats();
        $replacer_repo = new ReplacerRepository();

        /** @var EmailTemplate|SMSTemplate $template */
        $template = $campaign_template->template()->first();

        $message['body'] = '';
        if ($campaign_template->isSMS()) {
            $message['body'] = $campaign_template->smsTemplate()->first()->template;
        } elseif ($campaign_template->isEmail()) {
            $message['body'] = $template->html;
            $message['subject'] = $template->subject;
        }

        $campaign_template_type = $campaign_template->getTemplateType();

        if (!$this->isSimulation()) {
            $contact_list = $this->getContactsList($campaign_template);
            $campaign->contacts_count = count($contact_list);

            $contact_list = $this->basicAllowSendToUsers(collect($contact_list)->pluck('id')->toArray())
            ->filter(function ($user) use ($campaign_template_type, $template) {
                if ($template && !$user->block_repo->hasConsentFor($template->getRequiredConsent(), $campaign_template_type)) {
                    $this->data['stats']['fail']["no_{$campaign_template_type}_consent"]++;
                    return false;
                }
                return true;
            });
        } else {
            $users_ids = collect($this->data['sim_contacts'])->pluck('id')->toArray();
            $contact_list = User::query()->whereIn("users.id", $users_ids)->get();
        }

        foreach ($contact_list as $user) {
            /** @var User $user */
            if ($campaign_template->isBonus()) {
                if (!$this->isSimulation()) {
                    UserFlag::create(['user_id' => $user->id, 'flag' => 'bonus-' . $bonus_type->id]);
                }
                $replacers = array_merge($replacer_repo->getDefaultReplacers($user), $replacer_repo->getBonusReplacers($bonus_type, $reward, $user));
            } elseif ($campaign_template->isVoucher()) {
                $replacers = array_merge($replacer_repo->getDefaultReplacers($user), $replacer_repo->getVoucherReplacers($voucher, $reward, $bonus_type, $user));
            } else {
                $replacers = $replacer_repo->getDefaultReplacers($user);
            }

            $required_replacers = array_merge(
                $replacer_repo->getRequiredReplacers($message['body'], $replacers),
                $replacer_repo->getRequiredReplacers($message['subject'], $replacers)
            );
            $msg = $message['body'];
            $subject = $message['subject'];

            if ($campaign_template->isSMS() && $this->checkUserForSms($user)) {
                if (empty($msg)) {
                    if (!$this->isSimulation()) {
                        $this->data['stats']['fail']['replacer_error']++;
                        $app['monolog']->addError("SMS promotion not sent. Message not generated due to a replacer error", [
                            'campaign_id' => $campaign->id,
                            'user_id' => $user->id,

                        ]);
                        ActionRepository::logAction($user, "SMS promotion not sent. Message not generated due to a replacer error.", 'messaging-not-send-sms', false, false, true);
                    }
                } else {
                    if ($this->sendSMS($app, $user->id, $msg, $campaign, $required_replacers)) {
                        $this->data['msg'] = $msg;
                        if (!$this->isSimulation()) {
                            $app['monolog']->addInfo("Campaign with id: {$campaign->id} sms sent.", [
                                'campaign_id' => $campaign->id,
                                'user_id' => $user->id
                            ]);
                            ActionRepository::logAction($user, "Campaign with id: {$campaign->id} sms sent.", 'messaging-sms', false, false, true);
                        }
                        if ($campaign_template->isVoucher()) {
                            ActionRepository::logAction($user, $voucher->voucher_code, "voucher", false, false, true);
                        }
                    }
                }
            } elseif ($campaign_template->isEmail() && $this->checkUserForEmail($user) && $this->validateEmail($message)) {
                if (empty($msg)) {
                    if (!$this->isSimulation()) {
                        $this->data['stats']['fail']['replacer_error']++;
                        $app['monolog']->addError("Promotion not sent. Message not generated due to a replacer error", [
                            'campaign_id' => $campaign->id,
                            'user_id' => $user->id
                        ]);
                        ActionRepository::logAction($user, "Promotion not sent. Message not generated due to a replacer error.", 'messaging-not-send-email', false, false, true);
                    }
                } else {
                    if ($this->sendMail($app, $user, $msg, $subject, $campaign, $campaign_template->is_newsletter, $required_replacers)) {
                        $this->data['msg'] = $msg;
                        if (!$this->isSimulation()) {
                            $app['monolog']->addInfo("Campaign with id: {$campaign->id} email sent.", [
                                'campaign_id' => $campaign->id,
                                'user_id' => $user->id
                            ]);
                            ActionRepository::logAction($user, "Campaign with id: {$campaign->id} email sent.", 'messaging-email', false, false, true);
                        }
                        if ($campaign_template->isVoucher()) {
                            ActionRepository::logAction($user, $voucher->voucher_code, "voucher", false, false, true);
                        }
                    }
                }
            }
        }

        if (!$this->isSimulation()) {
            if (!empty($this->data['links'])) {
                MessagingCampaignUsers::bulkInsert(
                    array_map(function ($link) use ($template, $campaign_template) {
                        $link['template_name'] = $template->template_name;
                        $link['template_id'] = $template->id;
                        $link['template_type'] = $campaign_template->template_type;
                        return $link;
                    }, $this->data['links'])
                );
            }

            $campaign->status = MessagingCampaign::STATUS_SENT;
            $campaign->sent_time = Carbon::now()->toDateTimeString();
            $campaign->sent_count = $this->data['count'];
            $campaign->stats = json_encode($this->data['stats']);
            $campaign->contacts_list_name = $campaign_template->namedSearch()->first()->name;

            $campaign->save();

            $app['monolog']->addNotice("Campaign with id: {$campaign->id} finished process ", [
                'campaign_id' => $campaign->id,
                'sent_time' => $campaign->sent_time,
                'stats' => $this->data['stats'],
                'contacts_list_name' => $campaign->contacts_list_name
            ]);
        }
    }

    /**
     * @param MessagingCampaign $campaign
     * @param integer $user_id
     */
    private function messageSent(MessagingCampaign $campaign, $user_id)
    {
        $this->data['links'][] = ['user_id' => $user_id, 'campaign_id' => $campaign->id];
    }

    /**
     * @param Application $app
     * @param int $user_id
     * @param string $msg
     * @param MessagingCampaign $campaign
     * @return bool
     */
    private function sendSMS($app, $user_id, $msg, $campaign, $replacers = [])
    {
        $replacer_repo = new ReplacerRepository();
        $msg = $replacer_repo->replaceKeywords($msg, $replacers);
        Common::dumpTbl("updatedSMS",  $msg);

        if (!$this->isSimulation()) {
            SMSQueue::create(['user_id' => $user_id, 'msg' => $msg, 'created_at' => Carbon::now(), 'messaging_campaign_id' => $campaign->id]);
            $this->messageSent($campaign, $user_id);
        } else {
            $user = phive('UserHandler')->getUser($user_id);
            if (is_object($user)) {
                $res = phive('Mosms')->sendSms($user, $msg);
                $app['monolog']->addError("Res on sendSms: " . $res == true ? 'true' : $res == false ? 'false' : 'null');
            }
        }
        $this->data['count']++;
        return true;
    }

    /**
     * Get email and name.
     * @param $app
     * @param $is_newsletter
     * @return array
     */
    private function getFrom($app, $is_newsletter)
    {
        $from = [
            'email' => null,
            'name' => null
        ];
        if ($this->isSimulation()) {
            $from['email'] = $app['messaging']['test_from_email'];
        } else {
            if ($is_newsletter) {
                $from['email'] = $app['messaging']['default_from_email'];
                $from['name'] = $app['messaging']['default_from_email_name'];
            } else {
                $from['email'] = $app['messaging']['transactional_from_email'];
                $from['name'] = $app['messaging']['transactional_from_email_name'];
            }
        }

        $from['name'] = empty($from['name']) ? null : $from['name'];

        return $from;
    }

    /**
     * Send Email
     * TODO check this one
     * @param Application $app
     * @param User $user
     * @param string $msg
     * @param MessagingCampaign $campaign
     * @return boolean
     */
    private function sendMail($app, $user, $msg, $subject, $campaign, $is_newsletter = true, $replacers)
    {
        if (empty($replacers) || !is_array($replacers)) {
            $replacers = [];
        }
        $to = $user->email;
        $from = $this->getFrom($app, $is_newsletter);

        $msgText = html_entity_decode(strip_tags($msg), ENT_QUOTES, "UTF-8");

        $msg .= phive('MailHandler2')->getUnsubExtra($to);
        $msgHtml = strtr($msg, array("€" => "&euro;"));

        $reply_to = $app['messaging']['default_reply_to'];

        if (!$this->isSimulation()) {
            $queue = new MailerQueueCrm();
            $queue->user_id = $user->id;
            $queue->messaging_campaign_id = $campaign->getKey();
            $queue->from = $from['email'];
            $queue->from_name = $from['name'];
            $queue->to = $to;
            $queue->reply_to = $reply_to;
            $queue->subject = $subject;
            $queue->html = $msgHtml;
            $queue->text = $msgText;
            $queue->tag = '';
            $queue->important = false;
            $queue->track_events = true;
            $queue->replacers = json_encode($replacers);
            $queue->save();

            $this->messageSent($campaign, $user->id);
        } else {
            $mail = [
                'from_email' => $from['email'],
                'from_name' => $from['name'],
                'to' => [[
                    'email' => $to,
                ]],
                'headers' => [
                    'Reply-To' => $reply_to,
                ],
                'subject' => $subject,
                'html' => $msgHtml,
                'text' => $msgText,
                'important' => true,
            ];

            $mailer = new Mandrill(env('MANDRILL_API_KEY'));

            $mailer->messages->send($mail, $async = true);
        }
        $this->data['count']++;
        return true;
    }

    /**
     * Check if valid email
     * @param $message
     * @return bool
     */
    private function validateEmail($message)
    {
        return empty($message['body']) || empty($message['subject']) ? false : true;
    }

    /**
     * Detect if country is valid
     * @param BonusType $bonus
     * @param $user_country
     * @return bool
     */
    private function isValidCountry(BonusType $bonus, $user_country)
    {
        return $this->isSimulation()
            ? true
            : $bonus->isCountryIncluded($user_country) and !$bonus->isCountryExcluded($user_country);
    }

    /**
     * @param User $user
     * @return bool
     */
    private function isMailBlocked($user)
    {
        return in_array($user->country, $this->mailblock_countries);
    }

    /**
     * Check if user has a verified phone
     * @param User $user
     * @return boolean
     */
    private function checkUserForSms($user)
    {

        if (!$this->isSimulation() and (intval($user->settings_repo->settings->{'privacy-main-promo-sms'}) == 0 or empty($user->settings_repo->settings->{'privacy-main-promo-sms'}))) {
            $this->data['stats']['fail']['users_sms_off']++;
            return false;
        }

        return true;
    }

    /**
     * @param User $user
     * @return boolean
     */
    private function checkUserForEmail($user)
    {
        if (!$this->isSimulation() and $this->isMailBlocked($user)) {
            $this->data['stats']['fail']['user_email_off']++;
            return false;
        }
        return true;
    }

    /**
     * Applies the sql query statement of a single named search
     * @param MessagingCampaignTemplates $campaign_template
     * @return array
     */
    public function getContactsList(MessagingCampaignTemplates $campaign_template)
    {
        return DB::shsSelect('users', $campaign_template->namedSearch()->first()->sql_statement);
    }

    /**
     * Detect if running in simulation mode
     * @param bool $save_promos
     * @return bool
     */
    public function isSimulation($save_promos = false)
    {
        return $save_promos
            ? isset($this->data['env']) && $this->data['env'] == 'test'
            : $this->simulation !== false;
    }

    /**
     * Get the list of past campaigns
     * @param null|string|number $type
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|static[]
     */
    public static function getPastCampaignsList($type = null)
    {
        /** @var Builder|\Illuminate\Database\Eloquent\Builder $query */
        $query = MessagingCampaign::query();
        $query->selectRaw(implode(', ', ['messaging_campaigns.*', 'bonus_types.bonus_name', 'named_searches.id as named_search_id', 'named_searches.name as named_search_name', 'ifnull(email_templates.template_name, sms_templates.template_name) as template_name']))
            ->leftJoin('bonus_types', 'bonus_types.id', '=', 'messaging_campaigns.bonus_id')
            ->leftJoin('messaging_campaign_templates', 'messaging_campaign_templates.id', '=', 'messaging_campaigns.campaign_template_id')
            ->leftJoin('named_searches', 'named_searches.id', '=', 'messaging_campaign_templates.named_search_id')
            ->leftJoin('email_templates', function($q) {
                return $q->on('email_templates.id', '=', 'messaging_campaign_templates.template_id')
                    ->where('messaging_campaign_templates.template_type', '=', MessagingCampaignTemplates::TYPE_EMAIL);
            })
            ->leftJoin('sms_templates', function ($q) {
                return $q->on( 'sms_templates.id', '=', 'messaging_campaign_templates.template_id')
                    ->where('messaging_campaign_templates.template_type', '=', MessagingCampaignTemplates::TYPE_SMS);
            });

        if (!is_null($type)) {
            $query->where('messaging_campaigns.type', $type);
        }

        return $query->get();
    }

    /**
     * Returns list of users who can be contacted
     *
     * @param Collection|User[] $contacts
     * @return Collection
     */
    public function filterMarketingBlockedUsers($contacts)
    {
        $list = $contacts->map(function($contact) {
            return (array)$contact->getAttributes();
        })->toArray();

        $marketing_blocked_users = lics('getMarketingBlockedUsers', [$list]);
        $marketing_blocked_users = array_filter(array_flatten($marketing_blocked_users, false));

        return $contacts->filter(function($contact) use ($marketing_blocked_users) {
            if (in_array($contact->id, $marketing_blocked_users)) {
                $this->data['stats']['fail']['external_marketing_blocked'] += 1;
                return false;
            }
            return true;
        });
    }

    /**
     * Note: exceptions will be caught in NightlyCommand.
     *
     * @param $app
     * @param MessagingCampaign $campaign
     * @param $named_search_id
     * @param $wiraya_project_id
     * @param $language
     * @throws \GuzzleHttp\Exception\GuzzleException|\Exception
     */
    public function sendWirayaContacts($app, $campaign, $named_search_id, $wiraya_project_id, $language)
    {
        // only here $this->data['count'] will contain the number of all users in the list
        $this->resetStats();
        $this->data['stats']['wiraya_config'] = [$language, $named_search_id, $wiraya_project_id];

        $named_search = NamedSearch::query()->find($named_search_id);
        if (empty($named_search)) {
            throw new \Exception("Sql statement is empty for list with id: {$named_search_id}");
        }

        $contacts = DB::shsSelect('users', $named_search->sql_statement);

        $contacts = $this->basicAllowSendToUsers(collect($contacts)->pluck('id')->toArray())
            ->filter(function($contact) {
                /** @var User $contact */
                if (!$contact->block_repo->hasConsentFor("interactive", "voice", "bonus")) {
                    $this->data['stats']['fail']['no_interactive_voice_consent'] += 1;
                    return false;
                }
                return true;
            })
            ->mapWithKeys(function ($contact) {
                return [
                    $contact->id => $this->prepareContactForWiraya($contact)
                ];
            });

        if ($contacts->count() > 0) {
            $wiraya = new Wiraya($app);
            $wiraya->auth();
            $wiraya->registerUsers($contacts->all());
            $wiraya->setUsersOnCampaign($contacts->keys(), $wiraya_project_id);
            // set all the keys to empty string to prevent null value errors
            $data = $contacts->keys()->map(function($user_id) use ($campaign) {
                return [
                    "user_id" => $user_id,
                    "campaign_id" => $campaign->id,
                    "message_id" => "",
                    "status" => "",
                    "template_id" => "",
                    "template_name" => "",
                    "template_type" => "",
                    "subject" => "",
                    "html" => "",
                    "text" => "",
                    "smtp_events" => "",
                    "resends" => "",
                    "reject" => ""
                ];
            });
            MessagingCampaignUsers::bulkInsert($data->toArray());
        }

        $campaign->sent_time = Carbon::now()->toDateTimeString();
        $campaign->status = MessagingCampaign::STATUS_SENT;
        $campaign->contacts_count = $this->data['count'];
        $campaign->sent_count = $contacts->count();
        $campaign->stats = json_encode($this->data['stats']);
        $campaign->contacts_list_name = $named_search->name;
        $campaign->save();
    }
    private function formatGBPhoneNumber($phoneNumber) {
        if (substr($phoneNumber, 0, 2) === '10') {
            // Remove the leading '1' and add '44'
            return '44' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 1) === '7') {
            // Add '44' in front
            return '44' . $phoneNumber;
        } else {
            return $phoneNumber;
        }
    }
    private function prepareContactForWiraya($contact) {

        $phoneNumber = $contact->mobile;
        if ($contact->country === 'GB') {
            $phoneNumber = $this->formatGBPhoneNumber($phoneNumber);
        }
        return [
            "personal" => [
                "surName" => $contact->lastname,
                "givenName" => $contact->firstname,
                "phoneNumber" => $phoneNumber,
                "username" => $contact->username
            ],
            "general" => [
                "country" => $contact->country
            ]
        ];
    }
}
