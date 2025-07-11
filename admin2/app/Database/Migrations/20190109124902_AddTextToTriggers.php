<?php

use Phpmig\Migration\Migration;
use App\Models\Triggers_log;
use App\Extensions\Database\Schema\Blueprint;


class AddTextToTriggers extends Migration
{


    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'triggers_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */

    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'txt')) {
            $this->schema->table($this->table, function (Blueprint $table) {

                $table->asSharded();
                $table->string('txt');
                $table->index('txt', 'txt_descr');
            });
        }
        $triggers = Triggers_log::where('trigger_name', 'RG23')->where('data','!=','');
        $triggers->chunk(100, function ($helper) {
            $helper->each(function ($log) {
                $descr = explode(" ", $log->descr);
                $finger = end(explode(':', $descr[4]));//getting the fingerprint from description
                $log->txt = md5($finger . $log->data);
                $log->save();
            });
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'txt')) {
            $this->schema->table($this->table, function ($table) {
                $table->asSharded();
                $table->dropColumn('txt');
            });

        }
    }
}