<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class FixQspinWinsWithoutGameRef extends Seeder
{
    private string $start_date, $end_date, $table;
    private array $games;

    public function init()
    {
        $this->table = "wins";

    }

    public function up()
    {
        $query = <<<SQL
        SELECT wins.id AS win_id, rounds.bet_id AS bet_id, bets.game_ref AS game_ref
        FROM rounds
        JOIN bets ON rounds.bet_id = bets.id
        JOIN wins ON rounds.win_id = wins.id
        WHERE wins.created_at > '2025-04-10 00:00:00'
        AND wins.game_ref = ''
    SQL;

        DB::loopNodes(function ($connection) use ($query) {
            $records = $connection->select($query);

            foreach ($records as $record) {
                 $connection->table($this->table)
                    ->where('id', $record->win_id)
                    ->update(['game_ref' => $record->game_ref]);
            }
        }, false);
    }

}


