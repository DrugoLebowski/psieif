<?php

require __DIR__ . '/../vendor/autoload.php';

$target = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (preg_match('/\/(handler){1}/', $target) && $method === 'POST') {
        return include __DIR__.'/../app/controller/handler.php';
    }
} else if ($method === 'GET') {
    if (preg_match('/\/(validate){1}/', $target)) {
        return include __DIR__.'/../app/controller/handler.php';
    } else if (preg_match('/\/(hashes){1}\/[a-zA-Z0-9]+\.(zip){1}/', $target)) {
        $file = preg_split('/\//', $target)[2];
        if (file_exists(__DIR__.'/hashes/'.$file)) {
            return __DIR__.'/hashes/'.$file;
        }
    } else if ('/' === $target) {
        return require __DIR__.'/../app/view/index.html';
    }
}

return require_once '../app/view/404.html';
