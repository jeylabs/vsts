<?php

namespace Jeylabs\VSTS;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\RequestOptions as GuzzleRequestOptions;

class VSTS
{
    const DEFAULT_TIMEOUT = 5;
    protected $client;
    protected $headers = [];
    protected $promises = [];
    protected $lastResponse;
    protected $version = '1.0';
    protected $isAsyncRequest = false;
    protected $accessToken = null;

    public function __construct($instance, $collection = 'DefaultCollection', $version = '1.0', $httpClient = null)
    {
        $baseURI = 'https://' . $instance . '/' . $collection . '/_apis/';
        $this->version = $version;
        $this->client = $httpClient ?: new Client([
            'base_uri' => $baseURI,
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_TIMEOUT,
        ]);
    }

    public function isAsyncRequests()
    {
        return $this->isAsyncRequest;
    }

    public function setAsyncRequests($isAsyncRequest)
    {
        $this->isAsyncRequest = $isAsyncRequest;

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;

        return $this;
    }

    public function setHeaders($headers = [])
    {
        $this->headers = $headers;

        return $this;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    public function getProjects()
    {
        return $this->makeRequest('GET', 'projects');
    }

    protected function makeRequest($method, $uri, $query = [])
    {
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getDefaultHeaders();
        if ($this->isAsyncRequest) {
            return $this->promises[] = $this->client->requestAsync($method, $uri, $options);
        }
        $this->lastResponse = $this->client->request($method, $uri, $options);

        return json_decode($this->lastResponse->getBody(), true);
    }

    protected function getDefaultHeaders()
    {
        return array_merge([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json;api-version=' . $this->version,
        ], $this->headers);
    }

    public function __destruct()
    {
        Promise\unwrap($this->promises);
    }
}
