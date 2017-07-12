<?php

use App\Components\Database;

require __DIR__.'/../vendor/autoload.php';

// Requires the application settings
$settings = require __DIR__ . '/../config/settings.php';

$db = (new Database(
    $settings['database']['host'],
    $settings['database']['port'],
    $settings['database']['dbname'],
    $settings['database']['user'],
    $settings['database']['password']
))->getInstance();

$db->query("DROP TABLE IF EXISTS `posts`;

  CREATE TABLE `posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(8) NOT NULL,
    `creator` varchar(128) NOT NULL,
    `ref_post` LONGTEXT NOT NULL,
    `hash` varchar(128) NOT NULL,
    `date` date NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hash` (`hash`)
  ) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;"
)->execute();