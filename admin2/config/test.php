<?php

return [
    'vs.config' => [
        'active.sections' => [
            'fraud' => true,
            'user.profile' => true,
            'permissions' => false,
            'accounting' => true,
            'payments' => true,
            'licensing' => false,
            'messaging' => true,
            'cms' => true,
            'promotions' => true,
            'gamification' => true,
            'config' => true,
            'settings' => true,
            'rg' => true,
            'monitoring' => true,
            'games' => true,
            'user.download' => false,
        ],
        'archive.db.support' => false,
        'archive.db.support.actions' => false,
        'archive.scale.back' => true,
        'bulk.insert.limit' => 500
    ],
    'vs.api' => [
        'key' => 'test'
    ],
    'mts.config' => [
        'base.uri' => 'http://mts-test.videoslots.com/'
    ],
    'dmapi.config' => [
        'base.uri' => 'http://testdmapi.videoslots.com/'
    ],
    'pr.config' => [
        'base.uri' => 'https://partner.videoslots.com/',
        'liability.support' => true,
        'cache.balance.support' => true,
        'basic.auth' => true
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
        'base_url' => "https://test2.videoslots.com/",
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
    'locale' => [
        'race' => [
            'headline' => 'Clash Of Spins'
        ]
    ],
];
