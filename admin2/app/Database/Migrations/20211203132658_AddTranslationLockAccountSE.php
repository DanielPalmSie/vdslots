<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddTranslationLockAccountSE extends Migration
{
    private string $table = 'localized_strings';
    private array $data = [
        'en' => [
            'lock.accountse.info.html' => '<p style="text-align: justify;">Below you can lock your account for a specific amount of days.</p>
<p style="text-align: justify;">If you want to unlock your account. The change will automatically take place after a 3 day cooling off period. If you wish to self-exclude, please contact support@videoslots.com or scroll down below to &ldquo;Exclude now&rdquo;</p>
<p style="text-align: justify;">If you want to permanently lock your account, please contact support@videoslots.com. If you permanently lock your account, you will never be able to access it again under any circumstances.</p>
<p style="text-align: justify;">For how long do you want to lock your account?</p>',
        ],
        'sv' => [
            'lock.accountse.info.html' => '<p>Nedan kan du l&aring;sa ditt konto i ett specifikt antal dagar.</p>
<p>Om du vill l&aring;sa upp ditt konto kommer &auml;ndringen att ske efter en paus period p&aring; 3 dagar. Om du &ouml;nskar att sj&auml;lvutesluta ditt konto, s&aring; v&auml;nligen kontakta support@videoslots.com eller skrolla ner till "L&aring;s" och g&ouml;r det direkt d&auml;r.</p>
<p><em>Om du vill l&aring;sa ditt konto permanent, kontakta d&aring; support@videoslots.com. L&aring;ser du ditt konto permanent s&aring; kommer kommer det att f&ouml;rbli l&aring;st och &ouml;ppnas inte under n&aring;gra som helst omst&auml;ndigheter.</em></p>
<p>Hur l&auml;nge vill du l&aring;sa ditt konto?</p>',
        ]
    ];


    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exists = DB::getMasterConnection()
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
                    continue;
                }

                DB::getMasterConnection()
                    ->table($this->table)
                    ->insert([
                        [
                            'alias' => $alias,
                            'language' => $language,
                            'value' => $value,
                        ]
                    ]);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                DB::getMasterConnection()
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }
}
