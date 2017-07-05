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
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'],
        $settings['level']));
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

// Register component on container
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig(__DIR__.'/../templates/', [
        //'cache' => __DIR__.'/../cache/'
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.html', '',
        $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($c['router'],
        $basePath));

    return $view;
};

// Users model manager
$container['usersManager'] = function ($c) {
    return new App\Database\Managers\UsersManager($c);
};

// Posts model manager
$container['postsManager'] = function ($c) {
    return new App\Database\Managers\PostsManager($c);
};

// FB middleware
$container['fb'] = function ($c) {
    $settings = $c->get('settings')['facebook'];
    return new App\Components\FBCaller($settings['appId'],
        $settings['appSecret']);
};

// League/Parse service
$container['parser'] = function ($c) {
    return new \League\Uri\Parser();
};
