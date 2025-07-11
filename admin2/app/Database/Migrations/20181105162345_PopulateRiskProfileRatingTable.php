<?php

use App\Extensions\Database\FManager as DB;
use App\Models\BankCountry;
use Phpmig\Migration\Migration;

class PopulateRiskProfileRatingTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }


    /**
     * @throws Exception
     */
    public function up()
    {
        /**
         * @param \Illuminate\Support\Collection $data
         * @return mixed
         */
        $bulkInsertInMasterAndShards = function ($data) {
            DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
            DB::bulkInsert($this->table, null, $data->toArray());
            return $data;
        };

        $main = collect([
            [
                "name" => $game_type = "game_type",
                "title" => "Game Type",
                "type" => "option",
                "section" => "AML",
                "data" => ""
            ],
            [
                "name" => $countries = "countries",
                "title" => "Countries",
                "type" => "option",
                "section" => "AML",
                "data" => ""
            ],
            [
                "name" => $deposit_method = "deposit_method",
                "title" => "Deposit Method",
                "type" => "option",
                "section" => "AML",
                "data" => ""
            ],
            [
                "name" => $deposit_vs_wager = "deposit_vs_wager",
                "title" => "Deposit vs Wager",
                "type" => "multiplier",
                "section" => "AML",
                "data" => ""
            ],
            [
                "name" => $deposited_last_12_months = "deposited_last_12_months",
                "title" => "Deposited amount last _MONTHS months",
                "type" => "interval",
                "section" => "AML",
                "data" => json_encode([
                    "replacers" => [
                        "_MONTHS" => 12
                    ]
                ])
            ],
            [
                "name" => $ngr_last_12_months = "ngr_last_12_months",
                "title" => "NGR last _MONTHS months",
                "type" => "interval",
                "section" => "AML",
                "data" => json_encode([
                    "replacers" => [
                        "_MONTHS" => 12
                    ]
                ])
            ],
            [
                "name" => $wagered_last_12_months = "wagered_last_12_months",
                "title" => "Wagered last _MONTHS months",
                "type" => "interval",
                "section" => "AML",
                "data" => json_encode([
                    "replacers" => [
                        "_MONTHS" => 12
                    ]
                ])
            ],
            [
                "name" => $canceled_withdrawals_last_x_days = "canceled_withdrawals_last_x_days",
                "title" => "Canceled withdrawals last _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 90
                    ]
                ])
            ],
            [
                "name" => $failed_deposits_last_x_days = "failed_deposits_last_x_days",
                "title" => "Failed deposits last _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 90
                    ]
                ])
            ],
            [
                "name" => $big_win_multiplayer = "big_win_multiplayer",
                "title" => "Big Win Multiplier on one bet last _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 90
                    ]
                ])
            ],
            [
                "name" => $have_deposit_and_loss_limits = "have_deposit_and_loss_limits",
                "title" => "Have deposit and loss limit in place",
                "type" => "interval",
                "section" => "RG",
                "data" => ""
            ],
            [
                "name" => $self_locked_excluded = "self_locked_excluded",
                "title" => "Account self-lock or self-exclusion last _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 90
                    ]
                ])
            ],
            [
                "name" => $avg_dep_amount_x_days = "avg_dep_amount_x_days",
                "title" => "Average deposited amount per logged in day from last _DAYS days have increased from previous _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 45
                    ]
                ])
            ],
            [
                "name" => $avg_dep_count_x_days = "avg_dep_count_x_days",
                "title" => "Average deposit transactions per logged in day from last _DAYS days have increased from previous _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 45
                    ]
                ])
            ],
            [
                "name" => $avg_time_per_session_x_days = "avg_time_per_session_x_days",
                "title" => "Average time per session per logged in day from last _DAYS days have increased from previous _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 45
                    ]
                ])
            ],
            [
                "name" => $avg_sessions_count_x_days = "avg_sessions_count_x_days",
                "title" => "Average sessions per logged in day from last _DAYS days have increased from previous _DAYS days",
                "type" => "interval",
                "section" => "RG",
                "data" => json_encode([
                    "replacers" => [
                        "_DAYS" => 45
                    ]
                ])
            ],
        ]);
        $bulkInsertInMasterAndShards($main);

        \App\Classes\PaymentsHelper::getOptionsCollection()
            ->map(function ($el, $key) use ($deposit_method) {
                return [
                    "name" => $key,
                    "title" => $el['title'],
                    "category" => $deposit_method,
                    "score" => $el['score'],
                    "section" => "AML"
                ];
            })
            ->values()
            ->tap($bulkInsertInMasterAndShards);

        BankCountry::get()
            ->unique('iso')
            ->map(function ($el) use ($countries) {
                return [
                    "name" => $el->iso,
                    "title" => $el->name,
                    "category" => $countries,
                    "section" => "AML"
                ];
            })
            ->tap($bulkInsertInMasterAndShards);

        collect(array_fill(1, 20, 0))
            ->keys()
            ->map(function ($el) use ($deposit_vs_wager) {
                return [
                    "name" => $el,
                    "title" => $el . "X",
                    "category" => $deposit_vs_wager,
                    "section" => "AML"
                ];
            })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["€0 - €9,999", "0,9999", 1],
            ["€10,000 - €19,999", "10000,19999", 2],
            ["€20,000 - €29,999", "20000,29999", 3],
            ["€30,000 - €39,999", "30000,39999", 4],
            ["€40,000 - €49,999", "40000,49999", 5],
            ["€50,000 - €59,999", "50000,59999", 6],
            ["€60,000 - €69,999", "60000,69999", 7],
            ["€70,000 - €79,999", "70000,79999", 8],
            ["€80,000 - €89,999", "80000,89999", 9],
            ["€90,000+", "90000", 10]
        ])
            ->map(function ($el) use ($deposited_last_12_months) {
                list($title, $name, $score) = $el;
                return [
                    "name" => $name,
                    "title" => $title,
                    "score" => $score,
                    "category" => $deposited_last_12_months,
                    "section" => "AML"
                ];
            })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["€0 - €4,999", "0,4999", 1],
            ["€5,000 - €9,999", "5000,9999", 2],
            ["€10,000 - €14,999", "10000,14999", 3],
            ["€15,000 - €19,999", "15000,19999", 4],
            ["€20,000 - €24,999", "20000,24999", 5],
            ["€25,000 - €29,999", "25000,29999", 6],
            ["€30,000 - €34,999", "30000,34999", 7],
            ["€35,000 - €39,999", "35000,39999", 8],
            ["€40,000 - €44,999", "40000,44999", 9],
            ["€45,000+", "45000", 10]
        ])
            ->map(function ($el) use ($ngr_last_12_months) {
                list($title, $name, $score) = $el;
                return [
                    "name" => $name,
                    "title" => $title,
                    "score" => $score,
                    "category" => $ngr_last_12_months,
                    "section" => "AML"
                ];
            })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["€0 - €99,000", "0,99000", 1],
            ["€100,000 - €199,999", "100000,199999", 2],
            ["€200,000 - €299,999", "200000,299999", 3],
            ["€300,000 - €399,999", "300000,399999", 4],
            ["€400,000 - €499,999", "400000,499999", 5],
            ["€500,000 - €599,999", "500000,599999", 6],
            ["€600,000 - €699,999", "600000,699999", 7],
            ["€700,000 - €799,999", "700000,799999", 8],
            ["€800,000 - €899,999", "800000,899999", 9],
            ["€900,000+", "900000", 10]
        ])->map(function ($el) use ($wagered_last_12_months) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $wagered_last_12_months,
                "section" => "AML"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["0 - 5", "0,5", 3],
            ["6 - 10", "6,10", 6],
            ["11 - 15", "11,15", 8],
            ["16+", "16", 10]
        ])->map(function ($el) use ($canceled_withdrawals_last_x_days) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $canceled_withdrawals_last_x_days,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["0 - 5", "0,5", 3],
            ["6 - 10", "6,10", 6],
            ["11 - 15", "11,15", 8],
            ["16+", "16", 10]
        ])->map(function ($el) use ($failed_deposits_last_x_days) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $failed_deposits_last_x_days,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        $game_types_map = \App\Helpers\DataFormatHelper::gameTypeOptions();
        DB::table('micro_games')
            ->select('tag')
            ->groupBy('tag')
            ->get()
            ->map(function ($el) use ($game_types_map, $game_type) {
                return [
                    "name" => $el->tag,
                    "title" => $game_types_map[$el->tag] ?? $el->tag,
                    "category" => $game_type,
                    "section" => "AML"
                ];
            })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["1X - 1000X", "1,1000", 3],
            ["1001X - 5000X", "1001,5000", 5],
            ["5001X - 9999X", "5001,9999", 7],
            ["10000+X", "10000", 10]
        ])->map(function ($el) use ($big_win_multiplayer) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $big_win_multiplayer,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["Have both deposit and loss limit", "both", 1],
            ["Have deposit limit", "deposit", 3],
            ["Have loss limit", "loss", 3],
            ["Don't have deposit or loss limit", "none", 10]
        ])->map(function ($el) use ($have_deposit_and_loss_limits) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $have_deposit_and_loss_limits,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["Account has not been self-locked or self-excluded", "none", 1],
            ["Account has been self-locked", "locked", 7],
            ["Account has been self-excluded", "excluded", 10],
            ["Account has been both self-locked and self-excluded", "both", 10]
        ])->map(function ($el) use ($self_locked_excluded) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $self_locked_excluded,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["<25%", "0,25", 1],
            ["50%", "26,50", 7],
            ["75%", "51,75", 10],
            ["100%", "75,100", 10]
        ])->map(function ($el) use ($avg_dep_amount_x_days) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $avg_dep_amount_x_days,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["<25%", "0,25", 1],
            ["50%", "26,50", 7],
            ["75%", "51,75", 10],
            ["100%", "75,100", 10]
        ])->map(function ($el) use ($avg_dep_count_x_days) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $avg_dep_count_x_days,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["<25%", "0,25", 1],
            ["50%", "26,50", 7],
            ["75%", "51,75", 10],
            ["100%", "75,100", 10]
        ])->map(function ($el) use ($avg_time_per_session_x_days) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $avg_time_per_session_x_days,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

        collect([
            ["<25%", "0,25", 1],
            ["50%", "26,50", 7],
            ["75%", "51,75", 10],
            ["100%", "75,100", 10]
        ])->map(function ($el) use ($avg_sessions_count_x_days) {
            list($title, $name, $score) = $el;
            return [
                "name" => $name,
                "title" => $title,
                "score" => $score,
                "category" => $avg_sessions_count_x_days,
                "section" => "RG"
            ];
        })
            ->tap($bulkInsertInMasterAndShards);

    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)->truncate();
        }, true);
    }
}
