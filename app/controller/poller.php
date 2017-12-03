<?php

use App\Component\ProgressPollerHandler;

$resource = $_POST['resource'];

if (!is_null($resource) && !empty($resource)) {
    try {
        $resourceHandler = new ProgressPollerHandler(
            $settings['poll_resource_path'], $resource);
    } catch (\Exception $e) {
        http_response_code(500);
        exit(json_encode([
            'status'    => 'ERROR',
            'code'      => '@progress_poller_handler_error',
            'data'      => [
                'message'   => $e->getMessage()
            ]
        ]));
    }

    if ($resourceHandler->resourceExists()) {
        http_response_code(200);
        exit(json_encode([
            'status'    => 'OK',
            'data'      => $resourceHandler->readFromPoll()
        ]));
    } else {
        http_response_code(404);
        exit(json_encode([
            'status'    => 'ERROR',
            'code'      => '@resource_not_found_error',
            'data'      => [
                'message'   => 'The requested resource does not exists.'
            ]
        ]));
    }
} else {
    http_response_code(400);
    exit(json_encode([
        'status'    => 'ERROR',
        'code'      => '@invalid_resource_error',
        'data'      => [
            'message'   => 'Empty or null resource.'
        ]
    ]));
}
