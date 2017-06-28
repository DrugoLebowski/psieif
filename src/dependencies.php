<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// database
$container['db'] = function ($c) {
    $settings = $c->get('settings')['database'];
    return new App\Database\Database(
        $settings['host'],
        $settings['port'],
        $settings['dbname'],
        $settings['username'],
        $settings['password']
    );
};

$container[\App\Database\Managers\UsersManager::class] = function ($c) {
    return new App\Database\Managers\UsersManager($c);
};

$container[\App\Database\Managers\PostsManager::class] = function ($c) {
    return new App\Database\Managers\PostsManager($c);
};
