<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddInfluencedByCoulumnToRiskProfileRatingLog extends Migration
{
    private string $table;
    /**
     * @var mixed
     */
    private $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->json('influenced_by')->nullable();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->dropColumn('influenced_by');
        });
    }
}
