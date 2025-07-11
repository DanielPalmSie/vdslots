<?php

return [
    'vs.config' => [
        'active.sections' => [
            'fraud' => true,
            'user.profile' => true,
            'accounting' => true,
            'payments' => true,
            'licensing' => false,
            'messaging' => true,
            'cms' => true,
            'promotions' => true,
            'settings' => true,
            'gamification' => true,
            'rg' => true,
            'monitoring' => true,
            'triggers' => true,
            'games' => true,
            'user.download' => false,
            'sportsbook' => true,
            'translate' => true,
        ],
        'archive.db.support' => false,
        'archive.db.support.actions' => false,
        'archive.scale.back' => false,
        'bulk.insert.limit' => 500
    ],
    'vs.api' => [
        'key' => 'test3'
    ],
    'mts.config' => [
        'base.uri' => 'http://mts-test.videoslots.com/'
    ],
    'dmapi.config' => [
        'base.uri' => 'http://dmapi.videoslots.loc/'
    ],
    'pr.config' => [
        'base.uri' => 'http://partner.videoslots.loc/',
        'liability.support' => true,
        'cache.balance.support' => true,
        'basic.auth' => false
    ],
    'messaging' => [
        'default_from_email' => 'news@videoslots.com',
        'default_from_email_name' => 'Videoslots News',
        'transactional_from_email' => 'news@videoslots.com',
        'transactional_from_email_name' => 'Videoslots.com',
        'test_from_email' => 'notifications@videoslots.com',
        "default_reply_to" => "news@videoslots.com",
        "support_mail" => "support@videoslots.com",
    ],
    'mosaico' => [
        'base_url' => "http://www.videoslots.loc/",
        'base_dir' => "/var/www/admin2/",
        'uploads_url' => "phive/admin/customization/plugins/mosaico-template/email-templates/",
        'uploads_dir' => "phive_admin/customization/plugins/mosaico-template/email-templates/",
        'static_url' => "phive/admin/customization/plugins/mosaico-template/email-templates/static/",
        'static_dir' => "phive_admin/customization/plugins/mosaico-template/email-templates/static/",
        'thumbnails_url' => "phive/admin/customization/plugins/mosaico-template/email-templates/thumbnail/",
        'thumbnails_dir' => "phive_admin/customization/plugins/mosaico-template/email-templates/thumbnail/",
        'thumbnail_width' => 90,
        'thumbnail_height' => 90
    ],
    'wiraya' => [
        'url' => 'https://api.wiraya.ai'
    ],
    'mailer.provider' => [
        'priority_map' => [
            3 => 'SparkPost'
        ],
        'default' => 'SMTP',
    ],
    'bebettor' => [
        'base.uri' => 'https://sandbox-api.bebettor.com',
        'X-API-KEY' => 'd5tSjjFxIL42hxSMLs27Y11Mhw0XI9tV3nwoMk08',
        'affordability' => 'affordability/v1/check',
    ],
    'locale' => [
        'race' => [
            'headline' => 'Clash Of Spins'
        ]
    ],
    'sportsbook' => [
        'user-service-sport-url' => getenv('USER_SERVICE_SPORT_URL')
    ]
];
