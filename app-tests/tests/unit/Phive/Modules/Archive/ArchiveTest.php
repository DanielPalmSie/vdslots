<?php

namespace Tests\Unit\Phive\Modules\Archive;

use PHPUnit\Framework\TestCase;
use Tests\Unit\Phive\Modules\Archive\TablesManager;

// php vendor/bin/codecept run --skip-group=complicated unit Phive/Modules/Archive
final class ArchiveTest extends TestCase
{
    public array $validation_rules = [
        "2022-10-09" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 0, "primary" => 10],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 0, "primary" => 10],
                "master" => ["archive" => 0, "primary" => 10],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ],
        "2022-10-10" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 1, "primary" => 9],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 1, "primary" => 9],
                "master" => ["archive" => 1, "primary" => 9],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ],
        "2022-10-11" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 2, "primary" => 8],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 2, "primary" => 8],
                "master" => ["archive" => 2, "primary" => 8],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ],
        "2022-10-15" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 6, "primary" => 4],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 6, "primary" => 4],
                "master" => ["archive" => 6, "primary" => 4],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ],
        "2022-10-19" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 10, "primary" => 0],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 10, "primary" => 0],
                "master" => ["archive" => 10, "primary" => 0],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ],
        "2022-10-20" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 10, "primary" => 0],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 10, "primary" => 0],
                "master" => ["archive" => 10, "primary" => 0],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ],
        "2022-10-25" => [
            "bets,bets_mp,wins,wins_mp,tournament_entries" => [
                "nodes" => ["archive" => 10, "primary" => 0],
                "master" => ["archive" => 0, "primary" => 0],
            ],
            "tournaments" => [
                "nodes" => ["archive" => 10, "primary" => 0],
                "master" => ["archive" => 10, "primary" => 0],
            ],
            "micro_games" => [
                "nodes" => ["archive" => 10, "primary" => 10],
                "master" => ["archive" => 10, "primary" => 10],
            ],
        ]
    ];

    public function testNormalArchivingProcess(): void
    {
        // DONE: test normal execution instead of repopulating primary
        // DONE: test schema change on micro_games global tables
        // DONE: test schema change on micro_games table
        // todo-dip: test schema change on tournaments global table
        // todo-dip: test schema change on sharded table
        $this->executeTests($this->validation_rules);
    }

    public function testRepopulatedTables(): void
    {
        $this->executeTests($this->validation_rules, null, true);
    }

    public function testSchemaChange(): void
    {
        $validation_rules = [
            '2022-10-10' => $this->validation_rules['2022-10-10'],
            '2022-10-11' => $this->validation_rules['2022-10-11'],
            '2022-10-15' => $this->validation_rules['2022-10-15'],
        ];
        $this->executeTests($validation_rules, '2022-10-11'); // ok
    }

    private function validateArchiving(string $table, int $primary_total, int $archive_total, bool $is_master = false): void
    {
        $nodes = $is_master ? [-1] : [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

        foreach ($nodes as $node) {
            $primary = $node === -1 ? phive('SQL')->onlyMaster() : phive('SQL')->doDb('shards', $node);
            $archive = $node === -1 ? phive('SQL')->doDb('archive') : phive('SQL')->doDb('shard_archives', $node);

            $res = [
                $primary->getValue("select count(*) as total from {$table};"),
                $archive->getValue("select count(*) as total from {$table};"),
            ];
            foreach ($res as $index => $value) {
                if ($value === false) {
                    $res[$index] = 0;
                } else {
                    $res[$index] = (int)$value;
                }
            }

            [$p, $a] = $res;

            $this->assertSame($p, $primary_total, "PRIMARY: node {$node}, {$table}, expected:{$primary_total}, got:{$p}");
            $this->assertSame($a, $archive_total, "ARCHIVE: node {$node}, {$table}, expected:{$archive_total}, got:{$a}");
        }
    }

    public function executeTests(array $validation, string $schema_change_date = null, bool $repopulate_tales = false): void
    {
        self::resetDatabase(true);

        foreach ($validation as $day => $tables) {
            phive('Logger')->getLogger('archive')->info("Doing day: {$day}\n");

            phive('SQL/Archive')->execute([$day, "actions=$day"]);

            foreach ($tables as $table_list => $conditions) {
                foreach (explode(',', $table_list) as $table) {
                    $this->validateArchiving($table, $conditions['master']['primary'], $conditions['master']['archive'], true);
                    $this->validateArchiving($table, $conditions['nodes']['primary'], $conditions['nodes']['archive']);
                }
            }

            if ($repopulate_tales) {
                self::resetDatabase(false, $day === $schema_change_date);
            }
        }
    }

    public static function resetDatabase($reset_archive = false, bool $change_schema = false): void
    {
        $tables_manager = new TablesManager([]);

        // generate bets for 10 days
        for ($node = -1; $node < 10; $node++) {

            $primary = $node === -1 ? phive('SQL')->onlyMaster() : phive('SQL')->doDb('shards', $node);
            $archive = $node === -1 ? phive('SQL')->doDb('archive') : phive('SQL')->doDb('shard_archives', $node);

            $tables_manager->truncateTables($primary);

            if ($reset_archive) {
                $tables_manager->dropTables($archive);
                $tables_manager->revertSchemaTables($primary);
            }

            for ($j = 0; $j < 10; $j++) {
                $id = ($node + 10) * 10 + $j;
                $user_id = 100 + $node;
                $day = 10 + $j;

                $tables_manager->populateGlobal($primary, $id, $user_id, $day);

                // not master
                if ($node > -1) {
                    $tables_manager->populateShards($primary, $id, $user_id, $day);
                }

                // test schema change on global tables
                if ($change_schema) {
                    $tables_manager->changeSchemaTables($primary, true);
                }
            }
        }
    }

}
