<?php

use App\Components\Database;

$settings = require __DIR__ . '/../config/settings.php';

// Initialize handler
$db = (new Database(
    $settings['database']['host'],
    $settings['database']['port'],
    $settings['database']['dbname'],
    $settings['database']['user'],
    $settings['database']['password']
))->getInstance();

// Retrieves data
$code   = $_GET['code'];
$hash   = $_GET['hash'];

if (!is_null($code) && !empty($code) && !is_null($hash) && !empty($hash)) {
    $query  = $db->prepare(
        'SELECT * ' .
        'FROM posts ' .
        'WHERE code = :code AND hash = :hash'
    );
    $query->bindParam(':code', $code, PDO::PARAM_STR);
    $query->bindParam(':hash', $hash, PDO::PARAM_STR);
    if (!$query->execute()) {
        http_response_code(500);
        exit(json_encode([
            'status'    => 'ERROR',
            'code'      => '@invalid_input_error',
            'data'      => [
                'message'   => 'There is/are error/s with the input data.'
            ]
        ]));
    }

    if (count($query->fetchAll()) === 1) {
        http_response_code(200);
        exit(json_encode([
            'status'    => 'OK',
            'data'      => [
                'message'   => 'Code and hash are correct. The resource is verified.'
            ]
        ]));
    } else {
        http_response_code(404);
        exit(json_encode([
            'status'    => 'ERROR',
            'code'      => '@code_hash_validation_error',
            'data'      => [
                'message'   => 'Does not exists an hash associated to that code.'
            ]
        ]));
    }
} else {
    http_response_code(400);
    exit(json_encode([
        'status'    => 'ERROR',
        'code'      => '@invalid_input_error',
        'data'      => [
            'message'   => 'There is/are error/s with the input data.'
        ]
    ]));
}

