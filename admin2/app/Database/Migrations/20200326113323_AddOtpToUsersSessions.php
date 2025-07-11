<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddOtpToUsersSessions extends Migration
{
    
    protected $table;
    protected $schema;
    
    
    public function init()
    {
        $this->table = 'users_sessions';
        $this->schema = $this->get('schema');
    }
    
    
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->tinyInteger('otp')->default(0)->length(1)->index();
        });
    }
    
    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('otp');
        });
    }
}
