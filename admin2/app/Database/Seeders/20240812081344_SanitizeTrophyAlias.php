<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class SanitizeTrophyAlias extends Seeder
{
    private Connection $connection;

    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $batchSize = 500;
        $offset = 0;

        while (true) {
            $rows = $this->connection->table($this->table)
                ->where('alias', 'like', 'trophy.%.headline')
                ->limit($batchSize)
                ->offset($offset)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $cleaned_alias = preg_replace('/\s+/', '', $row->alias);

                if ($cleaned_alias !== $row->alias) {
                    $exists = $this->connection->table($this->table)
                        ->where('alias', $cleaned_alias)
                        ->where('language', $row->language)
                        ->exists();

                    if (!$exists) {
                        $this->connection->table($this->table)
                            ->where('alias', $row->alias)
                            ->where('language', $row->language)
                            ->where('value', $row->value)
                            ->update(['alias' => $cleaned_alias]);
                    } else {
                        echo "Skipping update for alias '{$row->alias}' as cleaned alias '{$cleaned_alias}' already exists.\n";
                    }
                }
            }

            $offset += $batchSize;
        }
    }
}
