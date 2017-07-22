<?php

use App\Component\Util;
use App\Component\Database;
use App\Component\ProgressPollerHandler;
use League\Uri\Schemes\Http as HttpUri;
use League\Uri\Components\Query;
use League\Uri\Components\HierarchicalPath;
use Facebook\Facebook;

// Initialize handler
$host           = $_SERVER['HTTP_HOST'];
$basePath       = __DIR__ . '/../..';
$baseTmpPath    = $basePath . '/.tmp/';
$baseSavingPath = $basePath . '/public/hashes/';
$db             = new Database(
    $settings['database']['host'],
    $settings['database']['port'],
    $settings['database']['dbname'],
    $settings['database']['user'],
    $settings['database']['password']
);

// Poll resource initialization
$pollResourcePath = $settings['poll_resource_path'];
$pollStructure    = [
    'global_progress' => 0
];

try {
    $fb = new Facebook([
        'app_id'                => $settings['facebook']['appId'],
        'app_secret'            => $settings['facebook']['appSecret'],
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
$hashLink       = $_POST['hash_link'];

// Checks if the session is valid
$session = Util::makeFBRequest($fb, '/me', $accessToken);

// Initializes the progress handler
try {
    $resourcePoller = new ProgressPollerHandler($pollResourcePath, $hashLink);
} catch (\Exception $e) {
    http_response_code(500);
    exit(json_encode([
        'status'    => 'ERROR',
        'code'      => '@resource_poller_handler_error',
        'data'      => [
            'message'   => $e->getMessage()
        ]
    ]));
}

if (empty($session['error'])) {
    if (Util::validateFBUrl($resourceLink)) {

        $resourcePoller->writeToPoll($pollStructure);

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

        $resourcePoller->writeKeyToPoll('global_progress', 0);

        // Analyzes the resource link to retrieve the page ID and post ID
        $path = $uri->getPath();
        $type = Util::getFBUrlType($path);
        $pageId = '';
        $postId = '';
        $creator = '';
        switch ($type) {
            case Util::POST:
            case Util::VIDEO:
            case Util::PAGE_PHOTO:
                $pathSegments = (new HierarchicalPath($path))->getSegments();
                $entityFBLink = 'https://www.facebook.com/'.$pathSegments[0];
                $postId       = empty($pathSegments[3]) ?
                    $pathSegments[2] : $pathSegments[3];
                $request = Util::makeFBRequest($fb, '/?id='.$entityFBLink,
                    $accessToken);
                $pageId = $request['id'];
                $creator = $request['name'];
                break;
            case Util::PERMALINK:
                $query = new Query($uri->getQuery());
                $pageId = $query->getPairs()['id'];
                $postId = $query->getPairs()['story_fbid'];
                $creator = Util::makeFBRequest($fb, '/?id='.$resourceLink,
                    $accessToken)['name'];
                break;
            CASE Util::EVENTS_PERMALINK:
                $pathSegments = (new HierarchicalPath($path))->getSegments();
                $pageId       = $pathSegments[1];
                $postId       = empty($pathSegments[3]) ?
                    $pathSegments[2] : $pathSegments[3];
                $entityFBLink = 'https://www.facebook.com/'.$pathSegments[0];
                $request = Util::makeFBRequest($fb, '/'.$pageId,
                    $accessToken);
                $creator = $request['name'];
            default:
        }

        // Retrieve the data associated to the facebook resource
        $resourceId = $pageId.'_'.$postId;
        $data = Util::makeFBRequest($fb,
            '/'.$resourceId.'?fields=attachments,source,comments.limit(10000000){attachment,comments.limit(10000000){attachment,message,from},from,message}',
            $accessToken
        );

        // If there are not error, then manipulate and save the facebook response
        if (empty($data['error'])) {
            $resourcePoller->writeKeyToPoll('global_progress', 10);

            /** @var PDO $dbInstance */
            $dbInstance = $db->getInstance();
            $parsedData = [];

            // Where the files about a post will be saved
            $version        = count(glob($baseTmpPath.$resourceId.'*')) + 1;
            $resourceDir    = $resourceId.'-'.$version;
            $tmpSavePath    = $baseTmpPath.$resourceDir;
            $tmpSaveOutPath = $tmpSavePath.'/output';
            mkdir($tmpSavePath, 0755); // The file will be saved


            // Saves the attachments, if it exists in the downloaded resource
            if (!empty($data['attachments'])) {
                $attachmentSavingPath = $tmpSavePath.'/attachments';
                mkdir($attachmentSavingPath, 0755);
                $parsedData['attachments'] = $data['attachments']['data'];
                foreach ($parsedData['attachments'] as $attachment) {
                    Util::saveAttachment($attachment,
                        $resourceId, $attachmentSavingPath);
                }
            }
            $resourcePoller->writeKeyToPoll('global_progress', 20);


            // Saves the comments (and sub-comments), if it exists in the downloaded resource
            if (!empty($data['comments'])) {
                $commentsSavingPath = $tmpSavePath.'/comments';
                mkdir($commentsSavingPath, 0755);
                $parsedData['comments'] = [];
                $comments = $data['comments']['data'];
                $commentsCounter = 1;
                foreach ($comments as $comment) {
                    $parsedComment['id']      = $comment['id'];
                    $parsedComment['from']    = $comment['from'];
                    $parsedComment['message'] = $comment['message'];
                    // Path where the attachments about this resource will be saved.
                    $commentsAttachSavingPath =
                        $commentsSavingPath.'/'.$parsedComment['id'];
                    if (!empty($comment['attachment'])) {
                        mkdir($commentsAttachSavingPath, 0755);
                        $parsedComment['attachment']    = $comment['attachment'];
                        Util::saveAttachment(
                            $parsedComment['attachment'],
                            $parsedComment['id'],
                            $commentsAttachSavingPath
                        );
                    }
                    // Now, if they exist, parse the comments of the current comment
                    if (!empty($comment['comments'])) {
                        $parsedCCs                  = $comment['comments']['data'];
                        $parsedComment['comments']  = [];
                        foreach ($parsedCCs as $parsedCC) {
                            $commentsComment['id']      = $parsedCC['id'];
                            $commentsComment['from']    = $parsedCC['from'];
                            $commentsComment['message'] = $parsedCC['message'];
                            if (!empty($parsedCC['attachment'])) {
                                $commentsComment['attachment'] = $parsedCC['attachment'];
                                // Checks if the saving directory is not already created
                                if (!file_exists($commentsAttachSavingPath)) {
                                    mkdir($commentsAttachSavingPath, 0755);
                                }
                                Util::saveAttachment(
                                    $commentsComment['attachment'],
                                    $commentsComment['id'],
                                    $commentsAttachSavingPath
                                );
                            }
                            array_push($parsedComment['comments'], $commentsComment);
                        }
                    }
                    array_push($parsedData['comments'], $parsedComment);
                    $resourcePoller->writeKeyToPoll('global_progress', 20 +
                        ($commentsCounter / count($comments)) * 50);
                    $commentsCounter++;
                }
            }

            // Saves the source, if it exists in the downloaded resource
            if (!empty($data['source'])) {
                mkdir($tmpSavePath.'/videos', 0755);
                $parsedData['source'] = $src = $data['source'];
                $sourceId = $resourceId.'.mp4';
                file_put_contents($tmpSavePath.'/videos/'.$sourceId,
                    fopen($src, 'r'));
            }
            $resourcePoller->writeKeyToPoll('global_progress', 80);

            // Saves the parsed response
            file_put_contents($tmpSavePath.'/data.json',
                json_encode($parsedData));

            // Makes hash (the footprint) of the post's zip
            mkdir($tmpSaveOutPath, 0755);
            $zipName = Util::makeDirZip($resourceDir, $tmpSavePath,
                $tmpSaveOutPath);
            $hash = hash_file('sha256',
                $tmpSaveOutPath.'/'.$zipName);
            file_put_contents($tmpSaveOutPath.'/'.$hash, "");

            // Generate code associated to the hash (Only for validation purpose)
            $code = hash('crc32', $hash.'-'.Util::generateCode());
            $zipName = Util::makeDirZip($code, $tmpSaveOutPath.'/',
                $baseSavingPath);
            $creationDate = date('Y-n-j');

            // Checks if the hash already exists in the DB
            $query = $dbInstance->prepare(
                'SELECT * '.
                'FROM posts '.
                'WHERE hash=:hash'
            );
            $query->bindParam(':hash', $hash, PDO::PARAM_STR);
            $query->execute();
            $rows = $query->fetchAll();
            if (count($rows) !== 0) {
                $zipName = $rows[0]['name'];
                $zipPath = $baseSavingPath.'/'.$zipName;
            } else {
                // Now saves the post hash to the database
                $query = $dbInstance->prepare(
                    'INSERT INTO posts(code, creator, ref_post, name, hash, date)'.
                    'VALUES (:code, :creator, :ref_post, :name, :hash, :date)'
                );
                $query->bindParam(':code', $code, PDO::PARAM_STR);
                $query->bindParam(':creator', $creator, PDO::PARAM_STR);
                $query->bindParam(':ref_post', $resourceLink, PDO::PARAM_STR);
                $query->bindParam(':name', $zipName, PDO::PARAM_STR);
                $query->bindParam(':hash', $hash, PDO::PARAM_STR);
                $query->bindParam(':date', $creationDate);
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
            }
            $resourcePoller->writeKeyToPoll('global_progress', 100);

            // Flushes the working directory
            system('rm -rf ' . escapeshellarg($tmpSavePath));

            // Finally, sends the response
            http_response_code(200);
            echo(json_encode([
                'status'    => 'OK',
                'data'      => [
                    'date'      => $creationDate,
                    'code'      => $code,
                    'owner'     => $creator,
                    'hash'      => $hash,
                    'location'  => 'http://'.$host.'/hashes/'.$zipName
                ]
            ]));
            fastcgi_finish_request();
            $resourcePoller->destroy();


            // Sleeps for 120 seconds and destroys the zip after this time.
            sleep(120);
            system('rm ' . escapeshellarg($baseSavingPath.'/'.$zipName));
            exit();
        } else {
            $resourcePoller->destroy();
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
        $resourcePoller->destroy();
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
    $resourcePoller->destroy();
    http_response_code(400);
    exit(json_encode([
        'status'    => 'ERROR',
        'code'      => '@access_token_not_valid_error',
        'data'      => [
            'message'   => 'The access token is not valid.'
        ]
    ]));
}
