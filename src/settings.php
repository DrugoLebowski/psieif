<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer'  => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger'    => [
            'name'      => 'slim-app',
            'path'      => __DIR__ . '/../logs/app.log',
            'level'     => \Monolog\Logger::DEBUG,
        ],

        // Database settings
        'database'  => [
            'host'      => 'localhost',
            'port'      => '3306',
            'dbname'    => 'psieif',
            'username'  => 'root',
            'password'  => ''
        ],

        // Facebook app settings
        'facebook'  => [
            'appId'        => '1536013056471626',
            'appSecret'    => '94f915f27c720ae3c8932d1d6470e23a'
        ]
    ],
];
