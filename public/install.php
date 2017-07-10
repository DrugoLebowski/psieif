<?php

use App\Components\Database;

require __DIR__.'/../vendor/autoload.php';

$db = (new Database(
    "localhost",
    "3306",
    "psieif",
    "root",
    "" // Add your password
))->getInstance();

$db->query("DROP TABLE IF EXISTS `posts`;

  CREATE TABLE `posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(128) NOT NULL,
    `hash` varchar(128) NOT NULL,
    `date` date NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hash` (`hash`)
  ) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;"
)->execute();