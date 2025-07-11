<?php

return [
    'vs.config' => [
        'active.sections' => [
            'fraud' => true,
            'user.profile' => true,
            'permissions' => true,
            'accounting' => true,
            'licensing' => false,
            'payments' => true,
            'messaging' => true,
            'cms' => true,
            'gamification' => true,
            'settings' => true,
            'rg' => true,
            'monitoring' => true,
            'games' => true,
            'user.download' => false,
            'sportsbook' => true,
            'translate' => true,
        ],
        'archive.db.support' => true,
        'archive.db.support.actions' => true,
        'archive.scale.back' => false,
        'bulk.insert.limit' => 500
    ],
    'vs.api' => [
        'key' => 'KZZUnzHLWEeKDDVOXSeROuM1hFmoEycz'
    ],
    'mts.config' => [
        'base.uri' => 'https://mts.videoslots.com/'
    ],
    'dmapi.config' => [
        'base.uri' => 'https://dmapi.videoslots.com/'
    ],
    'warehouse' => ['url' => 'https://raventrack.videoslots.com/api/'],
    'pr.config' => [
        'base.uri' => 'https://partner.videoslots.com/',
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
        'default_reply_to' => 'news@videoslots.com',
        'support_mail' => 'support@videoslots.com',
    ],
    'mosaico' => [
        'base_url' => "https://www.videoslots.com/",
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
    'bebettor' => [
        'base.uri' => 'https://api.bebettor.com',
        'X-API-KEY' => 'ra17RdOxQs1mQT2uur6m2qSZmMc4LcP5kkcAqoE5',
        'affordability' => 'affordability/v1/check',
    ],
    'locale' => [
        'race' => [
            'headline' => 'Clash Of Spins' // should be 'Lucky Charm Race' for MrV
        ]
    ],
    'sportsbook' => [
        'user-service-sport-url' => getenv('USER_SERVICE_SPORT_URL')
    ]
];
