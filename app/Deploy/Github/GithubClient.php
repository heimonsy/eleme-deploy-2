<?php
namespace Deploy\Github;

use GuzzleHttp\Client;
use Deploy\Exception\GithubException;

class GithubClient
{
    const  API_URL = 'https://api.github.com/';

    private $client;
    private $access_token;
    private $response;

    public function __construct($access_token, $proxy = null)
    {
        $this->access_token = $access_token;

        $defaults = array();
        if ($proxy !== null) {
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

        $option = array('headers' => $this->getHeaders());

        if ($method == 'POST') {
            $option['body'] = $params;
            $this->response = $this->client->post($url, $option);
        } else {
            $option['query'] = $params;
            $this->response = $this->client->get($url, $option);
        }

        $json = json_decode($this->response->getBody(), true);

        if ($this->response->getStatusCode() != 200) {
            $params = http_build_query($url);
            throw new GithubException('Github Api Error', "github api error. api={$url}; params={$params}; method={$method}; response_body={$this->response->getBody()}");
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
