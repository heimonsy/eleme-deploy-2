<?php
namespace Deploy\Github;

use GuzzleHttp\Client;
use Deploy\Exception\GithubException;

class GithubAuth
{
    public static function accessToken($clientId, $clientSecret, $code, $proxy = null)
    {
        $defaults = array(
            'timeout' => 5
        );
        if (!empty($proxy)) {
            $defaults['proxy'] = $proxy;
        }

        $client = new Client(array('defaults' => $defaults));

        $response = $client->post('https://github.com/login/oauth/access_token', array(
            'headers' => array(
                'Accept' => 'application/json'
            ),
            'body' => array(
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
            ),
        ));

        $values = json_decode($response->getBody(), true);

        if ($response->getStatusCode() != 200 || empty($values['access_token'])) {
            throw new GithubException('Github Oauth Access Token Error', "Github Oauth Access Token Error. httpCode={$response->getStatusCode()}; code={$code}; proxy={$proxy}; response_body={$response->getBody()}");
        }

        return  $values['access_token'];
    }

    public static function authorizeUrl($clientId, $callbackUrl, $scope)
    {
        return 'https://github.com/login/oauth/authorize?type=web_server&client_id='
                . $clientId . '&redirect_uri=' . $callbackUrl
                . '&scope=' .  $scope . '&response_type=code';
    }
}
