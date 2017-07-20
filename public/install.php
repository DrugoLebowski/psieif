<?php

use App\Component\Database;

require __DIR__.'/../vendor/autoload.php';

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

$tmpDir = __DIR__.'/../.tmp';
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0755);
}

$pollerDir = $tmpDir.'/poller';
if (!file_exists($pollerDir)) {
    mkdir($pollerDir, 0755);
}