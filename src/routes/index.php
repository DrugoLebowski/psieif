<?php

use League\Uri\Parser;
use League\Uri\Schemes\Http;
use League\Uri\Components\HierarchicalPath;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $this->logger->info("Facebook Psieif '/' route");
    

    // Render index view
    return $this->view->render($response, 'pages/welcome.twig', $args);
});

$app->get('/hash', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $this->logger->info("Facebook Psieif '/' route");
    $accessToken = $request->getCookieParams()['at'];

    if (!$this->fb->validSession($accessToken)) {
        return $response->withStatus(400)
            ->withHeader('Location','/');
    }

    return $this->view->render($response, 'pages/form.twig', $args);
});

$app->post('/hash/content', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {

    $body           = $request->getParsedBody();
    $link           = $body['link'];
    $accessToken    = $request->getCookieParams()['at'];

    $this->logger->info("Facebook Psieif '/hash' route " . $body['link']);

    if ($this->fb->validSession($accessToken)) {
        if (!is_null($link) && !empty($link) &&
            filter_var($link, FILTER_VALIDATE_URL) !== false) {

            // Transforms the $link in a object, so it can be manipulated
            $uri = '';
            try {
                $uri = Http::createFromString($link);
            } catch (\Exception $e) {
                $this->logger->error("Facebook Psieif '/hash' route " . $e->getMessage());
            }


            // Retrieves the segments of the uri
            $path = new HierarchicalPath($uri->getPath());
            $pathSegments = $path->getSegments();
            $pageName = $pathSegments[0];

            if (is_null($pageName) || empty($pageName)) {
                $payload = [
                    "status"    => "ERROR",
                    "code"      => "@not_a_page_error",
                    "data"      => [
                        "message"   => "The sent URI is not connected to a page."
                    ]
                ];

                return $response->withJson($payload, 404);
            }

            $normalizedLink = 'https://www.facebook.com/'.$pageName;
            $postId = empty($pathSegments[count($pathSegments) - 1]) ?
                $pathSegments[count($pathSegments) - 1] :
                $pathSegments[count($pathSegments) - 2];
            $pageId = $this->fb->get('/?id={}', [$normalizedLink], ['id'], $accessToken);

            $data = $this->fb->get('{}_{}/comments', [$pageId['id'], $postId], ['data'], $accessToken);

            if (!is_null($data)) {
                $payload = [
                    'status'    => 'OK',
                    'data'      => $data
                ];
                return $response->withJson($payload, 200);
            } else {
                $payload = [
                    'status'    => 'ERROR',
                    'code'      => '@facebook_data_fetching_error',
                    'data'      => [
                        'message'   => 'There is a problem with the facebook data fetching.'
                    ]
                ];
                return $response->withJson($payload, 400);
            }
        } else {
            $payload = [
                'status'    => 'ERROR',
                'code'      => '@invalid_uri_error',
                'data'      => [
                    'message'   => 'There is a problem with the URI.'
                ]
            ];
            return $response->withJson($payload, 400);
        }

    } else {
        $payload = [
            'status'    => 'ERROR',
            'code'      => '@access_token_not_valid_error',
            'data'      => [
                'message'   => 'The access token is not valid.'
            ]
        ];
        return $response->withJson($payload, 400);
    }

});