<?php

namespace Jeylabs\VSTS;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\RequestOptions as GuzzleRequestOptions;

class VSTS
{
    const DEFAULT_TIMEOUT = 10;
    protected $client;
    protected $headers = [];
    protected $promises = [];
    protected $lastResponse;
    protected $version = '2.0';
    protected $isAsyncRequest = false;

    protected $auhType = 'Bearer';
    protected $userPAT = null;
    protected $accessToken = null;

    protected $project = null;
    protected $team = null;

    public function __construct($instance, $collection = 'DefaultCollection', $version = '2.0', $httpClient = null)
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

    public function setTeam($team)
    {
        $this->team = $team;
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

    public function setAuthType($type)
    {
        $this->auhType = $type;

        return $this;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;

        return $this;
    }

    public function setUserPAT($userPAT)
    {
        $this->userPAT = $userPAT;

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
                'ids' => implode(',', $idArray),
                '$expand' => 'all'
            ];
            $r = $this->makeRequest('GET', 'wit/workitems', $query, []);
            $response['count'] += $r['count'];
            $response['value'] = array_merge($response['value'], $r['value']);
        }
        return $response;
    }

    public function getTeams($projectRemoteId)
    {
        return $this->makeRequest('GET', 'projects/' . $projectRemoteId . '/teams');
    }

    public function getTeamMembers($projectRemoteId, $teamRemoteId)
    {
        return $this->makeRequest('GET', 'projects/' . $projectRemoteId . '/teams/' . $teamRemoteId . '/members');
    }

    public function getIterations()
    {
        return $this->makeRequest('GET', 'work/teamsettings/iterations');
    }

    public function getWorkItem($workItemId)
    {
        $query = [
            '$expand' => 'all'
        ];
        return $this->makeRequest('GET', 'wit/workitems/' . $workItemId, $query, []);
    }

    public function getSubscriptions()
    {
        return $this->makeRequest('GET', 'hooks/subscriptions');
    }

    protected function makeRequest($method, $uri, $query = [], $data = null)
    {
        $uri = ($this->project ? '/' . $this->project : '') . ($this->team ? '/' . $this->team : '') . '/_apis/' . $uri;
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getRequestHeaders();

        if ($data) {
            $options[GuzzleRequestOptions::JSON] = $data;
        }

        if ($this->isAsyncRequest) {
            return $this->promises[] = $this->client->requestAsync($method, $uri, $options);
        }
        $this->lastResponse = $this->client->request($method, $uri, $options);
        return json_decode($this->lastResponse->getBody(), true);
    }

    protected function getRequestHeaders()
    {
        $defaultHeaders = array_merge([
            'Accept' => 'application/json;api-version=' . $this->version,
        ], $this->getAuthHeader());

        return array_merge($defaultHeaders, $this->headers);
    }

    private function getAuthHeader()
    {
        switch ($this->auhType) {
            case 'Bearer':
                return $this->createBearerAuthHeader();

            case 'Basic':
                return $this->createBasicAuthHeader();

            default:
                return [];
        }
    }

    private function createBearerAuthHeader()
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];
    }

    private function createBasicAuthHeader()
    {
        return [
            'Authorization' => 'Basic ' . $this->userPAT,
        ];
    }

    public function __destruct()
    {
        Promise\unwrap($this->promises);
    }
}
