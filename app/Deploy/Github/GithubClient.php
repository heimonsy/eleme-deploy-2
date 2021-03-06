<?php
namespace Deploy\Github;

use GuzzleHttp\Client;
use Deploy\Exception\GithubException;
use Log;

class GithubClient
{
    const  API_URL = 'https://api.github.com/';

    private $client;
    private $access_token;
    private $response;

    public function __construct($access_token, $proxy = null)
    {
        $this->access_token = $access_token;

        $defaults = array(
            'timeout' => 30,
            'connect_timeout' => 30,
        );
        if (!empty($proxy)) {
            $defaults['proxy'] = $proxy;
        }

        $this->client = new Client(array('defaults' => $defaults));
    }

    public static function catUrl($path)
    {
        return self::API_URL . $path;
    }

    public function request($url, $params = array(), $method = 'GET')
    {
        if (strpos($url, 'https://') !== 0) {
            $url = self::API_URL . $url;
        }

        $option = array(
            'headers' => $this->getHeaders(),
            'timeout' => 30,
            'connect_timeout' => 30,
        );

        if ($method == 'POST') {
            $option['body'] = $params;
            $this->response = $this->client->post($url, $option);
        } else {
            $option['query'] = $params;
            $this->response = $this->client->get($url, $option);
        }

        $json = json_decode($this->response->getBody(), true);
        $code = $this->response->getStatusCode() . '';

        if ($code[0] != '2') {
            if (is_array($params)) {
                $params = http_build_query($params);
            }
            throw new GithubException('Github Api Error', "github api error. code={$this->response->getStatusCode()}; api={$url}; params={$params}; method={$method}; response_body={$this->response->getBody()}");
        }

        return $json;
    }

    /**
     * @return GuzzleHttp\Message\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    private function getHeaders()
    {
        $headers = array();
        if (!empty($this->access_token)) {
            $headers['Authorization'] = 'token ' . $this->access_token;
        }

        return $headers;
    }
}
