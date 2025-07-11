<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class FixLostGameRefForPragmatic extends Seeder
{
    private string $start_date, $end_date, $table;
    private array $games;

    public function init()
    {
        $this->start_date = "2025-02-19";
        $this->end_date = "2025-02-27";
        $this->table = "wins";
        $this->games = [
            "pragmatic_vs10fruity2",
            "pragmatic_vs20starlight",
            "pragmatic_vs10txbigbass",
            "pragmatic_vs20doghouse",
            "pragmatic_vswaysmadame",
            "pragmatic_vs10bblotgl",
            "pragmatic_vs25wolfgold",
            "pragmatic_vswaysrhino",
        ];
    }

    public function up()
    {
        DB::loopNodes(function ($connection) {
            $records = $connection
                ->table($this->table)
                ->where('created_at', ">=" , $this->start_date)
                ->where('created_at', "<=" , $this->end_date)
                ->where('game_ref', "")
                ->where('trans_id', "")
                ->where('award_type', 3)
                ->where('bonus_bet', 3)
                ->get();

            foreach ($records as $record) {
                $connection
                    ->table($this->table)
                    ->where('id', $record->id)
                    ->update([
                        'game_ref' => $this->games[array_rand($this->games)],
                    ]);
            }
        }, false);
    }
}
