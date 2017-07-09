<?php

namespace App\Components;


class Util
{
    // Facebook content types
    const UNKNOWN     = -1;
    const POST         = 1;
    const VIDEO        = 2;
    const PAGE_PHOTO   = 3;
    const USER_PHOTO   = 4;
    const PERMALINK    = 5;

    public static function validateFBUrl ($url) {
        $pattern =
            '/^((https|http):\/\/)\n?((www|m|mbasic)\.facebook\.com)'.
            '(\/((([a-zA-Z0-9.]+)(\/(((posts|videos)\/\d{1,})|'.
            '((photos){1}\/a\.\d{1,}\.\d{1,}\/\d{1,}))))|'.
            /*'(photo\.php)|*/'(permalink\.php\?story_fbid=\d{1,}&id=\d{1,})))/';

        return preg_match($pattern, $url);
    }

    public static function getFBUrlType($url) {
        if (preg_match('/^\/[a-zA-Z0-9.]+\/posts\/\d{1,}/', $url)) {
            return Util::POST;
        } else if (preg_match('/\/[a-zA-Z0-9.]+\/videos\/\d{1,}/', $url)) {
            return Util::VIDEO;
        } else if(preg_match(
            '/^\/[a-zA-Z0-9.]+\/(photos){1}\/a\.\d{1,}\.\d{1,}\/\d{1,}/',
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

}