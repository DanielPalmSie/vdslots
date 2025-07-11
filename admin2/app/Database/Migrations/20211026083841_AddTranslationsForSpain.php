<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddTranslationsForSpain extends Migration
{
    private string $table = 'localized_strings';
    private array $insert_data = [
        'en' => [
            // Opening new game session in less than 60 minutes of previous session
            'rg.info.game-session-limit.before-sixty-minutes' => 'The Previous Game Session ended less than 60 minutes ago. If you wish to proceed to the New Game Session, press the \"Continue\" button below',
            'rg.info.game-session-limit.safety-concerns' => 'Your safety is important to us. If you want more help, you can complete our {{self_assessment_test}} or consult our page dedicated to {{responsible_gambling}}',
            'rg.info.game-session-limit.self.assessment' => 'Self Assessment Test',
            'rg.info.game-session-limit.self.responsible_gambling' => 'Responsible Gambling',
            'rg.info.game-session-responsible-gambling-notice.set.title' => 'Responsible Gambling',
            // Game Session Limit Reached Popup
            'game.session.limit.reached' => 'Game Session Limit Reached',
            'rg.info.game-session-limit.reached.description-spend' => 'You have now reached your spend limit.',
            'rg.info.game-session-limit.reached.description-time' => 'You have now reached your time limit.',
            'amount.wagered' => 'Amount Wagered:',
            'amount.won' => 'Amount Won:',
            'net.result' => 'Net Result:',
            // Game Session Limit Reminder
            'time.left' => 'Time Left:',
            'time.limit.set' => 'Time Limit Set:',
            'spend.left' => 'Spend Left:',
            'spend.limit.set' => 'Spend Limit Set:',
            'continue.playing' => 'Continue Playing',
            'limit.reminder' => 'Limit Reminder',
            // Set Game Session Limit Popup (GP covered: NetEnt, PlayNgo)
            'rg.info.game-session-limit.title' => 'Game Session Limit',
            'game.limit' => 'Game Limit',
            'spend.limit' => 'Spend Limit',
            'set.reminder' => 'Set Reminder',
            'restrict.future.sessions' => 'Restrict Future Sessions',
            'rg.info.game-session-limit.time-limit-reached-error' => 'Game limit is required and need to be greater than 0',
            'rg.info.game-session-balance.over-limit' => 'Session balance is required',
            'set-session-set-reminder-error' => 'The Set reminder field is required and need to be greater than 0',
            'game-session-restriction.future-session-timeout' => 'You will not be able to play for {{time_left}} more minutes, as you defined on your last game session',
            'rg.info.game-session-balance.over-balance' => 'Spend Limit can not be greater than the balance on your account',
            'set-session-restrict-future-session-error' => 'If you choose to restrict future sessions you need to insert a value greater than 0.',
            'set-session-create-session-generic-error' => 'Something went wrong while setting your game limits, please try again, if the issue persist contact support.',
            // Temporary game session restriction
            'selected.restricted.time' => 'Selected Restricted Time',
            'time.remaining' => 'Time Remaining',
            'temporarily.restrictions' => 'Temporarily Restricted',
            'rg.info.game-session-limit.temporarily.restrictions.description' => 'You are temporarily unable to open a new game session. Due to your own request, having reached any of the established previous set limits.',
            // Reminder max every 15min
            'set-session-set-reminder-max-length-error' => 'There is a maximum allowance of 15 minutes',
            'set-session-set-reminder-greater-than-limit' => 'Reminder cannot be greater than the limit',
            // Game Session Limits About to Be Reached Popup (certification)
            'about.to.reach.limits' => 'You are soon about to reach a pre-selected limit',
            // prevent 0 as spend limit
            'rg.info.game-session-balance.insufficient' => 'Insufficient amount, Spend Limit should be more than 0',
            'message' => 'Message',
            // Disallow multiple games
            'game.session.activity' => 'Game Session Activity',
            'rg.game-session-terminated.description-1' => 'You have another game session open. ',
            'rg.game-session-terminated.description-2' => 'To play a new game, please end your already open session. This can be done by selecting \'New game session\' button to proceed with this new game session. ',
            'rg.game-session-terminated.description-3' => 'To continue with your open game session, press \'OK\' button. ',
            'new.game.session' => 'New Game Session',
            'closed.by.new.session' => 'This game session was closed as you started a new one in another page.',
            'session.balance' => 'Session Balance',
            // RG Limits
            'rg.info.limits.select' => 'Select limit',
            'rg.info.limits.type' => 'Type limit',
            'rg.info.limits.reminders.15_minutes_max' => '15 min maximum',
            'rg.info.limits.restricts.future_sessions' => 'Restrict Future Sessions',
            'rg.info.limits.restricts.not_be_restricted' => 'I do not want to be restricted',
            'hour' => 'hour',
        ],
        'es' => [
            'rg.info.limits.select' => 'Seleccione el límite',
            'rg.info.limits.type' => 'Tipo de limite',
            'rg.info.limits.reminders.15_minutes_max' => '15 minutos máximo',
            'rg.info.limits.restricts.future_sessions' => 'Restringir las sesiones futuras',
            'rg.info.limits.restricts.not_be_restricted' => 'No quiero ser limitado',
            'hour' => 'hora',
        ]
    ];

    public function up()
    {
        [$data] = $this->extractData();

        // Checking entries 1 by 1 to prevent overcomplicating
        // performance issues can be ignored because this will be run only once
        foreach ($data as $item) {
            $exists = DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item[ 'alias'])
                ->where('language', $item[ 'language'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            DB::getMasterConnection()
                ->table($this->table)
                ->insert([$item]);
        }
    }

    public function down()
    {
        [, $languages, $aliases] = $this->extractData();

        DB::getMasterConnection()
            ->table($this->table)
            ->whereIn('alias', $aliases)
            ->whereIn('language', $languages)
            ->delete();
    }

    private function extractData(): array
    {
        $to_insert = [];
        $languages = [];
        $aliases = [];

        foreach ($this->insert_data as $language => $values) {
            $languages[] = $language;
            foreach ($values as $alias => $value) {
                $aliases[] = $alias;
                $to_insert[] = [
                    'alias' => $alias,
                    'language' => $language,
                    'value' => $value
                ];
            }
        }

        return [$to_insert, $languages, $aliases];
    }
}
