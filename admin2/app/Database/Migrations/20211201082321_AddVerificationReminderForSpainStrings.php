<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddVerificationReminderForSpainStrings extends Migration
{
    private string $table = 'localized_strings';

    private array $items = [
        [
            'alias' => 'acc.verification.documents.reminder.p1.es',
            'language' => 'en',
            'value' => 'You are required to verify your account by submitting requested documents within 30 calendar days.'
        ],
        [
            'alias' => 'acc.verification.documents.reminder.p2.es',
            'language' => 'en',
            'value' => 'Please complete the account verification to avoid any interference in your game play.'
        ],
        [
            'alias' => 'acc.verification.documents.reminder.p1.overtime.es',
            'language' => 'en',
            'value' => 'You are required to verify your account by submitting the requested documents.'
        ],
        [
            'alias' => 'acc.verification.documents.reminder.p2.overtime.es',
            'language' => 'en',
            'value' => 'You will need to do this before you can continue to deposit, withdraw and play. Please contact our Customer Service via live chat or email <b>(support@videoslots.com)</b> if you have any further questions.'
        ]
    ];

    public function up()
    {
        foreach ($this->items as $item) {
            $exists = DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
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
        foreach ($this->items as $item) {
            DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->delete();
        }
    }
}