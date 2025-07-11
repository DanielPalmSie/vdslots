<?php

if (env('APP_ENV') === 'dev') {
    return [
        'types' => [
            'casino',
            'casinowager',
            'freespin'
        ],

        'progress_types' => [
            'bonus',
            'both',
            'cash'
        ],
    ];
}

if (env('APP_ENV') === 'prod') {
    return [
        'types' => [
            'casino',
            'casinowager',
            'freesoub',
            'freespin'
        ],

        'progress_types' => [
            'both',
            'bonus',
            'cash',
            'bpmis'
        ],
    ];
}

return [];
