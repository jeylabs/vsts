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
    protected $project = null;

    public function __construct($instance, $collection = 'DefaultCollection', $version = '1.0', $httpClient = null)
    {
        $baseURI = 'https://' . $instance . '/' . $collection;
        $this->version = $version;
        $this->client = $httpClient ?: new Client([
            'base_uri' => $baseURI,
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_TIMEOUT,
        ]);
    }

    public function setProject($project)
    {
        $this->project = $project;
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

    public function getWorkItemTypes()
    {
        return $this->makeRequest('GET', 'wit/workItemTypes');
    }

    public function getWorkItemIDs($areaPath = null)
    {
        $q = 'Select [System.Id], [System.Title], [System.State] From WorkItems';
        if ($areaPath) {
            $q .= ' WHERE System.AreaPath = \'' . $areaPath . '\'';
        }
        $data = [
            'query' => $q
        ];
        return $this->makeRequest('POST', 'wit/wiql', [], $data);
    }

    public function getWorkItems($ids)
    {
        $idChunks = array_chunk(explode(',', $ids), 200, true);
        $response = [
            'count' => 0,
            'value' => []
        ];
        foreach ($idChunks as $idArray) {
            $query = [
                'ids' => implode(',', $idArray)
            ];
            $r = $this->makeRequest('GET', 'wit/workitems', $query, []);
            $response['count'] += $r['count'];
            $response['value'] = array_merge($response['value'], $r['value']);
        }
        return $response;
    }

    protected function makeRequest($method, $uri, $query = [], $data = null)
    {
        $uri = ($this->project ? '/' . $this->project : '') . '/_apis/' . $uri;
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getDefaultHeaders();
        if ($data) {
            $options[GuzzleRequestOptions::JSON] = $data;
        }
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
