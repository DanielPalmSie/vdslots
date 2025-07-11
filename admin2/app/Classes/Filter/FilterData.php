<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 12/10/2017
 * Time: 14:06
 */

namespace App\Classes\Filter;

use App\Extensions\Database\Builder;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper;
use App\Models\Config;
use App\Models\UsersSegments;

/*
|--------------------------------------------------------------------------
| Filters Data Class
|--------------------------------------------------------------------------
|
| Holds all the rules used to filter users.
|
*/
class FilterData
{

    /*
    |--------------------------------------------------------------------------
    | Class keys
    |--------------------------------------------------------------------------
    */
        private $field;
        private $field_key;

    /*
    |--------------------------------------------------------------------------
    | Class constructor
    |--------------------------------------------------------------------------
    */
        public function __construct($key=null)
        {
            if ($key)
            {
                $this->field_key = $key;
                $this->field = $this->getFieldsMap()[$key];
            }

            return $this;
        }

    /*
    |--------------------------------------------------------------------------
    | Class constants
    |--------------------------------------------------------------------------
    */
        const INVALID_DATE_DEFAULT = "none";

        const TYPES         = [
            "PERCENTAGE"=> "PERCENTAGE",
            "NUMBER"    => "NUMERICAL",
            "DROPDOWN"  => "DROPDOWN",
            "DATETIME"  => "DATETIME",
            "GROUP"     => "GROUP",
            "TEXT"      => "TEXT",
            "DATE"      => "DATE"
        ];

        const COMPARATORS   = [
            "CONTAINS"          => "contains",
            "ENDS_WITH"         => "ends_with",
            "AFTER"             => "is_after",
            "AFTER_AND_EQUAL"   => "is_after_and_equal",
            "ANNIVERSARY"       => "is_anniversary",
            "BEFORE"            => "is_before",
            "BEFORE_AND_EQUAL"  => "is_before_and_equal",
            "EQUALS"            => "is_equal_to",
            "GREATER"           => "is_greater_than",
            "GREATER_AND_EQUAL" => "is_greater_than_and_equal_to",
            "LESS"              => "is_less_than",
            "LESS_AND_EQUALS"   => "is_less_than_and_equal_to",
            "NOT_EQUAL"         => "is_not_equal_to",
            "STARTS"            => "starts_with",
            'CUSTOM_COUNTRY_BLOCKED'        => 'in_country_blocked',
        ];

        const DEFAULT_COMPARATORS           = [
            self::COMPARATORS["EQUALS"],
            self::COMPARATORS["NOT_EQUAL"],
            self::COMPARATORS["STARTS"],
            self::COMPARATORS["CONTAINS"],
            self::COMPARATORS["ENDS_WITH"],
        ];

        const EQUAL_COMPARATORS             = [
            self::COMPARATORS["EQUALS"],
            self::COMPARATORS["NOT_EQUAL"],
        ];

        const SIMPLE_NUMBER_COMPARATORS     = [
            self::COMPARATORS["EQUALS"],
            self::COMPARATORS["NOT_EQUAL"],
            self::COMPARATORS["LESS"],
            self::COMPARATORS["LESS_AND_EQUALS"],
            self::COMPARATORS["GREATER"],
            self::COMPARATORS["GREATER_AND_EQUAL"],
        ];

        const COMPLEX_NUMBER_COMPARATORS    = [
            self::COMPARATORS["EQUALS"],
            self::COMPARATORS["NOT_EQUAL"],
            self::COMPARATORS["GREATER"],
            self::COMPARATORS["GREATER_AND_EQUAL"],
            self::COMPARATORS["LESS"],
            self::COMPARATORS["LESS_AND_EQUALS"],
        ];

        const BASIC_NUMBER_COMPARATORS    = [
            self::COMPARATORS["EQUALS"],
            self::COMPARATORS["GREATER_AND_EQUAL"],
            self::COMPARATORS["LESS_AND_EQUALS"],
        ];

        const DATE_COMPARATORS              = [
            self::COMPARATORS["EQUALS"],
            self::COMPARATORS["NOT_EQUAL"],
            self::COMPARATORS["AFTER"],
            self::COMPARATORS["AFTER_AND_EQUAL"],
            self::COMPARATORS["BEFORE_AND_EQUAL"],
            self::COMPARATORS["BEFORE"],
            self::COMPARATORS["ANNIVERSARY"],
        ];

