<?php

use App\Components\Util;
use App\Components\Database;
use League\Uri\Schemes\Http as HttpUri;
use League\Uri\Components\Query;
use League\Uri\Components\HierarchicalPath;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

require __DIR__ . '/../vendor/autoload.php';

// Initialize handler
$host           = $_SERVER['HTTP_HOST'];
$baseTmpPath    = __DIR__.'/../.tmp/';
$baseSavingPath = __DIR__ . '/hashes/';
$db             = new Database('localhost', '3306',
    'psieif', 'root', 's4npaol0');
try {
    $fb    = new Facebook([
        'app_id'                => '1536013056471626',
        'app_secret'            => '94f915f27c720ae3c8932d1d6470e23a',
        'default_graph_version' => 'v2.9'
    ]);
} catch(\Exception $e) {
    http_response_code(500);
    exit(json_encode([
        'status'    => 'ERROR',
        'code'      => '@facebook_sdk_error',
        'data'      => [
            'message'   => $e->getMessage()
        ]
    ]));
}

// Retrieves the data sent from the client
$accessToken    = $_COOKIE['at'];
$resourceLink   = $_POST['link'];

// Checks if the session is valid
$session = Util::makeFBRequest($fb, '/me', $accessToken);

if (empty($session['error'])) {
    if (Util::validateFBUrl($resourceLink)) {

        try {
            $uri = HttpUri::createFromString($resourceLink);
        } catch (\Exception $e) {
            http_response_code(500);
            exit(json_encode([
                'status'    => 'ERROR',
                'code'      => '@unkown_error',
                'data'      => [
                    'message'   => $e->getMessage()
                ]
            ]));
        }

        // Analyzes the resource link to retrieve the page ID and post ID
        $path = $uri->getPath();
        $type = Util::getFBUrlType($path);
        $pageId = "";
        $postId = "";
        switch ($type) {
            case Util::POST:
            case Util::VIDEO:
            case Util::PAGE_PHOTO:
                $pathSegments = (new HierarchicalPath($path))->getSegments();
                $entityFBLink = 'https://www.facebook.com/'.$pathSegments[0];
                $postId       = empty($pathSegments[3]) ?
                    $pathSegments[2] : $pathSegments[3];
                $pageId = Util::makeFBRequest($fb, '/?id='.$entityFBLink,
                    $accessToken)['id'];
                break;
            case Util::PERMALINK:
                $query = new Query($uri->getQuery());
                $pageId = $query->getPairs()['id'];
                $postId = $query->getPairs()['story_fbid'];
            default:
        }

        // Retrieve the data associated to the facebook resource
        $resourceId = $pageId.'_'.$postId;
        $data = Util::makeFBRequest($fb,
            '/'.$resourceId.'?fields=attachments,source,comments.limit(10000000){likes,attachment,comments.limit(10000000){attachment,message,from},from,message}',
            $accessToken
        );

        // If there are not error, then manipulate and save the facebook response
        if (empty($data['error'])) {

            /** @var PDO $dbInstance */
            $dbInstance = $db->getInstance();
            $parsedData = [];

            // Where the files about a post will be saved
            $version = count(glob($baseTmpPath.$resourceId.'*')) + 1;
            $resourceDir  = $resourceId.'-'.$version;
            $tmpSavePath  = $baseTmpPath.$resourceDir;
            $tmpSaveOutPath = $tmpSavePath.'/output';
            mkdir($tmpSavePath, 0755); // The file will be saved

            // Saves the attachments, if it exists in the downloaded resource
            if (!empty($data['attachments'])) {
                mkdir($tmpSavePath.'/attachments', 0755);
                $parsedData['attachments'] = $data['attachments']['data'];
                foreach ($parsedData['attachments'] as $attachment) {
                    $src     = $attachment['media']['image']['src'];
                    $imageId = $resourceId.'.jpg';
                    file_put_contents($tmpSavePath.'/attachments/'.$imageId,
                        fopen($src, 'r'));
                }
            }

            // Saves the comments (and sub-comments), if it exists in the downloaded resource
            if (!empty($data['comments'])) {
                mkdir($tmpSavePath.'/comments', 0755);
                $parsedData['comments'] = $data['comments']['data'];
                // TODO: save comments
            }

            // Saves the source, if it exists in the downloaded resource
            if (!empty($data['source'])) {
                mkdir($tmpSavePath.'/videos', 0755);
                $parsedData['source'] = $src = $data['source'];
                $sourceId = $resourceId.'.mp4';
                file_put_contents($tmpSavePath.'/videos/'.$sourceId,
                    fopen($src, 'r'));
            }

            // Saves the parsed response
            file_put_contents($tmpSavePath.'/data.json',
                serialize(json_encode($parsedData)));


            // Makes hash (the footprint) of the post's zip
            mkdir($tmpSaveOutPath, 0755);
            $zipName = Util::makeDirZip($resourceDir, $tmpSavePath,
                $tmpSaveOutPath);
            $hash = hash_file('sha256',
                $tmpSaveOutPath.'/'.$zipName);
            file_put_contents($tmpSaveOutPath.'/'.$hash, "");
            $zipName = Util::makeDirZip($hash, $tmpSaveOutPath.'/',
                $baseSavingPath);

            // Checks if the hash already exists in the DB
            $query = $dbInstance->prepare(
                'SELECT * '.
                'FROM posts '.
                'WHERE hash=:hash'
            );
            $query->execute([':hash' => $hash]);
            $rows = $query->fetchAll();
            if (count($rows) !== 0) {
                $zipName = $rows[0]['name'];
                $zipPath = $baseSavingPath.'/'.$zipName;
            } else {
                // Now saves the post hash to the database
                $query = $dbInstance->prepare(
                    'INSERT INTO posts(name, hash, date)'.
                    'VALUES (:name, :hash, :date)'
                );
                $query->bindParam(':name', $zipName);
                $query->bindParam(':hash', $hash);
                $query->bindParam(':date', date('Y-n-j'));
                if (!$query->execute()) {
                    http_response_code(500);
                    exit(json_encode([
                        'status'    => 'ERROR',
                        'code'      => '@query_execution_error',
                        'data'      => [
                            'message'   => 'The query was not executed.',
                            'reason'    => $query->errorInfo()
                        ]
                    ]));
                }

                // Flushes the working directory
            }
            system('rm -rf ' . escapeshellarg($tmpSavePath));


            // Finally, sends the response
            http_response_code(200);
            exit(json_encode([
                'status'    => 'OK',
                'data'      => [
                    'location'  => "http://".$host.'/hashes/'.$zipName
                ]
            ]));
        } else {
            http_response_code(400);
            exit(json_encode([
                'status'    => 'ERROR',
                'code'      => '@facebook_data_fetching_error',
                'data'      => [
                    'message'   => 'There is a problem with the facebook data fetching.'
                ]
            ]));
        }
    } else {
        http_response_code(400);
        exit(json_encode([
            'status'    => 'ERROR',
            'code'      => '@invalid_uri_error',
            'data'      => [
                'message'   => 'There is a problem with the URI.'
            ]
        ]));
    }

} else {
    http_response_code(400);
    exit(json_encode([
        'status'    => 'ERROR',
        'code'      => '@access_token_not_valid_error',
        'data'      => [
            'message'   => 'The access token is not valid.'
        ]
    ]));
}
