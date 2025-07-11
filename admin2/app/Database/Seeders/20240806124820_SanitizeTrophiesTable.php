<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class SanitizeTrophiesTable extends Seeder
{
    private string $table;

    public function init()
    {
        $this->table = 'trophies';
    }

    public function up()
    {
        DB::loopNodes(function(Connection $connection) {
            try {
                $batchSize = 500;
                $offset = 0;

                while (true) {
                    $rows = $connection->table($this->table)
                        ->whereRaw("game_ref REGEXP '[\\s]' OR sub_category REGEXP '[\\s]'")
                        ->limit($batchSize)
                        ->offset($offset)
                        ->get();

                    if($rows->isEmpty()) {
                        break;
                    }

                    $connection->transaction(function() use ($connection, $rows) {
                        foreach ($rows as $row) {
                            $cleanedGameRef = preg_replace('/\s+/', '', $row->game_ref ?? '');
                            $cleanedSubCategory = preg_replace('/\s+/', '', $row->sub_category ?? '');

                            $connection->table($this->table)
                                ->where('id', $row->id)
                                ->update([
                                    'game_ref' => $cleanedGameRef,
                                    'sub_category' => $cleanedSubCategory
                                ]);
                        }
                    });

                    $offset += $batchSize;
                }

            } catch (\Exception $e) {
                echo "\n {$e->getMessage()}";
            }
        });
    }
}
