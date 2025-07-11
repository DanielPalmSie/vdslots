<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use Illuminate\Database\Schema\Blueprint;


class UpdateAML59TriggerDescription extends Seeder
{

    protected string $tableTrigger;
    private Connection $connection;

    private array $triggerData;
    private string $newTriggerDescription;
    protected $schema;
    private $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();

        $this->tableTrigger = 'triggers';
        $this->newTriggerDescription = "Bonus payout ≥€1000 (or local currency equivalent) representing ≥9% of user's 90-day wager total (all configurable)";

        $this->schema = $this->get('schema');

        $this->triggerData = [
            [
                'name' => 'AML59',
                'description' => 'Got a bonus payout >= €1000(configurable), accounting for >= 9%(configurable) of their total wager amount in the last 90 days.',
            ],
        ];

    }

    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | Update AML59 Trigger record
        |--------------------------------------------------------------------------
       */

        $this->init();

       
        // Handle the master adjustment
        foreach ($this->triggerData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableTrigger)
                ->where('name', '=', $data['name'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableTrigger)
                    ->where('name', '=', $data['name'])
                    ->update(['description' => $this->newTriggerDescription]);
            }
        }

        // Handle the shards adjustment
        foreach ($this->triggerData as $data) {
            $this->data = $data;
            if ($this->schema->hasTable($this->tableTrigger)) {
                $this->schema->table($this->tableTrigger, function (Blueprint $table) {
                    $table->asSharded();
                    DB::loopNodes(function (Connection $shardConnection) {
                        $isDataExists = $shardConnection->table($this->tableTrigger)
                            ->where('name', '=', $this->data['name'])
                            ->exists();

                        if ($isDataExists) {
                            $shardConnection
                                ->table($this->tableTrigger)
                                ->where('name', '=', $this->data['name'])
                                ->update(['description' => $this->newTriggerDescription]);
                        }
                    }, false);
                });
            }
        }

    }

    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | Revert AML59 Trigger record
        |--------------------------------------------------------------------------
       */

        $this->init();

        // Handle the master adjustment
        foreach ($this->triggerData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableTrigger)
                ->where('name', '=', $data['name'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableTrigger)
                    ->where('name', '=', $data['name'])
                    ->update(['description' => $data['description']]);
            }
        }

        // Handle the shards adjustment
        foreach ($this->triggerData as $data) {
            $this->data = $data;
            if ($this->schema->hasTable($this->tableTrigger)) {
                $this->schema->table($this->tableTrigger, function (Blueprint $table) {
                    $table->asSharded();
                    DB::loopNodes(function (Connection $shardConnection) {
                        $isDataExists = $shardConnection->table($this->tableTrigger)
                            ->where('name', '=', $this->data['name'])
                            ->exists();

                        if ($isDataExists) {
                            $shardConnection
                                ->table($this->tableTrigger)
                                ->where('name', '=', $this->data['name'])
                                ->update(['description' => $this->data['description']]);
                        }
                    }, false);
                });
            }
        }
    }


}