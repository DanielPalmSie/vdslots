<?php

return [
    'vs.menu' => [
        'user.profile' => [
            'name' => 'Users', //'menuer.browseusers'
            'icon' => 'users',
            'permission' => 'users.section',
            'show.submenu' => true,
            'root.url' => 'user',
            'submenu' => [
                [
                    'name' => 'Users Filter',
                    'permission' => 'users.section',
                    'url' => 'user'
                ],
                [
                    'name' => 'List Documents',
                    'permission' => 'menuer.list-documents',
                    'url' => 'admin.user-documents-list'
                ],
                [
                    'name' => 'Similarity',
                    'permission' => 'menuer.check-similarity',
                    'url' => 'admin.fraud.check-similarity'
                ],
                [
                    'name' => 'List SourceOfFunds',
                    'permission' => 'users.list.sourceoffunds',
                    'url' => 'admin.user-sourceoffunds-list'
                ],
                [
                    'name' => 'User Risk Score Report',
                    'permission' => 'users.risk.score.report',
                    'url' => 'admin.user-risk-score-report'
                ],
                [
                    'name' => 'User Follow Up Report',
                    'permission' => 'users.follow.up.report',
                    'url' => 'admin.user-follow-up-report'
                ],
                [
                    'name' => 'Monitored Accounts',
                    'permission' => 'users.monitored.accounts.report',
                    'url' => 'admin.user-monitored-accounts-report'
                ],
                [
                    'name' => 'Monitoring Logs Report',
                    'permission' => 'users.monitoring.log.report',
                    'url' => 'admin.user-monitoring-log-report'
                ],
                [
                    'name' => 'Forced RG Limits Report',
                    'permission' => 'users.force.limits.report',
                    'url' => 'admin.user-force-limits-report'
                ],
                [
                    'name' => 'Documents Management Report',
                    'permission' => 'users.documents.management.report',
                    'url' => 'admin.user-docs-report'
                ],
                /*[
                    'name' => 'Regulatory reports',
                    'permission' => 'users.reports.half-year',
                    'url' => 'admin.reports.half-year'
                ],*/
            ],
            'remove.testUserFlag' => false
        ],
        'fraud' => [
            'name' => 'Fraud',
            'icon' => 'credit-card',
            'permission' => 'fraud.section',
            'show.submenu' => false,
            'root.url' => 'fraud-dashboard',
            'submenu' => [
                [
                    'name' => 'High Depositors',
                    'permission' => 'fraud.section.high-depositors',
                    'url' => 'fraud-high-deposits'
                ],
                [
                    'name' => 'Non Turned-over Withdrawals',
                    'permission' => 'fraud.section.non-turned-over-withdrawals',
                    'url' => 'fraud-non-turned-over-withdrawals'
                ],
                [
                    'name' => 'Anonymous Method Deposits',
                    'permission' => 'fraud.section.anonymous-methods',
                    'url' => 'fraud-anonymous-methods'
                ],
                [
                    'name' => 'Multi-Method Transactions',
                    'permission' => 'fraud.section.multi-method-transactions',
                    'url' => 'fraud-multi-method-transactions'
                ],
                [
                    'name' => 'Big Winners',
                    'permission' => 'fraud.section.big-winners',
                    'url' => 'fraud-big-winners'
                ],
                [
                    'name' => 'Big Losers',
                    'permission' => 'fraud.section.big-losers',
                    'url' => 'fraud-big-losers'
                ],
                [
                    'name' => 'Big Depositors',
                    'permission' => 'fraud.section.big-depositors',
                    'url' => 'fraud-big-depositors'
                ],
                [
                    'name' => 'Battle Of Slots Gladiators',
                    'permission' => 'fraud.section.daily-gladiators',
                    'url' => 'fraud-daily-gladiators'
                ],
                [
                    'name' => 'Failed Deposits',
                    'permission' => 'fraud.section.failed-deposits',
                    'url' => 'fraud-failed-deposits'
                ],
                [
                    'name' => 'Bonus Abusers',
                    'permission' => 'fraud.section.bonus-abusers',
                    'url' => 'fraud-bonus-abusers'
                ],
                [
                    'name' => 'Fraud Rule Sets',
                    'permission' => 'fraud.section.fraud-rule-sets',
                    'url' => 'fraud-rules'
                ],
                [
                    'name' => 'Fraud Groups',
                    'permission' => 'fraud.section.fraud-groups',
                    'url' => 'fraud-groups'
                ],
                [
                    'name' => 'AML Monitoring',
                    'permission' => 'fraud.section.aml-monitoring',
                    'url' => 'fraud-aml-monitoring',
                    'methodName' => 'fraudAmlMonitoring',
                    'method' => 'GET|POST',
                    'dashboard-content' => 'AML Monitoring',
                    'dashboard-icon' => 'fa-lock',
                    'visible' => true
                ],
                [
                    'name' => 'Similar account',
                    'permission' => 'fraud.section.similar-account',
                    'url' => 'similar-account',
                    'methodName' => 'similarAccount',
                    'method' => 'GET|POST',
                    'dashboard-content' => 'Similar account',
                    'dashboard-icon' => 'fa-lock',
                    'visible' => true
                ],
                [
                    'name' => 'Min Fraud',
                    'permission' => 'fraud.section.min-fraud',
                    'url' => 'min-fraud',
                    'methodName' => 'minFraud',
                    'method' => 'GET|POST',
                    'dashboard-content' => 'Min Fraud',
                    'dashboard-icon' => 'fa-lock',
                    'visible' => true
                ],
                [
                    'name' => 'User Risk Score Rating',
                    'permission' => 'fraud.section.user-risk-score',
                    'url' => 'fraud.user-risk-score-report',
                    'methodName' => 'riskScoreReport',
                    'method' => 'GET|POST',
                    'dashboard-content' => 'Fraud User Risk Score',
                    'dashboard-icon' => 'fa-lock',
                    'visible' => true
                ],
                [
                    'name' => 'GoAML',
                    'permission' => 'fraud.section.goaml',
                    'url' => 'fraud.go-aml'
                ],
                [
                    'name' => 'AML Global Risk Score Report',
                    'permission' => 'fraud.section.user-risk-score',
                    'url' => 'fraud.grs-score-report',
                    'methodName' => 'riskScoreReport',
                    'method' => 'GET|POST',
                    'dashboard-content' => 'AML Risk Score Report',
                    'dashboard-icon' => 'fa-lock',
                    'visible' => true
                ],
            ]
        ], 'rg' => [
            'name' => 'Responsible Gaming',
            'icon' => 'cube',
            'show.submenu' => false,
            'root.url' => '/',
            'permission' => 'rg.section',
            'submenu' => [
                [
                    'name' => 'Dashboard'
                    , 'permission' => 'rg.section'
                    , 'url' => '/'
                    , 'method' => 'GET'
                    , 'methodName' => 'dashboard'
                    , 'dashboard-content' => 'Dashboard'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => false
                ]
                , [
                    'name' => 'Limit Changes'
                    , 'permission' => 'rg.section.limit-changes'
                    , 'url' => 'limit-changes'
                    , 'method' => 'GET|POST'
                    , 'methodName' => 'limitChanges'
                    , 'dashboard-content' => 'Rg Limit Changes'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => false
                ]
                , [
                    'name' => 'Responsible Gaming Limits Report'
                    , 'permission' => 'rg.section.self-exclusion'
                    , 'url' => 'self-exclusion-locked-accounts'
                    , 'methodName' => 'selfExclusionLockedAccounts'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Responsible Gaming Limits Report'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Interactions'
                    , 'permission' => 'rg.section.interactions'
                    , 'url' => 'interactions'
                    , 'methodName' => 'interactions'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Interactions'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Change of playing pattern'
                    , 'permission' => 'rg.section.change-playing-pattern'
                    , 'url' => 'change-of-playing-pattern'
                    , 'methodName' => 'changeOfPlayingPattern'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Change of playing pattern'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => false
                ]
                , [
                    'name' => 'Change of deposit pattern'
                    , 'permission' => 'rg.section.change-deposit-pattern'
                    , 'url' => 'change-of-deposit-pattern'
                    , 'methodName' => 'changeOfDepositPattern'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Change of deposit pattern'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Change of wager pattern'
                    , 'permission' => 'rg.section.change-wager-pattern'
                    , 'url' => 'change-of-wager-pattern'
                    , 'methodName' => 'changeOfWagerPattern'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Change of wager pattern'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Frequent account closing and reopening'
                    , 'permission' => 'rg.section.frequent-account-closing-opening'
                    , 'url' => 'frequent-account-closing-and-reopening'
                    , 'methodName' => 'frequentAccountClosingReopening'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Frequent account closing and reopening'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Multiple changes to rg limits'
                    , 'permission' => 'rg.section.multiple-changes-rg-limits'
                    , 'url' => 'multiple-changes-rg-limits'
                    , 'methodName' => 'multipleChangesToRgLimits'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Multiple changes to rg limits'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Extended game play'
                    , 'permission' => 'rg.section.extended-game-play'
                    , 'url' => 'extended-game-play'
                    , 'methodName' => 'extendedGamePlay'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Extended game play'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => false
                ]
                , [
                    'name' => 'Frequent game play'
                    , 'permission' => 'rg.section.frequent-game-play'
                    , 'url' => 'frequent-game-play'
                    , 'methodName' => 'frequentGamePlay'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Frequent game play'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'Cancellation of withdrawals'
                    , 'permission' => 'rg.section.cancellation-of-withdrawals'
                    , 'url' => 'cancellation-of-withdrawals'
                    , 'methodName' => 'cancellationOfWithdrawals'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'Cancellation of withdrawals'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ]
                , [
                    'name' => 'High wagers per bet / spin'
                    , 'permission' => 'rg.section.high-wager-bet-spin'
                    , 'url' => 'high-wagers-per-bet-spin-relative-to-deposits'
                    , 'methodName' => 'highWagersPerBetSpin'
                    , 'method' => 'GET|POST'
                    , 'dashboard-content' => 'High wagers per bet/spin relative to deposits'
                    , 'dashboard-icon' => 'fa-lock'
                    , 'visible' => true
                ],
                [
                        'name' => 'Responsible Gaming Monitoring',
                        'permission' => 'rg.section.monitoring',
                        'url' => 'responsible-gaming-monitoring',
                        'methodName' => 'rgMonitoring',
                        'method' => 'GET|POST',
                        'dashboard-content' => 'Responsible Gaming Monitoring',
                        'dashboard-icon' => 'fa-lock',
                        'visible' => true
                ],
                [
                        'name' => 'User Risk Score Rating',
                        'permission' => 'rg.section.user-risk-score',
                        'url' => 'user-risk-score',
                        'methodName' => 'riskScoreReport',
                        'method' => 'GET|POST',
                        'dashboard-content' => 'Responsible Gaming User Risk Score',
                        'dashboard-icon' => 'fa-lock',
                        'visible' => true
                ],
                [
                        'name' => 'User Interaction Result Report',
                        'permission' => 'rg.section.interaction-result-report',
                        'url' => 'interaction-result-report',
                        'methodName' => 'interactionResultReport',
                        'method' => 'GET|POST',
                        'dashboard-content' => 'User interaction result report',
                        'dashboard-icon' => 'fa-lock',
                        'visible' => true
                ],
                [
                    'name' => 'RG Global Risk Score Report',
                    'permission' => 'rg.section.user-risk-score',
                    'url' => 'rg.grs-score-report',
                    'visible' => true,
                    'method' => 'GET|POST',
                    'dashboard-content' => 'RG Global Risk Score Report',
                    'dashboard-icon' => 'fa-lock',
                ],
            ]
        ],
        'accounting' => [
            'name' => 'Accounting',
            'icon' => 'university',
            'permission' => 'accounting.section',
            'show.submenu' => false,
            'root.url' => 'accounting-index',
            'submenu' => [
                [
                    'name' => 'Player Liability',
                    'permission' => 'accounting.section.liability',
                    'url' => 'accounting-liability'
                ],
                [
                    'name' => 'Site Balance Report',
                    'permission' => 'accounting.section.site-balance',
                    'url' => 'accounting-site-balance'
                ],
                [
                    'name' => 'Player Balance Report',
                    'permission' => 'accounting.section.player-balance',
                    'url' => 'accounting-player-balance'
                ],
                [
                    'name' => 'Transaction History',
                    'permission' => 'accounting.section.transaction-history',
                    'url' => 'accounting-transaction-history'
                ],
                [
                    'name' => 'Pending Withdrawals',
                    'permission' => 'accounting.section.pending-withdrawals',
                    'url' => 'accounting-pending-withdrawals'
                ],
                [
                    'name' => 'Consolidation',
                    'permission' => 'accounting.section.consolidation',
                    'url' => 'accounting-consolidation',
                    'hidden' => true
                ],
                /* [
                     'name' => 'Transfer Stats',
                     'permission' => 'accounting.section.transfer-stats',
                     'url' => 'accounting-transfer-stats'
                 ]*/
                [
                    'name' => 'Gaming Revenue',
                    'permission' => 'accounting.section.gaming-revenue',
                    'url' => 'accounting-gaming-revenue-report'
                ],
                [
                    'name' => 'Jackpots Log',
                    'permission' => 'accounting.section.jackpot-logs',
                    'url' => 'accounting-jackpot-log'
                ],
                [
                    'name' => 'Open Bets',
                    'permission' => 'accounting.section.open-bets',
                    'url' => 'accounting-open-bets'
                ],
                [
                    'name' => 'Vaults',
                    'permission' => 'accounting.section.liability',
                    'url' => 'accounting-vaults'
                ],
            ]
        ],
        'payments' => [
            'name' => 'Payments',
            'icon' => 'money-bill',
            'permission' => 'admin.payments',
            'show.submenu' => false,
            'root.url' => 'index',
            'submenu' => [
                [
                    'name' => 'Pending Deposits',
                    'permission' => 'admin.payments.pending.deposits',
                    'url' => 'pending-deposits'
                ],
                [
                    'name' => 'BINs Blacklist',
                    'permission' => 'admin.payments.bin-blacklist',
                    'url' => 'bin-blacklist.index'
                ]
            ]
        ],
        'licensing' => [
            'name' => 'Licensing',
            'icon' => 'globe',
            'permission' => 'licensing.section',
            'show.submenu' => false,
            'root.url' => 'licensing-index',
            'submenu' => [
                [
                    'name' => 'Jackpot Log',
                    'permission' => 'licensing.view.jackpotlog',
                    'url' => 'accounting-liability'
                ],
            ]
        ],
        'cms' => [
            'name' => 'CMS',
            'icon' => 'tags',
            'permission' => 'cms.section',
            'show.submenu' => false,
            'root.url' => 'cms-dashboard',
            'submenu' => [
                [
                    'name' => 'Banners Uploads',
                    'permission' => 'cms.banners.upload',
                    'url' => 'banneruploads'
                ],
                [
                    'name' => 'Banners Tags',
                    'permission' => 'cms.banners.tag',
                    'url' => 'bannertags'
                ],
                [
                    'name' => 'Image Uploads',
                    'permission' => 'cms.upload.images',
                    'url' => 'uploadimages'
                ],
                [
                    'name' => 'File Uploads',
                    'permission' => 'cms.upload.files',
                    'url' => 'uploadfiles'
                ],
                [
                    'name' => 'Page backgrounds',
                    'permission' => 'cms.change.pagebackgrounds',
                    'url' => 'pagebackgrounds'
                ]
            ]
        ],
        'messaging' => [
            'name' => 'Messaging',
            'icon' => 'envelope',
            'permission' => 'messaging.section',
            'show.submenu' => true,
            'root.url' => 'messaging.index',
            'submenu' => [
                'dashboard' => [
                    'name' => 'Dashboard',
                    'permission' => 'messaging.section',
                    'url' => 'messaging.index'
                ],
                'contacts' => [
                    'name' => 'Contacts',
                    'permission' => 'messaging.contacts',
                    'url' => 'messaging.contact.list-filters'
                ],
                'promotions' => [
                    'name' => 'Promotion Codes',
                    'permission' => 'messaging.promotions',
                    'url' => 'messaging.bonus.list'
                ],
                'sms' => [
                    'name' => 'Send SMS',
                    'permission' => 'messaging.sms',
                    'url' => 'messaging.sms-templates',
                ],
                'email' => [
                    'name' => 'Send Email',
                    'permission' => 'messaging.email',
                    'url' => 'messaging.email-templates',
                ],
                'offline' => [
                    'name' => 'Offline Campaigns',
                    'permission' => 'messaging.offline-campaigns',
                    'url' => 'messaging.offline-campaigns',
//                    'hidden'=> true
                ],
                'reports' => [
                    'name' => 'Reports',
                    'permission' => 'messaging.reports',
                    'url' => 'messaging.contact.list-filters',
                    'hidden' => true
                ],
            ]
        ],
        'promotions' => [
            'name' => 'Promotions',
            'icon' => 'th-large',
            'permission' => 'admin.promotions',
            'show.submenu' => false,
            'root.url' => 'promotions.dashboard',
            'submenu' => [
                'races' => [
                    'name' => 'Races',
                    'permission' => 'promotions.races',
                    'url' => 'promotions.races.index'
                ],
                'bonustypes' => [
                    'name' => 'Bonus Types',
                    'permission' => 'promotions.races',
                    'url' => 'bonustypes.index'
                ],
            ]
        ],

        'settings' => [
            'name' => 'Settings',
            'icon' => 'cog',
            'show.submenu' => true,
            'root.url' => 'settings-dashboard',
            'permission' => 'settings.section',
            'submenu' => [
                'config' => [
                    'name' => 'Config',
                    'icon' => 'gear',
                    'permission' => 'config.section',
                    'show.submenu' => false,
                    'url' => 'settings.config.index',
                    'submenu' => []
                ],
                'permissions' => [
                    'name' => 'Permissions',
                    'permission' => 'view.user.groups',
                    'url' => 'settings.permissions',
                ],
                'triggers' => [
                    'name' => 'Triggers',
                    'permission' => 'settings.triggers.section',
                    'url' => 'settings.triggers.index',
                ],
                'aml-profile-settings' => [
                    'name' => 'AML Profile Settings',
                    'permission' => 'settings.aml-profile.section',
                    'url' => 'settings.aml-profile.index',
                ],
                'rg-profile-settings' => [
                    'name' => 'RG Profile Settings',
                    'permission' => 'settings.rg-profile.section',
                    'url' => 'settings.rg-profile.index',
                ],


            ]
        ],
        'gamification' => [
            'name' => 'Gamification',
            'icon' => 'gamepad',
            'show.submenu' => true,
            'root.url' => 'gamification-dashboard',
            'permission' => 'gamification.section',
            'submenu' => [
                'trophyawards' => [
                    'name' => 'Trophy Awards',
                    'permission' => 'trophyawards.section',
                    'url' => 'trophyawards.index'
                ],
                'trophies' => [
                    'name' => 'Trophies',
                    'permission' => 'trophies.section',
                    'url' => 'trophies.index',
                ],
                'tournamenttemplates' => [
                    'name' => 'Tournament Templates',
                    'permission' => 'tournamenttemplates.section',
                    'url' => 'tournamenttemplates.index',
                ],
                'tournaments' => [
                    'name' => 'Tournaments',
                    'permission' => 'tournaments.section',
                    'url' => 'tournaments.index',
                ],
            	'wheelofjackpots' =>[
            		'name'       => 'Wheel of Jackpots',
            		'permission' => 'wheelofjackpots.section',
            		'url'        => 'wheelofjackpots',
            	],
                'jackpot' => [
                    'name'       => 'Jackpot',
                    'permission' => 'jackpot.section',
                    'url'        => 'jackpot.index',
                ],
                'bonustypes' => [
                    'name'          => 'Bonus Types',
                    'permission'    => 'bonustypes.section',
                    'url'           => 'bonustypes.index'
                ],
                'racetemlpates' => [
                    'name'          => 'Race Templates',
                    'permission'    => 'racetemplates.section',
                    'url'           => 'racetemplates.index'
                ],
            ]
        ],
        'games' => [
            'name' => 'Games',
            'icon' => 'gamepad',
            'show.submenu' => true,
            'root.url' => 'game.dashboard',
            'permission' => 'games.section',
            'submenu' => [
                'game-overrides' => [
                    'name' => 'Game Overrides',
                    'permission' => 'game.override',
                    'url' => 'games-override',
                ],
                'games' => [
                    'name' => 'Games',
                    'permission' => 'settings.games.section',
                    'url' => 'settings.games.index',
                ],
                'operators' => [
                    'name' => 'Operators',
                    'permission' => 'settings.operators.section',
                    'url' => 'settings.operators.index',
                ],
            ]
        ],
        'sportsbook' => [
            'name' => 'Sportsbook',
            'icon' => 'bicycle',
            'show.submenu' => true,
            'root.url' => 'sportsbook.index',
            'permission' => 'admin.sportsbook.index',
            'submenu' => [
                [
                    'name' => 'Clean Events',
                    'permission' => 'admin.sportsbook.clean-events',
                    'url' => 'sportsbook.clean-events'
                ],
                [
                    'name' => 'Unsettled Bets',
                    'permission' => 'admin.sportsbook.download-not-settled-tickets',
                    'url' => 'sportsbook.unsettled-tickets-report'
                ],
            ]
        ],
        'translate' => [
            'name' => 'Translate',
            'icon' => 'language',
            'root.url' => 'translate.index',
            'permission' => 'admin.translate.deepl',
        ],
    ],
    'vs.sections' => [
        'user.main-info' => [
            'show.brands-links' => false,
        ],
    ],
];
