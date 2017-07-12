<?php

namespace App\Components;


use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Util
{
    // Facebook content types
    const UNKNOWN     = -1;
    const POST         = 1;
    const VIDEO        = 2;
    const PAGE_PHOTO   = 3;
    const USER_PHOTO   = 4;
    const PERMALINK    = 5;

    public static function validateFBUrl ($url)
    {
        $pattern =
            '/^((https|http):\/\/)\n?((www|m|mbasic)\.facebook\.com)'.
            '(\/((([a-zA-Z0-9.]+)(\/(((posts|videos)\/\d{1,})|'.
            '((photos){1}\/a\.\d{1,}\.\d{1,}\.\d{1,}\/\d{1,}))))|'.
            /*'(photo\.php)|*/'(permalink\.php\?story_fbid=\d{1,}&id=\d{1,})))/';

        return preg_match($pattern, $url);
    }

    /**
     * @param   string  $url
     * @return  int     The type of the url (Refers to the
     */
    public static function getFBUrlType($url)
    {
        if (preg_match('/^\/[a-zA-Z0-9.]+\/posts\/\d{1,}/', $url)) {
            return Util::POST;
        } else if (preg_match('/\/[a-zA-Z0-9.]+\/videos\/\d{1,}/', $url)) {
            return Util::VIDEO;
        } else if(preg_match(
            '/^\/[a-zA-Z0-9.]+\/(photos){1}\/a\.\d{1,}\.\d{1,}\.\d{1,}\/\d{1,}/',
            $url)) {
            return Util::PAGE_PHOTO;
        } else if(preg_match('/^\/photo\.php/', $url)) {
            return Util::USER_PHOTO;
        } else if (preg_match('/^\/permalink\.php/', $url)) {
            return Util::PERMALINK;
        } else {
            return Util::UNKNOWN;
        }
    }

    /**
     * Makes the zip of a directory.
     *
     * @param $name
     * @param $originPath
     * @param $savingPath
     */
    public static function makeDirZip($name, $originPath, $savingPath)
    {
        // Makes the zip of the current version of the post and
        // saves it to the ../hashes/ dir
        $zip     = new ZipArchive();
        $zipName = $name.'.zip';
        $zipPath = $savingPath.'/'.$zipName;

        // In case the zip file creation is not possible
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            http_response_code(500);
            exit(json_encode([
                'status'    => 'ERROR',
                'code'      => '@zip_creation_error',
                'data'      => [
                    'message'   => 'It was not possible to create the zip file.'
                ]
            ]));
        }

        // Iterator to iterate
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(realpath($originPath)),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(realpath($originPath)) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        return $zipName;
    }

    /**
     * Makes a request to Facebook at the indicated endpoint.
     *
     * @param   Facebook  $fb
     * @param   string    $endpoint
     * @param   $accessToken
     * @return  mixed
     */
    public static function makeFBRequest($fb, $endpoint, $accessToken)
    {
        try {
            return $fb->get($endpoint, $accessToken)->getDecodedBody();
        } catch(FacebookResponseException $fre) {
            http_response_code(500);
            exit(json_encode([
                'status'    => 'ERROR',
                'code'      => '@fb_response_error',
                'data'      => [
                    'message'   => $fre->getMessage()
                ]
            ]));
        } catch(FacebookSDKException $fsde) {
            http_response_code(500);
            exit(json_encode([
                'status'    => 'ERROR',
                'code'      => '@fb_sdk_error',
                'data'      => [
                    'message'   => $fsde->getMessage()
                ]
            ]));
        }
    }

    /**
     * Generates a code long as the specified length.
     *
     * @param   int     $length
     * @return  string  The generated code as string.
     */
    public static function generateCode($length = 8)
    {
        $baseChars = "abcdefghjklmnopqrstuwxyz" .
            "ABCDEFGHJKLMNOPQRSTUWXYZ" .
            "0123456789" .
            "!@$";
        $baseCharsLength = mb_strlen($baseChars);

        $password = "";
        for ($i = 0; $i < $length; $i++) {
            $password .= $baseChars[rand(0, $baseCharsLength - 1)];
        }

        return $password;
    }
}