    /*
    |--------------------------------------------------------------------------
    | Class methods
    |--------------------------------------------------------------------------
    |
    | Fields rules:
    |
    |    select   -> value is string
    |             -> the value is the 'placeholder' in select statement:
    |                    select placeholder as key
    |             -> will be set for each field
    |    can_select   -> value in [true, false]
    |                 -> false: prevents the user from selecting a column
    |                 -> true: form field is required
    |                 -> null: user can select the field with no restrictions
    |    options_source   -> value is string
    |                     -> is a method name from app/Helpers/DataFormatHelper
    |                        which will return an array of key value items
    |                     -> is used only for TYPE: DROPDOWN
    |    addons     -> value is array of arrays
    |               -> enable multiple values on field
    |               -> each array can contain these keys:
    |                    - key
    |                    - type: html input type
    |                    - comparators: array of comparators
    |    join   -> is function, params:
    |                - query: base query where the join will be applied
    |                - $v(or $value): *contains the input values
    |                                 *structure: [key, comparator, value, arr]
    |                                    arr: [[key, comparator, value]]
    |                                         will be enabled when addons is set
    |                - $ids: will be set when pagination is in place
    |                        contains a list of user ids which can be used to optimize queries
    |
    |           -> callback which appends necessary query or queries
    |
    */
        public static function getUserInfoFields()
        {
            return [
                "id"        => [
                    "title"         => "ID",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.id",
                ],
                "address"        => [
                    "title"         => "Address",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.address",
                ],
                "zipcode"        => [
                    "title"         => "Zipcode",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.zipcode",
                ],
                "language"  => [
                    "title"         => "Language",
                    "type"          => self::TYPES["DROPDOWN"],
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "options_source"=> 'languageOptions',
                    "select"        => "users.preferred_lang",
                ],
                "email"     => [
                    "title"         => "Email",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.email",
                ],
                "name"      => [
                    "title"         => "Name",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "CONCAT_WS(' ', users.firstname, users.lastname)",
                ],
                "mobile"    => [
                    "title"         => "Mobile Number",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.mobile",
                ],
                "country"   => [
                    "title"             => "Country",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getCountryList",
                    "select"            => "users.country",
                ],
                "city"      => [
                    "title"         => "City",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.city",
                ],
                "currency"  => [
                    "title"             => "Currency",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getCurrencyList",
                    "select"            => "users.currency",
                ],
                "sex"       => [
                    "title"             => "Gender",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getSex",
                    "select"            => "users.sex",
                ],
                "messaging_segment"   => [
                    "title"             => "Messaging Segment",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "segmentOptions",
                    "select"            => function ($fake_name) {
                        return "{$fake_name}.group_name";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', '');
                            $segment_comparator = self::comparatorMap($v[1], $v[2]);
                            $group_comparator = self::comparatorMap($v[3][1], $v[3][2]);

                            $table = UsersSegments::groupInProgress()
                                ->selectRaw("user_id, segment_id AS group_name")
                                ->where('segment_id', $segment_comparator[0], $segment_comparator[1])
                                ->where('group_id', $group_comparator[0], $group_comparator[1])
                                ->groupBy('user_id');

                            if (!empty($condition)) {
                                $table->whereRaw($condition);
                            }

                            return $query->leftJoin(self::setFakeName("(".DB::getSql($table).")", $fake_name),
                                function ($q) use ($fake_name) {
                                    $q->on("users.id", "=", "{$fake_name}.user_id");
                                });
                        }
                    ],
                    "addons"            => [
                        ["key" => "group", "type" => "select", "comparators"=>self::EQUAL_COMPARATORS, "options_source" => "segment"]
                    ],
                    "can_select"        => false
                ],
                "messaging_segment_last_group"   => [
                    "title"             => "Last Messaging Segment Group",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "segmentOptions",
                    "select"            => function ($fake_name) {
                        return "{$fake_name}.group_name";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', '');
                            $segment_comparator = self::comparatorMap($v[1], $v[2]);
                            $group_comparator = self::comparatorMap($v[3][1], $v[3][2]);

                            $table = UsersSegments::groupEnded()
                                ->selectRaw("user_id, segment_id AS group_name")
                                ->where('segment_id', $segment_comparator[0], $segment_comparator[1])
                                ->where('group_id', $group_comparator[0], $group_comparator[1])
                                ->havingRaw("max(ended_at) between '{$v[3][5]}' and '{$v[3][8]}'")
                                ->groupBy('user_id');

                            if (!empty($condition)) {
                                $table->whereRaw($condition);
                            }

                            return $query->leftJoin(self::setFakeName("(".DB::getSql($table).")", $fake_name),
                                function ($q) use ($fake_name) {
                                    $q->on("users.id", "=", "{$fake_name}.user_id");
                                });
                        }
                    ],
                    "addons"            => [
                        ["key" => "group", "type" => "select", "comparators"=>self::EQUAL_COMPARATORS, "options_source" => "segment"],
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select"        => false
                ],
                "username"  => [
                    "title"         => "Username",
                    "type"          => self::TYPES["TEXT"],
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "select"        => "users.username",
                ],
                "age"       => [
                    "title"         => "Age",
                    "type"          => self::TYPES["NUMBER"],
                    "comparators"   => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => "TIMESTAMPDIFF(YEAR, users.dob, CURDATE())",
                ],
                "xp_level"  => [
                    "title"         => "XP Level",
                    "type"          => self::TYPES["NUMBER"],
                    "comparators"   => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "xp-level");
                            });
                        }
                    ],
                ],
                "firstname" => [
                    "title"         => "First Name",
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "type"          => self::TYPES["TEXT"],
                    "select"        => "users.firstname",
                ],
                "lastname"  => [
                    "title"         => "Last name",
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "type"          => self::TYPES["TEXT"],
                    "select"        => "users.lastname",
                ],
                "alias"     => [
                    "title"         => "Battle Alias",
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "type"          => self::TYPES["TEXT"],
                    "select"        => "alias",
                ],
                "register_date"     => [
                    "title"         => "Register date",
                    "type"          => self::TYPES["DATE"],
                    "comparators"   => self::DATE_COMPARATORS,
                    "select"        => self::defaultValueForDate('users.register_date')
                ],
                "account_verified"  => [
                    "title"         => "Account Verified",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName('users_settings', $fake_name) , function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "verified");
                            });
                        }
                    ],

                ],
                "device_preference" => [
                    "title"         => "Device preference",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "preferedDeviceTypeOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.device";
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');

                            $table = "(SELECT users.id as user_id,
                                                CASE 
                                                    WHEN IFNULL(m.count,0) > 0 AND IFNULL(d.count,0) > IFNULL(m.count,0) THEN 0
                                                    WHEN IFNULL(d.count,0) > 0 AND IFNULL(m.count,0) > IFNULL(d.count,0) THEN 1
                                                    WHEN IFNULL(m.count,0) = 0 AND IFNULL(d.count,0) > 0 THEN 2
                                                    WHEN IFNULL(d.count,0) = 0 AND IFNULL(m.count,0) > 0 THEN 3
                                                    WHEN IFNULL(d.count,0) = IFNULL(m.count,0) THEN 4
                                                END as device
                                            FROM users
                                            LEFT JOIN (
                                                SELECT user_id, device_type_num, COUNT(*) AS count
                                                FROM users_game_sessions
                                                WHERE device_type_num = 0 ";
                            $table .= $condition;
                            $table.=" GROUP BY user_id
                                            ) as d on users.id = d.user_id
                                            LEFT JOIN (
                                                SELECT user_id, device_type_num, COUNT(*) AS count
                                                FROM users_game_sessions
                                                WHERE device_type_num = 1 ";
                            $table .= $condition;
                            $table.=" GROUP BY user_id
                                            ) as m on users.id = m.user_id
                                            ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "phone_calls_subscribed"    => [
                    "title"         => "Phone Calls Subscribed",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "calls");
                            });
                        }
                    ],
                ],
                "account_status"            => [
                    "title"         => "Account Status",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "activeInactiveOptions",
                    "select"        => self::defaultValueForKey("users.active", 0),
                ],
                "is_affiliate_tagged"       => [
                    "title"         => "Is Affiliate Tagged",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => "CASE WHEN users.affe_id > 0 THEN 1 ELSE 0 END",
                ],
                "is_forum_tagged"           => [
                    "title"         => "Is Forum Tagged",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.value IS NOT NULL";
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "like", "forum-username-%");
                            });
                        }
                    ],
                ],
                "is_bonus_fraud_flagged"    => [
                    "title"         => "Is Bonus Fraud Flagged",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "bonus-fraud-flag");
                            });
                        }
                    ],
                ],
                "kyc_verified"              => [
                    "title"         => "KYC Verified",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "verified");
                            });
                        }
                    ],
                ],
                "sms_verified"              => [
                    "title"         => "SMS Verified",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return "IF({$fake_name}.value='yes', 1, 0)";
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "sms_code_verified");
                            });
                        }
                    ],
                ],
                "affiliate_id"              => [
                    "title"         => "Affiliate Id",
                    "comparators"   => self::DEFAULT_COMPARATORS,
                    "type"          => self::TYPES["TEXT"],
                    "select"        => "users.affe_id",
                ],
                "birth_date"                => [
                    "title"         => "Birth date",
                    "comparators"   => self::DATE_COMPARATORS,
                    "type"          => self::TYPES["DATE"],
                    'select'        => self::defaultValueForDate('users.dob'),
                ],
                "self_exclusion_start_date" => [
                    "title"         => "Self exclusion start date",
                    "comparators"   => self::DATE_COMPARATORS,
                    "type"          => self::TYPES["DATE"],
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.value");
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "excluded-date");
                            });
                        }
                    ],
                ],
                "self_exclusion_end_date"   => [
                    "title"         => "Self exclusion end date",
                    "comparators"   => self::DATE_COMPARATORS,
                    "type"          => self::TYPES["DATE"],
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.value");
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "unexcluded-date");
                            });
                        }
                    ],
                ],
            ];
        }
        public static function getFinancialFields()
        {
            return [
                "deposit_method"    => [
                    "title"             => "Deposit Method",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getDepositMethods",
                    "select"            => function($fake_name) {
                        return "{$fake_name}.dep_type";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = DB::table('deposits')
                                ->select(['user_id', 'dep_type'])
                                ->groupBy('user_id');

                            if ($v[2]) {
                                $comparator = self::comparatorMap($v[1], $v[2]);
                                $table->where('dep_type', $comparator[0], $comparator[1]);
                            }

                            if ($ids) $table->whereIn('user_id', $ids);

                            $table = DB::getSql($table);
                            return $query->leftJoin(self::setFakeName("({$table})", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "can_select"        => true
                ],
                "last_deposit_date" => [
                    "title"             => "Last Deposit Date",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"            => function($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.max_timestamp");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = DB::table('deposits')
                                ->selectRaw('user_id, max(timestamp) as max_timestamp')
                                ->groupBy('user_id');

                            if ($ids) $table->whereIn('user_id', $ids);

                            $table = DB::getSql($table);

                            return $query->leftJoin(self::setFakeName("({$table})", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "lifetime_revenue"  => [
                    "title"             => "Lifetime revenue",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.site_rev", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_lifetime_stats", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "has_limits"        => [
                    "title"             => "Has limits",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getHasLimitsOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.has_limits", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "(SELECT user_id, COUNT(*) > 0 AS has_limits
                                                FROM users_settings
                                                WHERE  setting = 'dep_lim'
                                                    OR setting = 'lgaloss-lim'
                                                    OR setting = 'lgawager-lim'
                                                    OR setting = 'betmax-lim'
                                                    OR setting = 'lgatime-lim'
                                                {$condition}
                                                GROUP BY user_id
                                            ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "account_balance"           => [
                    "title"         => "Account Balance",
                    "type"          => self::TYPES["NUMBER"],
                    "comparators"   => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => "users.cash_balance",
                ],
                "first_deposit_date"        => [
                    "title"             => "First Deposit Date",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.min_timestamp");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT user_id, min(timestamp) as min_timestamp 
                                                FROM deposits
                                                {$condition} 
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "deposit_limit_duration"    => [
                    "title"             => "Deposit Limit Duration",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getPeriodLimitDuration",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "dep-lim_duration");
                            });
                        }
                    ],
                ],
                "deposit_limit_amount"      => [
                    "title"             => "Deposit Limit Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "dep-lim");
                            });
                        }
                    ],
                ],
                "loss_limit_duration"       => [
                    "title"             => "Loss Limit Duration",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getPeriodLimitDuration",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",-1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "lgaloss-lim_duration");
                            });
                        }
                    ],
                ],
                "loss_limit_amount"         => [
                    "title"             => "Loss Limit Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "lgaloss-lim");
                            });
                        }
                    ],
                ],
                "wage_limit_duration"       => [
                    "title"             => "Wage Limit Duration",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getPeriodLimitDuration",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "lgawager-lim_duration");
                            });
                        }
                    ],
                ],
                "wage_limit_amount"         => [
                    "title"             => "Wage Limit Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "lgawager-lim");
                            });
                        }
                    ],
                ],
                "max_bet_limit_duration"    => [
                    "title"             => "Max Bet Limit Duration",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getPeriodLimitDuration",
                    "select"            => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "betmax-lim_duration");
                            });
                        }
                    ],
                ],
                "max_bet_limit_amount"      => [
                    "title"             => "Max Bet Limit Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "betmax-lim");
                            });
                        }
                    ],
                ],
                "timeout_limit_duration"    => [
                    "title"             => "Timeout Limit Duration",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "getPeriodLimitDuration",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "lgatime-lim_duration");
                            });
                        }
                    ],
                ],
                "timeout_limit_amount"      => [
                    "title"             => "Timeout Limit Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "=", "lgatime-lim");
                            });
                        }
                    ],
                ],
                "lifetime_deposit_amount"           => [
                    "title"             => "Lifetime Deposit Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.deposits", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_lifetime_stats", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "lifetime_number_of_deposits"       => [
                    "title"             => "Lifetime Number of Deposits",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.amount_sum", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT user_id, count(amount) as amount_sum 
                                                FROM deposits 
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return  $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "lifetime_withdrawal_amount"        => [
                    "title"             => "Lifetime Withdrawal Amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.withdrawals", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return  $query->leftJoin(self::setFakeName("users_lifetime_stats", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "lifetime_number_of_withdrawals"    => [
                    "title"             => "Lifetime Number of Withdrawals",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.withdrawas_count", 0);
                    },
                    "join"              => [
                        "callback"  => function($query,$v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids,'user_id', 'AND');
                            $table = "( SELECT count(amount) as withdrawas_count, user_id 
                                                FROM cash_transactions 
                                                WHERE description = 'Withdrawal'
                                                {$condition} 
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return  $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "deposit_limit_amount_remaining"    => [
                    "title"             => "Deposit Limit Amount Percent Remaining",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.percent", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT user_id, 100 - ( sum(amount) * 100 / limit_value_holder.value ) as percent                             
                                                FROM deposits
                                                LEFT JOIN (
                                                    SELECT user_id as uid, value 
                                                    FROM users_settings 
                                                    WHERE users_settings.setting = 'dep-lim'
                                                    {$condition}
                                                ) as limit_value_holder on deposits.user_id = limit_value_holder.uid
                                                
                                                LEFT JOIN (
                                                    SELECT user_id as uid, value 
                                                    FROM users_settings 
                                                    WHERE users_settings.setting = 'dep-lim_stamp'
                                                    {$condition}
                                                ) as start_limit on deposits.user_id = start_limit.uid
                                                
                                                LEFT JOIN (
                                                    SELECT user_id as uid, value 
                                                    FROM users_settings 
                                                    WHERE users_settings.setting = 'dep-lim_unlock'
                                                    {$condition}
                                                ) as end_limit on user_id = end_limit.uid
                                                
                                                WHERE limit_value_holder.value > 0 
                                                    AND timestamp > start_limit.value 
                                                    AND timestamp < end_limit.value
                                                    AND end_limit.value > now() AND start_limit.value < now()
                                                    {$condition} 
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "loss_limit_amount_remaining"       => [
                    "title"             => "Loss Limit Amount Remaining Percent",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.percent", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'lga_log.user_id', 'AND');

                            $table = "( SELECT lga_log.user_id, 100 - (val * 100 / us.value) as percent 
                                                FROM lga_log
                                                LEFT JOIN users_settings as us 
                                                    ON lga_log.user_id = us.user_id 
                                                    AND us.setting = 'lgaloss-lim'
                                                
                                                WHERE nm = 'lossamount' 
                                                AND val > 0
                                                AND us.value > 0
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "wage_limit_amount_remaining"       => [
                    "title"             => "Wage Limit Amount Remaining Percent",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.percent", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'lga_log.user_id', 'AND');

                            $table = "( SELECT lga_log.user_id, 100 - (lga_log.val * 100 / us.value) as percent
                                                FROM lga_log
                                                LEFT JOIN users_settings as us 
                                                    ON lga_log.user_id = us.user_id 
                                                    AND us.setting = 'lgawager-lim'
                                                WHERE nm = 'betamount' 
                                                AND val > 0
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "max_bet_limit_amount_remaining"    => [
                    "title"             => "Max Bet Limit Amount Remaining Percent",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.percent", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'maximum.user_id', 'AND');

                            $table = "( SELECT maximum.user_id, 100 - (current.value * 100 / maximum.value) as percent
                                            FROM users_settings maximum
                                            LEFT JOIN users_settings as current
                                                ON current.user_id = maximum.user_id
                                                AND current.setting = 'cur-betmax-lim'
                                            where maximum.setting = 'betmax-lim'
                                                {$condition}
                                            GROUP BY user_id
                                            ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "timeout_limit_amount_remaining"    => [
                    "title"             => "Timeout Limit Amount Remaining percent",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.percent", -1);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'maximum.user_id', 'AND');

                            $table = "( SELECT maximum.user_id, 100 - (current.value * 100 / maximum.value) as percent
                                            FROM users_settings maximum
                                            LEFT JOIN users_settings as current
                                                ON current.user_id = maximum.user_id
                                                AND current.setting = 'cur-lgatime-lim'
                                            where maximum.setting = 'lgatime-lim'
                                                {$condition}
                                            GROUP BY user_id
                                            ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "deposit_amount_in_period"          => [
                    "title"             => "Deposit amount in period",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"            => function($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.sum_amount", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = DB::table('deposits')
                                ->selectRaw("sum(amount) as sum_amount, user_id")
                                ->groupBy('user_id');

                            if ($v[3][2])   $table->whereDate('timestamp', '>', $v[3][2]);
                            if ($v[3][5])   $table->whereDate('timestamp', '<', $v[3][5]);
                            if ($ids)       $table->whereIn('user_id', $ids);

                            $table = DB::getSql($table);

                            return $query->leftJoin(self::setFakeName("({$table})", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select" => true
                ],
                "number_of_deposits_in_period"      => [
                    "title"             => "Number of deposits in period",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.count_amount", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = "( SELECT count(amount) as count_amount, user_id 
                                                FROM deposits 
                                                WHERE timestamp > 0 ";
                            $table .= self::getPaginationCondition($ids, 'user_id', 'AND');
                            if ($v[3][2])
                            {
                                $table .= " AND DATE_FORMAT(".self::defaultValueForDate('timestamp').", '%Y-%m-%d') >= '".$v[3][2]."' ";
                            }
                            if ($v[3][5])
                            {
                                $table .= "AND DATE_FORMAT(".self::defaultValueForDate('timestamp').", '%Y-%m-%d') <= '".$v[3][5]."' ";
                            }
                            $table .= " GROUP BY user_id) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select" => true
                ],
                "number_of_withdrawals_in_period"   => [
                    "title"             => "Number of withdrawals in period",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.count_amount", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = "( SELECT count(amount) as count_amount, user_id 
                                                FROM cash_transactions 
                                                WHERE description = 'Withdrawal'";
                            $table .= self::getPaginationCondition($ids, 'user_id', 'AND');
                            if ($v[3][2])
                            {
                                $table .= " AND timestamp > '".$v[3][2]."' ";
                            }
                            if ($v[3][5])
                            {
                                $table .= " AND timestamp < '".$v[3][5]."' ";
                            }
                            $table .= " GROUP BY user_id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select"        => true
                ],
                "withdrawal_amount_in_period"       => [
                    "title"             => "Withdrawal amount in period",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.sum_amount", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = "( SELECT sum(-1*amount) as sum_amount, user_id 
                                                FROM cash_transactions 
                                                WHERE description = 'Withdrawal'";
                            $table .= self::getPaginationCondition($ids, 'user_id', 'AND');
                            if ($v[3][2])
                            {
                                $table .= " AND timestamp > '" . $v[3][2] . "' ";
                            }
                            if ($v[3][5])
                            {
                                $table .= " AND timestamp < '".$v[3][5]."' ";
                            }
                            $table .= " GROUP BY user_id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select"        => true
                ],
                "country_deposit_block"       => [
                    "title"             => "Country Deposit Block",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [
                        self::COMPARATORS["CUSTOM_COUNTRY_BLOCKED"]
                    ],
                    "options_source"    => "simpleBooleanOptions",
                    "select"            => "users.country",
                    "can_select"        => true
                ],
            ];
        }
        public static function getActivityDataFields()
        {
            return [
                "last_login"       => [
                    "title"             => "Last Login Date",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"            => self::defaultValueForDate('users.last_login'),
                ],
                "last_played_date" => [
                    "title"             => "Last Played Date",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.last_date");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT user_id, max(end_time) as last_date 
                                                FROM users_game_sessions 
                                                {$condition}
                                                GROUP BY user_id 
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "has_unplayed_spins"       => [
                    "title"             => "Has unplayed spins/ re-buys",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.left_spins", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT te.user_id as uid, sum(te.spins_left) > 0 as left_spins 
                                                FROM tournament_entries as te
                                                LEFT JOIN tournaments as t
                                                ON te.t_id = t.id
                                                WHERE DATE_FORMAT(".self::defaultValueForDate('t.end_time').", '%Y-%m-%d') > DATE_FORMAT(NOW(), '%Y-%m-%d')
                                                {$condition}
                                                GROUP BY uid
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.uid", "=", "users.id");
                            });
                        }
                    ],
                ],
                "has_favourite_games"      => [
                    "title"             => "Has favourite games",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.total", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT count(*) > 0 as total, user_id 
                                                FROM users_games_favs
                                                {$condition} 
                                                GROUP BY user_id
                                          ) as {$fake_name} ";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "lifetime_wagered_amount"  => [
                    "title"             => "Lifetime wagered amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.bets", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_lifetime_stats", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "have_never_played"        => [
                    "title"             => "Have never played",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "gameTypeOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.game";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            if (in_array($v[2], ["sng", "mtt"]))
                            {
                                $table = "( SELECT users.id, '{$v[2]}' as game 
                                                    FROM users
                                                    LEFT JOIN (
                                                        SELECT user_id, count(*) as total 
                                                        FROM tournament_entries as te
                                                        LEFT JOIN tournaments as t on te.t_id = t.id
                                                        WHERE t.start_format = '{$v[2]}'
                                                        {$condition}
                                                        GROUP BY user_id
                                                    ) as aux
                                                    ON users.id = aux.user_id
                                                    WHERE aux.total is null OR aux.total = 0
                                                    {$condition}
                                              ) as {$fake_name}";
                            } else {
                                $table = "( SELECT users.id, '{$v[2]}' as game 
                                                    FROM users
                                                    LEFT JOIN (
                                                        SELECT user_id, count(*) as total 
                                                        FROM users_game_sessions as ugs
                                                        LEFT JOIN micro_games as mg on ugs.game_ref = mg.game_id
                                                        WHERE mg.tag = '{$v[2]}'
                                                        {$condition}
                                                        GROUP BY user_id
                                                    ) as aux 
                                                    ON users.id = aux.user_id
                                                    WHERE aux.total is null OR aux.total = 0
                                                    {$condition}
                                              ) as {$fake_name}";
                            }

                            return $query->rightJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.id", "=", "users.id");
                            });
                        }
                    ],
                    "can_select"        => false
                ],
                "last_played_videoslots"   => [
                    "title"             => "Last played videoslots",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "gameTypeOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.played";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            if (in_array($v[2], ["sng", "mtt"]))
                            {
                                $table = "( SELECT users.id, last_played, '{$v[2]}' as played
                                                    FROM users
                                                    LEFT JOIN (
                                                        SELECT t_id, user_id, max(t.end_time) as last_played, mg.tag as played
                                                        FROM tournament_entries as te
                                                        LEFT JOIN tournaments as t on te.t_id = t.id
                                                        WHERE t.start_format = '{$v[2]}'
                                                        {$condition}
                                                        GROUP BY user_id
                                                    ) aux ON aux.user_id = users.id
                                                    WHERE last_played is not null 
                                                    {$condition}
                                              ) as {$fake_name}";
                            } else {
                                $table = "( SELECT users.id, last_played, '{$v[2]}' as played
                                                    FROM users
                                                    LEFT JOIN (
                                                        SELECT user_id, max(ugs.end_time) as last_played, mg.tag as played
                                                        FROM users_game_sessions as ugs
                                                        LEFT JOIN micro_games as mg ON ugs.game_ref = mg.game_id
                                                        WHERE mg.tag = '{$v[2]}'
                                                        {$condition}
                                                        GROUP BY user_id
                                                    ) as aux ON aux.user_id = users.id
                                                    WHERE last_played is not null
                                                    {$condition}
                                              ) as {$fake_name}";
                            }

                            $comparator = self::comparatorMap($v[3][1], $v[3][2]);

                            return $query->join(DB::raw($table), function($q) use ($comparator, $fake_name)
                            {
                                $q->on("{$fake_name}.id", "=", "users.id");
                                if ($comparator[0] and $comparator[1])
                                {
                                    $q->whereRaw("DATE_FORMAT(".self::defaultValueForDate("{$fake_name}.last_played").", '%Y-%m-%d') {$comparator[0]} '{$comparator[1]}'");
                                }
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "date", "type" => "date", "comparators" => self::DATE_COMPARATORS]
                    ],
                    "can_select"        => false
                ],
                "have_played_at_least_once"        => [
                    "title"             => "Have played at least once",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "gameTypeOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.game_type";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            if (in_array($v[2], ["sng", "mtt"]))
                            {
                                $table = "( SELECT user_id, '{$v[2]}' as game_type
                                                    FROM tournament_entries as te
                                                    LEFT JOIN tournaments as t ON te.t_id = t.id
                                                    WHERE t.start_format = '{$v[2]}'
                                                    {$condition}
                                                    GROUP BY user_id
                                              ) as {$fake_name}";
                            } else {
                                $table = "( SELECT user_id, '{$v[2]}' as game_type
                                                    FROM users_game_sessions as ugs
                                                    LEFT JOIN micro_games as mg ON ugs.game_ref = mg.game_id
                                                    WHERE mg.tag = '{$v[2]}'
                                                    {$condition}
                                                    GROUP BY user_id
                                              ) as {$fake_name}";
                            }
                            return $query->rightJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "can_select"        => false
                ],
                "have_never_played_between"        => [
                    "title"             => "Have never played between",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "gameTypeOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.game";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = "( SELECT users.id, IFNULL(aux.total, 0) as total, '{$v[2]}' as game
                                                FROM users
                                                LEFT JOIN ";
                            if (in_array($v[2], ["sng", "mtt"]))
                            {
                                $table .= "( SELECT user_id, count(*) as total
                                                    FROM tournament_entries as te
                                                    LEFT JOIN tournaments as t ON te.t_id = t.id
                                                    WHERE t.start_format = '".$v[2]."' ";
                                if ($v[3][2])
                                {
                                    $table .= " AND t.start_time >= '".$v[3][2]."' ";
                                    $table .= " AND t.start_time <= '".$v[3][5]."' ";
                                }
                            } else {
                                $table .= "( SELECT user_id, count(*) as total 
                                                    FROM users_game_sessions as ugs
                                                    LEFT JOIN micro_games as mg ON ugs.game_ref = mg.game_id
                                                    WHERE mg.tag = '".$v[2]."' ";
                                if ($v[3][2])
                                {
                                    $table .= " AND ugs.start_time >= '".$v[3][2]."' ";
                                    $table .= " AND ugs.start_time <= '".$v[3][5]."' ";
                                }
                            }
                            $table .= "         GROUP BY user_id
                                               ) as aux ON users.id = aux.user_id
                                           ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.id", "=", "users.id")
                                    ->where("{$fake_name}.total", "=", 0);
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select"        => false
                ],
                /* todo: how to cover this case: user can register in multiple battles in the same time */
                /*"registered_battle_end_date_time"  => [
                    "title"             => "Registered Battle End Date",
                    "type"              => self::TYPES['DATE'],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "DATE_FORMAT(".self::defaultValueForDate("{$fake_name}.end_time").", '%Y-%m-%d')";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'WHERE');
                            $table = "( SELECT te.user_id, t.end_time as end_time
                                                FROM tournament_entries as te
                                                LEFT JOIN tournaments as t
                                                ON te.t_id = t.id
                                                where t.status = 'registration.open'
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],*/
                "total_number_of_game_categories"  => [
                    "title"             => "Total number of game categories played",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.total", '0');
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $condition_2 = self::getPaginationCondition($ids, 'users.id');
                            $table = "( select users.id as user_id, IFNULL(tournaments_total.total, 0) + IFNULL(games_total.total, 0) as total 
                                            FROM users
                                            LEFT JOIN (
                                                SELECT distinct start_format, user_id as id, count(*) as total
                                                FROM tournament_entries te
                                                LEFT JOIN tournaments t
                                                ON t.id = te.t_id
                                                WHERE start_format is not null
                                                {$condition}
                                                GROUP BY user_id
                                            ) as tournaments_total ON users.id = tournaments_total.id
                                            LEFT JOIN (
                                                SELECT distinct tag, user_id as id, count(*) as total 
                                                FROM users_game_sessions mgs
                                                LEFT JOIN micro_games as mg 
                                                ON mgs.game_ref = mg.game_id
                                                WHERE tag is not null
                                                {$condition}
                                                GROUP BY user_id
                                            ) as games_total ON users.id = games_total.id
                                            {$condition_2}
                                      ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "total_number_of_games_played"     => [
                    "title"             => "Total number of games played",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.total";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id');
                            $condition2 = self::getPaginationCondition($ids, 'users.id');
                            $table = "( SELECT users.id as user_id, IFNULL(tournaments_total.total, 0) + IFNULL(games_total.total, 0) as total 
                                                FROM users
                                                LEFT JOIN (
                                                    SELECT user_id, count(*) as total 
                                                    FROM tournament_entries 
                                                    {$condition}
                                                    GROUP BY user_id
                                                ) as tournaments_total ON users.id = tournaments_total.user_id
                                                LEFT JOIN (
                                                    SELECT user_id, count(*) as total 
                                                    FROM users_game_sessions
                                                    {$condition}
                                                    GROUP BY user_id
                                                ) as games_total ON users.id = games_total.user_id
                                            {$condition2}
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "have_played_battle_of_slots"      => [
                    "title"             => "Have played battle of slots",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.played", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT user_id, count(*) > 0 as played 
                                                FROM tournament_entries
                                                {$condition}
                                                GROUP BY user_id 
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "number_of_failed_login_attempts"  => [
                    "title"             => "Number of failed login attempts",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.total", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');

                            $table = "( SELECT user_id, count(*) as total
                                            FROM failed_logins
                                            WHERE " . self::defaultValueForDate('created_at') . " >= '{$v[3][2]}'
                                            AND " . self::defaultValueForDate('created_at') . " <= '{$v[3][5]}'
                                            {$condition}
                                            GROUP BY user_id
                                         ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select"        => true
                ],
                "last_played_videoslots_main_category"     => [
                    "title"             => "Last played videoslots main category",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "mainGameCategoryOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.game";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id');
                            if ($v[2] === "bos")
                            {
                                // Battle of slots
                                $table = "( SELECT user_id, max(updated_at) as last_played, '{$v[2]}' as game
                                                FROM tournament_entries
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";
                            } else {
                                // Any game in general
                                $table = "( SELECT user_id, max(end_time) as last_played, '{$v[2]}' as game
                                                FROM users_game_sessions
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";
                            }

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "date", "type" => "date", "comparators" => self::DATE_COMPARATORS]
                    ],
                    "can_select"        => false
                ],
                "have_played_at_least_once_between"        => [
                    "title"             => "Have played at least once between",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => [self::COMPARATORS["EQUALS"]],
                    "options_source"    => "gameTypeOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.game_type";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            if (in_array($v[2], ["sng", "mtt"]))
                            {
                                $table = "( SELECT user_id, count(*) as total, '{$v[2]}' as game_type 
                                                FROM tournament_entries as te
                                                LEFT JOIN tournaments as t ON te.t_id = t.id
                                                WHERE t.start_format = '{$v[2]}' 
                                                    AND t.start_time >= '{$v[3][2]}'
                                                    AND t.start_time <= '{$v[3][5]}' 
                                                GROUP BY user_id
                                           ) as {$fake_name} ";
                            } else {
                                $table = "( SELECT user_id, count(*) as total, '{$v[2]}' as game_type
                                                FROM users_game_sessions as ugs
                                                LEFT JOIN micro_games as mg ON ugs.game_ref = mg.game_id
                                                WHERE mg.tag = '{$v[2]}' 
                                                    AND ugs.start_time >= '{$v[3][2]}' 
                                                    AND ugs.start_time <= '{$v[3][5]}'
                                                GROUP BY user_id
                                           ) as {$fake_name}";
                            }

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.total", ">", 0);
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "start_time", "type" => "date"],
                        ["key" => "end_time", "type" => "date"]
                    ],
                    "can_select"        => false
                ],
                "registered_to_a_not_started_battle"       => [
                    "title"             => "Registered to a battle which didn't start yet",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.total", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');

                            $table = "( SELECT te.user_id, t.start_time, count(user_id) > 0 as total 
                                                FROM tournament_entries as te
                                                LEFT JOIN tournaments as t on te.t_id = t.id
                                                WHERE DATE_FORMAT(".self::defaultValueForDate('t.start_time').", '%Y-%m-%d') > DATE_FORMAT(NOW(), '%Y-%m-%d')
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                /* todo: how to cover this case: user can register in multiple battles in the same time */
                /*"registered_battle_start_date_time"        => [
                    "title"             => "Registered Battle Start Date",
                    "type"              => self::TYPES['DATETIME'],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"            => "IFNULL(registered_battle_start_date_time.start_time, -1)",
                    "join"              => [
                        "callback"  => function($query, $v, $ids)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');

                            $table = "(SELECT te.user_id,  t.start_time FROM tournament_entries as te
                                                left join tournaments as t
                                                on te.t_id = t.id
                                                WHERE DATE_FORMAT(".self::defaultValueForDate('t.start_time').", '%Y-%m-%d') > DATE_FORMAT(NOW(), '%Y-%m-%d')
                                                {$condition}
                                                group by user_id
                                            ) as registered_battle_start_date_time";

                            return $query->leftJoin(DB::raw($table), function($q)
                            {
                                $q->on("registered_battle_start_date_time.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],*/
                "last_session_end_result"           => [
                    "title"             => "Last session end result",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "lastSessionOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.position", 6);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'users_sessions.user_id', 'AND');
                            $condition_2 = self::getPaginationCondition($ids, 'ugs.user_id', 'AND');

                            $table = "(SELECT a.user_id
                                             , a.created_at
                                             , a.ended_at
                                             , sum(win_amount)*100/sum(bet_amount) as percentage 
                                             , CASE 
                                                WHEN sum(win_amount)*100/sum(bet_amount) = 0.0 THEN 0
                                                WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 0.1 AND 20.0 THEN 1
                                                WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 20.1 AND 80.0 THEN 2
                                                WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 80.1 AND 120.0 THEN 3
                                                WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 120.1 AND 500.0 THEN 4
                                                WHEN sum(win_amount)*100/sum(bet_amount) > 500.0 THEN 5
                                             END as position
                                        FROM (
                                            SELECT f.user_id, f.created_at, f.ended_at 
                                            FROM (
                                                SELECT user_id, max(created_at) as created_at 
                                                FROM users_sessions 
                                                WHERE end_reason = 'logout'
                                                {$condition} 
                                                GROUP BY user_id
                                            ) as x 
                                            JOIN users_sessions as f 
                                                ON f.user_id = x.user_id 
                                                AND f.created_at = x.created_at
                                        ) as a
                                        RIGHT JOIN users_game_sessions as ugs
                                            ON ugs.user_id = a.user_id 
                                            AND ugs.start_time >= a.created_at 
                                            AND ugs.end_time <= a.ended_at
                                            AND start_time <> '0000-00-00 00:00:00'
                                            AND end_time <> '0000-00-00 00:00:00'
                                           {$condition_2}
                                        GROUP BY a.user_id
                                    ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "previous_session_end_result"       => [
                    "title"             => "Previous session end result",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "lastSessionOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.position", 6);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'b.user_id', 'AND');
                            $condition_1 = self::getPaginationCondition($ids, 'a.user_id', 'AND');
                            $condition_2 = self::getPaginationCondition($ids, 'ugs.user_id', 'AND');
                            $table = "( select a.user_id
                                                 , a.created_at
                                                 , a.ended_at 
                                                 , case 
                                                    WHEN sum(win_amount)*100/sum(bet_amount) = 00.0 THEN 0
                                                    WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 0.1 AND 20.0 THEN 1
                                                    WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 20.1 AND 80.0 THEN 2
                                                    WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 80.1 AND 120.0 THEN 3
                                                    WHEN sum(win_amount)*100/sum(bet_amount) BETWEEN 120.1 AND 500.0 THEN 4
                                                    WHEN sum(win_amount)*100/sum(bet_amount) > 500.0 THEN 5
                                                 END as position
                                            FROM (
                                                SELECT f.user_id, f.created_at, f.ended_at 
                                                FROM (
                                                    SELECT user_id, created_at 
                                                    from users_sessions as a
                                                    WHERE 1 = ( SELECT count(*) 
                                                         FROM users_sessions b
                                                         WHERE b.created_at > a.created_at 
                                                         AND b.user_id = a.user_id 
                                                         AND end_reason = 'logout'
                                                         {$condition}
                                                    )
                                                    {$condition_1}
                                                    GROUP BY user_id
                                                ) as x 
                                                JOIN users_sessions as f 
                                                    on f.user_id = x.user_id 
                                                    AND f.created_at = x.created_at
                                            ) as a
                                            RIGHT JOIN users_game_sessions as ugs
                                                ON ugs.user_id = a.user_id 
                                                AND ugs.start_time >= a.created_at 
                                                AND ugs.end_time <= a.ended_at
                                                {$condition_2}
                                            GROUP BY a.user_id
                                      ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "wagered_amount_30_days"  => [
                    "title"             => "Wagered amount - past 30 days",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"            => function($fake_name) {
                        return self::defaultValueForKey("sum({$fake_name}.bets)", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_daily_stats", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.date", ">=", "CURDATE() - INTERVAL 30 DAY");
                            });
                        }
                    ],

                ],
                "lifetime_avg_deposit_amount"       => [
                    "title"             => "Lifetime average deposit amount",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"            => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.deposits / {$fake_name}.ndeposits ", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_lifetime_stats", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "bonus_of_total_wagering"       => [
                    "title"             => "% Bonus of total wagering",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"            => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.percent", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id');

                            $table = "( SELECT 
                                            user_id, 
                                            (ABS( IF( rewards = 0, 0, rewards-fails ) ) * 100 / bets ) AS percent
                                        FROM users_lifetime_stats
                                        {$condition}
                                      ) AS {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "bet_amount_since"          => [
                    "title"             => "Bet amount since",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"            => function($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.sum_amount", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $comparator = self::comparatorMap($v[3][1], $v[3][2]);

                            $comparator[1]  = Condition::getComplexValue($comparator[1]);

                            $table = "( SELECT sum(bets) as sum_amount, user_id, date
                                        FROM users_daily_stats 
                                        WHERE 1 ";

                            if ($comparator[0] and $comparator[1]) {
                                $table .= " AND date {$comparator[0]} ({$comparator[1]}) ";
                            }

                            if ($ids) {
                                $ids = self::arrayToSql($ids);
                                $table .= " AND user_id in $ids ";
                            }

                            $table .= " GROUP BY user_id ) AS {$fake_name}";


                            return $query->leftJoin(DB::raw($table), function ($q) use ($fake_name, $comparator) {
                                $q->on("{$fake_name}.user_id", "=", "users.id");

                                if ($comparator[0] and $comparator[1]) {
                                    $q->whereRaw("DATE_FORMAT(" . self::defaultValueForDate("{$fake_name}.date") . ", '%Y-%m-%d') {$comparator[0]} {$comparator[1]}");
                                }
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "date", "type" => "date", "comparators" => self::DATE_COMPARATORS, "expand_date" => true]
                    ],
                    "can_select" => true
                ],
            ];
        }
        public static function getPromotionStatsFields()
        {
            return [
                "bonus_code"        => [
                    "title"             => "Bonus Code",
                    "type"              => self::TYPES["TEXT"],
                    "comparators"       => self::DEFAULT_COMPARATORS,
                    "select"            => "users.bonus_code",
                ],
                "last_won_trophy"   => [
                    "title"             => "Last won trophy",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.last_won");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');

                            $table = "( SELECT user_id, max(te.created_at) as last_won 
                                                FROM trophy_events as te
                                                WHERE finished = 1
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "first_won_trophy"  => [
                    "title"             => "First won trophy",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.first_won");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT user_id, min(te.created_at) as first_won 
                                                FROM trophy_events as te
                                                WHERE finished = 1
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "first_won_reward"  => [
                    "title"             => "First won reward",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.first_won");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT user_id, min(tao.created_at) as first_won 
                                                FROM trophy_award_ownership as tao
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "last_won_reward"   => [
                    "title"             => "Last won reward",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.last_won");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids);
                            $table = "( SELECT user_id, max(tao.created_at) as last_won 
                                                FROM trophy_award_ownership as tao
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "bonus_available"           => [
                    "title"             => "Bonus available",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "hasBonusOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.available", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT user_id, count(status) > 0 as available 
                                                FROM trophy_award_ownership
                                                WHERE status = 0
                                                {$condition}
                                                GROUP BY status, user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "bonus_claimed"             => [
                    "title"             => "Bonus claimed",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "hasBonusOptions",
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.claimed";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT user_id, count(status) > 0 as claimed 
                                                FROM trophy_award_ownership
                                                WHERE status = 2
                                                {$condition}
                                                GROUP BY status, user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "bonus_progress"            => [
                    "title"             => "Bonus progress",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.progress";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT be.user_id, be.progress / be.cost * 100 AS progress 
                                                FROM bonus_entries be 
                                                LEFT JOIN bonus_types bt ON be.bonus_id = bt.id 
                                                WHERE bonus_code <> ''
                                                AND bonus_code = '{$v[3][2]}' ";
                            $table .= $condition;
                            $table .= " GROUP BY be.id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "bonus_code", "type" => "text"]
                    ]
                ],
                "bonus_expiry_date"         => [
                    "title"             => "Bonus expiry date",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.expire_date");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT be.user_id, bt.expire_time as expire_date 
                                                FROM bonus_entries be 
                                                LEFT JOIN bonus_types bt ON be.bonus_id = bt.id 
                                                WHERE bonus_code <> '' 
                                                AND bonus_code like '%{$v[3][2]}%' ";
                            $table .= $condition;
                            $table .= " GROUP BY be.id, be.user_id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key"=>"bonus_code", "type"=>"text"]
                    ],
                    "can_select"        => true
                ],
                "bonus_lifetime_ends"       => [
                    "title"             => "Bonus lifetime ends",
                    "type"              => self::TYPES["DATE"],
                    "comparators"       => self::DATE_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForDate("{$fake_name}.end_time");
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT be.id AS entry_id, bt.bonus_code, be.user_id, be.cost AS wager_req, be.end_time
                                                FROM bonus_entries be 
                                                LEFT JOIN bonus_types bt ON be.bonus_id = bt.id 
                                                WHERE bonus_code <> '' 
                                                AND bonus_code like '%{$v[3][2]}%' 
                                                ";
                            $table .= $condition;
                            $table .= " GROUP BY be.id, be.user_id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "bonus_code", "type" => "text"]
                    ],
                    "can_select"        => true
                ],
                "number_of_trophies_won"    => [
                    "title"             => "Number of trophies won",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.won_number", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'tao.user_id');
                            $table = "( SELECT user_id, award_id, count(award_id) as won_number 
                                                FROM trophy_award_ownership as tao
                                                LEFT JOIN trophy_awards as ta
                                                    ON tao.award_id = ta.id
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "this_week_position_casino_race"    => [
                    "title"             => "Position in this week's Casino Race",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.spot", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( select spot, user_id 
                                                FROM race_entries as r
                                                WHERE WEEKOFYEAR(r.start_time)=WEEKOFYEAR(NOW()) 
                                                AND YEAR(r.start_time) = YEAR(now())
                                                {$condition}
                                                GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "last_week_position_casino_race"    => [
                    "title"             => "Position in last week's Casino Race",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.spot", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( select spot, user_id 
                                                FROM race_entries as r
                                                WHERE WEEKOFYEAR(r.start_time)=WEEKOFYEAR(NOW())-1 
                                                    AND YEAR(r.start_time) = YEAR(now())
                                                    {$condition}
                                                    GROUP BY user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "lifetime_bonus_money_claimed"      => [
                    "title"             => "Lifetime bonus money claimed",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.sum_amount", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id');
                            $table = "( SELECT status, user_id, award_id, sum(ta.amount) as sum_amount 
                                                FROM trophy_award_ownership tao
                                                LEFT JOIN trophy_awards ta
                                                ON tao.award_id = ta.id
                                                {$condition}
                                                GROUP BY tao.user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "number_of_claimed_bonuses"         => [
                    "title"             => "Number of claimed blonuses",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::COMPLEX_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.claimed", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "( SELECT status, user_id, award_id, count(status) as claimed 
                                                FROM trophy_award_ownership
                                                WHERE status = 2
                                                {$condition}
                                                GROUP BY status, user_id
                                          ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
            ];
        }
        public static function getCommunicationsDataFields()
        {
            return [
                "email_name"    => [
                    "title"             => "Email name",
                    "type"              => self::TYPES["TEXT"],
                    "comparators"       => self::DEFAULT_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.template_name";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("messaging_campaign_users", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.template_type", "=", 2);
                            });
                        }
                    ],
                ],
                "email_id"      => [
                    "title"             => "Email id",
                    "type"              => self::TYPES["TEXT"],
                    "comparators"       => self::DEFAULT_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.template_id";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("messaging_campaign_users", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.template_type", "=", 2);
                            });
                        }
                    ],
                ],
                "sms_name"      => [
                    "title"             => "Sms name",
                    "type"              => self::TYPES["TEXT"],
                    "comparators"       => self::DEFAULT_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.template_name";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("messaging_campaign_users", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.template_type", "=", 1);
                            });
                        }
                    ],
                ],
                "sms_id"        => [
                    "title"             => "Sms id",
                    "type"              => self::TYPES["TEXT"],
                    "comparators"       => self::DEFAULT_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.template_id";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("messaging_campaign_users", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.template_type", "=", 1);
                            });
                        }
                    ],
                ],
                "sent_sms"      => [
                    "title"             => "Sent sms",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.count", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $fake_name)
                        {
                            $table = "( SELECT user_id, count(*) > 0 as count from messaging_campaign_users
                                            WHERE template_type = 1 ";
                            if ($v[3][2])
                            {
                                $comparator = self::comparatorMap($v[3][1], $v[3][2]);
                                $table .= " AND template_id {$comparator[0]} {$comparator[1]}";
                            }
                            if ($v[3][5])
                            {
                                $comparator = self::comparatorMap($v[3][4], $v[3][5]);
                                $table .= " AND template_name {$comparator[0]} '{$comparator[1]}'";
                            }
                            $table .= "GROUP BY user_id
                                            ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "sms_id", "type" => "number", "comparators" => self::COMPLEX_NUMBER_COMPARATORS],
                        ["key" => "sms_name", "type" => "text", "comparators" => self::DEFAULT_COMPARATORS]
                    ],
                    "can_select"        => false
                ],
                "sent_email"    => [
                    "title"             => "Sent email",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.count", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = "( SELECT user_id, count(*) > 0 as count from messaging_campaign_users
                                            WHERE template_type = 2 ";
                            if ($v[3][2])
                            {
                                $comparator = self::comparatorMap($v[3][1], $v[3][2]);
                                $table .= " AND template_id {$comparator[0]} {$comparator[1]}";
                            }
                            if ($v[3][5])
                            {
                                $comparator = self::comparatorMap($v[3][4], $v[3][5]);
                                $table .= " AND template_name {$comparator[0]} '{$comparator[1]}'";
                            }
                            $table .= "GROUP BY user_id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "email_id", "type" => "number", "comparators" => self::COMPLEX_NUMBER_COMPARATORS],
                        ["key" => "email_name", "type" => "text", "comparators" => self::DEFAULT_COMPARATORS]
                    ],
                    "can_select"        => false
                ],
                "delivered_email"    => [
                    "title"             => "Delivered email",
                    "type"              => self::TYPES["DROPDOWN"],
                    "comparators"       => self::EQUAL_COMPARATORS,
                    "options_source"    => "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.count", 0);
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $table = "( SELECT user_id, count(*) > 0 as count from messaging_campaign_users
                                            WHERE template_type = 2 
                                            AND status = 'sent' ";
                            if ($v[3][2])
                            {
                                $comparator = self::comparatorMap($v[3][1], $v[3][2]);
                                $table .= " AND template_id {$comparator[0]} {$comparator[1]}";
                            }
                            if ($v[3][5])
                            {
                                $comparator = self::comparatorMap($v[3][4], $v[3][5]);
                                $table .= " AND template_name {$comparator[0]} '{$comparator[1]}'";
                            }
                            $table .= "GROUP BY user_id ) as {$fake_name}";

                            return $query->leftJoin(DB::raw($table), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                    "addons"            => [
                        ["key" => "email_id", "type" => "number", "comparators" => self::COMPLEX_NUMBER_COMPARATORS],
                        ["key" => "email_name", "type" => "text", "comparators" => self::DEFAULT_COMPARATORS]
                    ],
                    "can_select"        => false
                ],
                "privacy_bonus_direct_mail"  => [
                    "title"         => "Privacy bonus direct mail",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName('users_settings', $fake_name) , function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "privacy-bonus-direct-mail");
                            });
                        }
                    ],
                ],
                "promotional_send_outs_sms"  => [
                    "title"         => "Promotional send-outs | SMS",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName('users_settings', $fake_name) , function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", DataFormatHelper::getSetting('privacy_main_promo_sms'));
                            });
                        }
                    ]
                ],
                "promotional_send_outs_mail"  => [
                    "title"         => "Promotional send-outs | Email",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "simpleBooleanOptions",
                    "select"        => function ($fake_name) {
                        return self::defaultValueForKey("{$fake_name}.value",0);
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName('users_settings', $fake_name) , function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", DataFormatHelper::getSetting('privacy_main_promo_email'));
                            });
                        }
                    ]
                ],
            ];
        }

        public static function getResponsibleGamblingDataFields()
        {
            return [
                "responsible_gambling_rating"    => [
                    "title"             => "Responsible Gambling Rating",
                    "type"              => self::TYPES["NUMBER"],
                    "comparators"       => self::BASIC_NUMBER_COMPARATORS,
                    "select"        => function ($fake_name) {
                        return "{$fake_name}.rating";
                    },
                    "join"              => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            $condition = self::getPaginationCondition($ids, 'user_id', 'AND');
                            $table = "SELECT user_id, rating FROM risk_profile_rating_log
                                        WHERE id IN(
                                            SELECT max(id) 
                                            FROM risk_profile_rating_log WHERE rating_type = 'RG'
                                            GROUP BY user_id, rating_type
                                        ) {$condition}";

                            return $query->leftJoin(self::setFakeName("({$table})", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id");
                            });
                        }
                    ],
                ],
                "is_self_excluded"          => [
                    "title"         => "Self-Excluded",
                    "comparators"   => self::EQUAL_COMPARATORS,
                    "type"          => self::TYPES["DROPDOWN"],
                    "options_source"=> "booleanOptions",
                    "select"        => function ($fake_name) {
                        return "CASE when IFNULL({$fake_name}.value, CURDATE() - INTERVAL 1 DAY) >= CURDATE() THEN 1 ELSE 0 END";
                    },
                    "join"          => [
                        "callback"  => function($query, $v, $ids, $fake_name)
                        {
                            return $query->leftJoin(self::setFakeName("users_settings", $fake_name), function($q) use ($fake_name)
                            {
                                $q->on("{$fake_name}.user_id", "=", "users.id")
                                    ->where("{$fake_name}.setting", "unexclude-date");
                            });
                        }
                    ],
                ],
            ];
        }

        /**
         * @return array
         */
        public static function getFilterFields()
        {
            return [
                'user_info'             => [
                    'group_name'    => 'User info',
                    'fields'        =>  self::getUserInfoFields()
                ],
                'financials'            => [
                    'group_name'    => 'Financials',
                    'fields'        => self::getFinancialFields()
                ],
                'promotion_stats'       => [
                    'group_name'    => 'Promotion Stats',
                    'fields'        => self::getPromotionStatsFields()
                ],
                'activity_data'         => [
                    'group_name'    => 'Activity Data',
                    'fields'        => self::getActivityDataFields()
                ],
                'communications_data'   => [
                    'group_name'    => 'Communications Data',
                    'fields'        => self::getCommunicationsDataFields()
                ],
                'responsible_gambling'   => [
                    'group_name'    => 'Responsible Gambling',
                    'fields'        => self::getResponsibleGamblingDataFields()
                ],
            ];
        }

        /**
         * @param $key
         * @return mixed
         */
        public function solveTitleKey($key)
        {
            return $this->getFieldsMap()[$key]['title'] ?? $key;
        }

        /**
         * @param $fields
         * @return array|mixed
         */
        public function comparatorParams($fields, $table)
        {
            $comparator = $fields[1];
            $value      = $fields[2];
            $key        = DB::raw($this->solveExceptionKey($fields[0], $table));

            if (strpos($value, 'CURDATE') !== false)
            {
                $value = DB::raw($value);
            }

            $comparators= [
                self::COMPARATORS['EQUALS']             => [
                    "where" => [$key, "=", $value]
                ],
                self::COMPARATORS['NOT_EQUAL']          => [
                    "where" => [$key, '<>', $value],
                    "orWhereNull" => [$key]
                ],
                self::COMPARATORS['AFTER']              => [
                    "where" =>  [$key, '>', $value]
                ],
                self::COMPARATORS['AFTER_AND_EQUAL']    => [
                    "where" =>  [$key, '>=', $value]
                ],
                self::COMPARATORS['GREATER']            => [
                    "where" =>  [$key, '>', $value]
                ],
                self::COMPARATORS['GREATER_AND_EQUAL']  => [
                    "where" =>  [$key, '>=', $value]
                ],
                self::COMPARATORS['BEFORE']             => [
                    "where" =>  [$key, '<', $value]
                ],
                self::COMPARATORS['BEFORE_AND_EQUAL']   => [
                    "where" =>  [$key, '<=', $value]
                ],
                self::COMPARATORS['LESS']               => [
                    "where" =>  [$key, '<', $value]
                ],
                self::COMPARATORS['LESS_AND_EQUALS']    => [
                    "where" =>  [$key, '<=', $value]
                ],
                self::COMPARATORS['STARTS']             => [
                    "where" =>  [$key, 'like', $value . '%']
                ],
                self::COMPARATORS['ENDS_WITH']          => [
                    "where" =>  [$key, 'like', '%' . $value]
                ],
                self::COMPARATORS['CONTAINS']           => [
                    "where" =>  [$key, 'like', '%' . $value . '%']
                ],
                self::COMPARATORS['ANNIVERSARY']        => [
                    "where" =>  [DB::raw("DATE_FORMAT({$key}, '%m-%d')"), '=', DB::raw("DATE_FORMAT('{$value}', '%m-%d')")]
                ]
            ];

            if($comparator === self::COMPARATORS['CUSTOM_COUNTRY_BLOCKED']) {
                $config = Config::where('config_tag', '=', 'countries')->where('config_name', '=', 'deposit-blocked')->first();

                $where = (int) $value === 1 ? 'whereIn' : 'whereNotIn';

                return [
                    $where => [$key, explode(' ', $config->config_value)]
                ];
            }

            if ($comparator === self::COMPARATORS['ANNIVERSARY']) {

                $key =  strpos($key, 'DATE_FORMAT') !== false
                     ?  str_replace('%Y-%m-%d','%m-%d', $key)
                     :  DB::raw("DATE_FORMAT({$key}, '%m-%d')");

                $key .= " = ";

                $key .= strpos($value, 'CURDATE') !== false
                      ? DB::raw("DATE_FORMAT({$value}, '%m-%d')")
                      : DB::raw("DATE_FORMAT('{$value}', '%m-%d')");

                return [
                    "raw" => true,
                    "sql" => $key
                ];
            }
            return $comparators[$comparator];
        }

        /**
         * @param $comparator
         * @param $value
         * @return array
         */
        public static function comparatorMap($comparator, $value)
        {
            return [
                self::COMPARATORS['EQUALS']             => ['=', $value],
                self::COMPARATORS['NOT_EQUAL']          => ['<>', $value],
                self::COMPARATORS['AFTER']              => ['>', $value],
                self::COMPARATORS['AFTER_AND_EQUAL']    => ['>=', $value],
                self::COMPARATORS['GREATER']            => ['>', $value],
                self::COMPARATORS['GREATER_AND_EQUAL']  => ['>=', $value],
                self::COMPARATORS['BEFORE']             => ['<', $value],
                self::COMPARATORS['BEFORE_AND_EQUAL']   => ['<=', $value],
                self::COMPARATORS['LESS']               => ['<', $value],
                self::COMPARATORS['LESS_AND_EQUALS']    => ['<=', $value],
                self::COMPARATORS['STARTS']             => ['like', "{$value}%"],
                self::COMPARATORS['ENDS_WITH']          => ['like', "%{$value}"],
                self::COMPARATORS['CONTAINS']           => ['like', "%{$value}%"],
            ][$comparator];
        }

        /**
         * @return array
         */
        public function getFieldsMap()
        {
            return self::getUserInfoFields()
                 + self::getFinancialFields()
                 + self::getActivityDataFields()
                 + self::getPromotionStatsFields()
                 + self::getCommunicationsDataFields()
                 + self::getResponsibleGamblingDataFields();
        }

        /**
         * @return bool
         */
        public function needsOptions()
        {
            return $this->field['type'] == self::TYPES["DROPDOWN"];
        }

        /**
         * @param $options
         */
        public function setOptions($options)
        {
            $this->field['data'] = $options;
        }

        /**
         * @param null $key
         * @return mixed
         */
        public function get($key=null)
        {
            return $key ? $this->field[$key] : $this->field;
        }

        /**
         * @param string|Builder $table
         * @param string $fake_name
         * @param boolean $is_builder
         * @return mixed
         */
        private static function setFakeName($table, $fake_name)
        {
            return DB::raw("{$table} as {$fake_name}");
        }

        /**
         * Convert php array to sql array.
         * @param $arr
         * @return string
         */
        private static function arrayToSql($arr)
        {
            return "(" . implode(',', $arr) . ")";
        }

        /**
         * Creates a raw sql statement for optimizing queries.
         * @param $ids
         * @param string $column
         * @return string
         */
        private static function getPaginationCondition($ids, $column = 'user_id', $condition = 'WHERE')
        {
            return $ids ? "{$condition} {$column} in " . self::arrayToSql($ids) : '';
        }

        /**
         * @param $key
         * @return mixed
         */
        private function solveExceptionKey($key, $table)
        {
            return (is_callable($this->getFieldsMap()[$key]['select']) ? $this->getFieldsMap()[$key]['select']($table) : $this->getFieldsMap()[$key]['select']) ?? $key;
        }

        /**
         * @param $key
         * @param $default
         * @return string
         */
        private static function defaultValueForKey($key, $default)
        {
            return "IF(IFNULL({$key},'')='','{$default}',{$key})";
        }

        /**
         * Used to fix 000-00-00 dates which will result in unexpected behaviour
         * Usage: ".self::defaultValueForDate('timestamp')."
         * @param $key
         * @return string
         */
        private static function defaultValueForDate($key)
        {
            return "IF(IFNULL({$key},'0000-00-00') = '0000-00-00', '".self::INVALID_DATE_DEFAULT."', DATE_FORMAT({$key}, '%Y-%m-%d'))";
        }
}
