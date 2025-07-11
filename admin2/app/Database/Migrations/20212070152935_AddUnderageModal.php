<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddUnderageModal extends Migration
{
    private string $trans_table = 'localized_strings';
    private string $page_table = 'pages';
    private string $boxes_table = 'boxes';

    private array $trans = [
        [
            'alias' => 'verification.box.show.headline',
            'language' => 'en',
            'value' => 'Message'
        ],
        [
            'alias' => 'verification.box.show.verify',
            'language' => 'en',
            'value' => 'Verify'
        ],
        [
            'alias' => 'verification.box.show.html',
            'language' => 'en',
            'value' => '<p>You are required to verify your account by submitting the requested documents.<br />You will need to do this before you can deposit, play and withdraw. <br /> Please contact our Customer Service via live chat or email (<b>support@videoslots.com</b>) if you have any further questions</p>',
        ]
    ];

    private array $page = [
        'alias' => 'rg-verify',
        'filename' => 'diamondbet/mobile.php',
        'cached_path' => '/mobile/rg-verify',
    ];

    /**
     * Do the migration
     */
    public function up()
    {
        foreach($this->trans as $item) {
            $exists = DB::getMasterConnection()
                ->table($this->trans_table)
                ->where('alias', $item['alias'])
                ->where('language', $item['language'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            DB::getMasterConnection()
                ->table($this->trans_table)
                ->insert([$item]);
        }

        $mobile_parent_id = DB::getMasterConnection()
            ->table($this->page_table)->select('page_id')
            ->where('alias', '=', 'mobile')
            ->value('page_id');

        $this->page['parent_id'] = $mobile_parent_id;

        DB::getMasterConnection()
            ->table($this->page_table)
            ->insert([$this->page]);

        $page = DB::getMasterConnection()
            ->table($this->page_table)
            ->where('alias', $this->page['alias'])
            ->first();

        DB::getMasterConnection()
            ->table($this->boxes_table)
            ->insert([
                [
                    'container' => 'full',
                    'priority' => 0,
                    'box_class' => 'MobileGeneralBox',
                    'page_id' => $page->page_id,
                ]
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach($this->trans as $item) {
            DB::getMasterConnection()
                ->table($this->trans_table)
                ->whereIn('alias', $item['alias'])
                ->whereIn('language', $item['language'])
                ->delete();
        }

        $page = DB::getMasterConnection()
            ->table($this->page_table)
            ->where('alias', $this->page['alias'])
            ->first();

        DB::getMasterConnection()
            ->table($this->boxes_table)
            ->where('page_id', $page->page_id)
            ->first();

        DB::getMasterConnection()
            ->table($this->page_table)
            ->where('alias', $this->page['alias'])
            ->delete();
    }
}
