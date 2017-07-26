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
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(8) NOT NULL,
    `creator` VARCHAR(128) NOT NULL,
    `issuer` VARCHAR(128) NOT NULL,
    `ref_post` LONGTEXT NOT NULL,
    `name` varchar(128) NOT NULL,
    `hash_sha` VARCHAR(128) NOT NULL,
    `hash_md5` VARCHAR(128) NOT NULL,
    `date` DATE NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hash_sha` (`hash_sha`),
    UNIQUE KEY `hash_md5` (`hash_md5`)
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