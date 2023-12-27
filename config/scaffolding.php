<?php

declare(strict_types=1);

return [
    'global' => [
        'paths' => [
            'providers' => 'app/Providers',
            'repositories' => 'app/Repositories',
        ],

        'namespaces' => [
            'providers' => 'App\\Providers',
            'repositories' => 'App\\Repositories',
        ],
    ],

    'repository' => [
        'databases' => [
            'MySQL',
            'PostgreSQL',
            'SQLite',
        ],

        'default_database' => 'MySQL',

        'stubs' => null,
    ],
];
