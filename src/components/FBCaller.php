<?php

namespace App\Components;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

/**
 * Class tha manages the interaction with the application and the Graph API.
 *
 * Class FBCaller
 * @package App\Components
 */
class FBCaller
{

    /** @var Facebook */
    private $fb;

    /**
     * FBCaller constructor.
     *
     * @param   string  $appId
     * @param   string  $appSecret
     */
    public function __construct(string $appId, string $appSecret)
    {
        $this->setFb(new Facebook([
            'app_id'                => $appId,
            'app_secret'            => $appSecret,
            'default_graph_version' => 'v2.9'
        ]));
    }

    /**
     * Retrieves from the $endpoint the data requested with $data.
     *
     * @param   string  $endpoint
     * @param   array   $arguments
     * @param   array   $data
     * @param   string  $accessToken
     * @return  null|array  If there are not errors returns an array with
     *                      the extracted attributes, null otherwise.
     */
    public function get(string $endpoint, array $arguments, array $data,
        string $accessToken = null)
    {
        foreach ($arguments as $argument) {
            $endpoint = str_replace('{}', $argument, $endpoint);
        }

        try {
            if (!is_null($accessToken) && !empty($accessToken)) {
                $response = $this->getFb()->get($endpoint, $accessToken);
            } else {
                $response = $this->getFb()->get($endpoint);
            }

            return $this->parseResponse($response->getDecodedBody(), $data);
        } catch (FacebookResponseException $fre) {
            return null;
        } catch (FacebookSDKException $fde) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if a user Facebook session is still valid.
     *
     * @param   string  $accessToken
     * @return  bool    Returns true if the session is valid, false otherwise.
     */
    public function validSession($accessToken): bool
    {
        $response = $this->get('/me', [], ['id'], $accessToken);
        return !is_null($response) && !key_exists('error', $response);
    }

    /**
     * Filters a Facebook $response with the specified $data.
     *
     * @param   array   $response
     * @param   array   $data
     * @return  array   The filtered response through the $data array.
     */
    private function parseResponse(array $response, array $data): array
    {
        $filteredResponse = array();

        // Iterate through the attributes and filter the current route
        foreach($data as $d)
        {
            if (array_key_exists($d, $response)) {
                $filteredResponse[$d] = $response[$d];

                // TODO: makes possible to iterate also in sub-arrays
            }
        }

        return $filteredResponse;
    }

    /**
     * @return Facebook
     */
    public function getFb()
    {
        return $this->fb;
    }

    /**
     * @param Facebook $fb
     * @return FBCaller
     */
    public function setFb($fb)
    {
        $this->fb = $fb;
        return $this;
    }
}