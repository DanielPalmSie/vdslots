<?php

/**
 * Global Rating Score
 */

return [
    'grs_tags' => [
        'Social Gambler',
        'Low Risk',
        'Medium Risk',
        'High Risk',
    ],
    'grs_tag_color_map' => [
        'Social Gambler' => '#59b300',
        'Low Risk' => '#8cd98c',
        'Medium Risk' => '#fe7b1e',
        'High Risk' => '#fe1e2c',
        'default' => '#a3a3a3',
    ],

    /**
     * Used as default start points in profile rating range filter
     */
    'grs_range_filter_map' => [
        'Social Gambler' => 0,
        'Low Risk' => 26,
        'Medium Risk' => 51,
        'High Risk' => 100,
    ],
    /**
     * NOTE: one a flag was added to one of the group below
     * don't forget to duplicate it in lic config on the Phive side in `triggers_of_grs_recalculation`.
     * Since the Phive app should know which flag force the immediate GRS recalculation
     *
     * array{
     *  jurisdiction: array{
     *      array{
     *          trigger_name: string, // required
     *          past: int, // required. The number of <period> the GRS should be set for
     *          period: string, // required. Within defined period of time: hours, days, months, years
     *          occurrence: int, // optional. Default: 1. How many times a flag should occur to influence the GRS
     *          before_past: int, // optional. Default: 0. If is set then data is fetched between now()->sub(latest_flag_triggered)->sub(before_past) and now()->sub(past)
     *      }
     *  }
     * }
     */
    'aml_highrisk_triggers' => [
        'DGOJ' => [
            [
                'trigger_name' => 'AML55',
                'past' => 3,
                'period' => 'months'
            ],
        ],
        'AGCO' => [
            [
                'trigger_name' => 'AML61',
                'past' => 10,
                'period' => 'years',
                'occurrence' => 2,
            ],
        ],
        'SGA' => [
            [
                'trigger_name' => 'AML64',
                'past' => 7,
                'period' => 'days',
                'occurrence' => 1,
            ],
        ],
        'DGA' => [
            [
                'trigger_name' => 'AML64',
                'past' => 7,
                'period' => 'days',
                'occurrence' => 1,
            ],
        ],
        'ALL' => [
            [
                'trigger_name' => 'AML24',
                'past' => 1,
                'period' => 'months'
            ],
            [
                'trigger_name' => 'AML37',
                'past' => 6,
                'period' => 'months'
            ],
            [
                'trigger_name' => 'AML57',
                'past' => 1,
                'period' => 'months'
            ],
            [
                'trigger_name' => 'AML58',
                'past' => 7,
                'period' => 'days',
                'occurrence' => 2,
                'before_past' => 30
            ],
            [
                'trigger_name' => 'AML62',
                'past' => 7,
                'period' => 'days',
            ],
        ]
    ],
    'aml_mediumrisk_triggers' => [
        'ALL' => [
            [
                'trigger_name' => 'AML58',
                'past' => 7,
                'period' => 'days'
            ],
        ],
    ],
    'rg_highrisk_triggers' => [
        'ALL' => [
            [
                'trigger_name' => 'RG3',
                'past' => 30,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG38',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG39',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG44',
                'past' => 14,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG47',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG48',
                'past' => 14,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG49',
                'past' => 30,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG50',
                'past' => 90,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG51',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG52',
                'past' => 1,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG53',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG54',
                'past' => 90,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG55',
                'past' => 90,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG56',
                'past' => 90,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG57',
                'past' => 90,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG67',
                'past' => 7,
                'period' => 'days',
                'occurrence' => 2,
                'before_past' => 30
            ],
        ],
        'UKGC' => [
            [
                'trigger_name' => 'RG70',
                'past' => 7,
                'period' => 'days',
            ],
        ],
    ],
    'rg_mediumrisk_triggers' => [
        'DGA' => [
            [
                'trigger_name' => 'RG20',
                'past' => 7,
                'period' => 'days'
            ],
        ],
        'SGA' => [
            [
                'trigger_name' => 'RG20',
                'past' => 7,
                'period' => 'days'
            ],
        ],
        'UKGC' => [
            [
                'trigger_name' => 'RG19',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG20',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG69',
                'past' => 7,
                'period' => 'days'
            ],
        ],
        'ALL' => [
            [
                'trigger_name' => 'RG13',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG14',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG66',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG67',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG72',
                'past' => 7,
                'period' => 'days',
            ],
            [
                'trigger_name' => 'RG73',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG74',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG75',
                'past' => 7,
                'period' => 'days',
            ],
            [
                'trigger_name' => 'RG77',
                'past' => 7,
                'period' => 'days',
            ],
            [
                'trigger_name' => 'RG78',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG79',
                'past' => 7,
                'period' => 'days',
            ],
            [
                'trigger_name' => 'RG80',
                'past' => 7,
                'period' => 'days'
            ],
            [
                'trigger_name' => 'RG81',
                'past' => 7,
                'period' => 'days',
            ],
            [
                'trigger_name' => 'RG82',
                'past' => 7,
                'period' => 'days',
            ],
        ],
    ],
];
