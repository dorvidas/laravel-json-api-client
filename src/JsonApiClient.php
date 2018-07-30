<?php

namespace Dorvidas\JsonApiClient;

use Illuminate\Support\Facades\Storage;

/**
 * Guzzle client wrapper for requesting resources from {json:api} APIs
 *
 * Class JsonApiClient
 * @package Dorvidas\JsonApiClient
 */
class JsonApiClient
{
    protected $client;
    protected $response;
    protected $token;
    protected $includes = [];
    protected $fields = [];
    protected $filters = [];
    protected $limit;
    protected $offset;
    protected $formData;
    protected $jsonData;
    protected $json = [];

    public function __construct($client, $token = null)
    {
        $this->client = $client;
        $this->token = $token;
    }

    /**
     * @param array $includes
     * @return $this
     */
    public function withIncludes(array $includes)
    {
        $this->includes = $includes;
        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function withFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function withFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function formData($data)
    {
        $this->formData = $data;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function jsonData($data)
    {
        $this->jsonData = $data;
        return $this;
    }

    /**
     * Build query params array
     * @return array
     */
    public function buildQuery()
    {
        $query = [];
        if ($this->limit || $this->offset) {
            $query['page'] = [];
            if ($this->limit) {
                $query['page']['limit'] = $this->limit;
            }
            if ($this->offset) {
                $query['page']['offset'] = $this->offset;
            }
        }
        if ($this->filters) {
            foreach ($this->filters as $resource => $columns) {
                foreach ($columns as $column => $operands) {
                    foreach ($operands as $operand => $value) {
                        $query['filter'][$resource][$column][$operand] = is_array($value) ? implode(',',
                            $value) : $value;
                    }
                }
            }
        }
        if ($this->fields) {
            foreach ($this->fields as $resource => $fieldList) {
                $query['fields'][$resource] = implode(',', $fieldList);
            }
        }
        if ($this->includes) {
            $query['include'] = implode(',', $this->includes);
        }
        return $query;
    }

    /**
     * Do a GET request to API
     * @param $url
     * @return JsonApiResponse|null
     */
    public function get($url)
    {
        $response = $this->client->get($url, [
            'headers' => $this->getHeaders(),
            'query' => $this->buildQuery()
        ]);

        return (new JsonApiResponse($response))->parse();
    }

    /**
     * Do a POST request to API
     * @param $url
     * @return JsonApiResponse|null
     */
    public function post($url)
    {
        $params = [];
        $params['headers'] = $this->getHeaders();
        if (isset($this->jsonData)) {
            $params['json'] = $this->jsonData;
        }
        if (isset($this->formData)) {
            $params['form_params'] = $this->formData;
        }

        $response = $this->client->post($url, $params);

        return (new JsonApiResponse($response))->parse();
    }

    /**
     * @param $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        $headers = [];
        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    /**
     * @param $token
     * @return $this
     */
    public function token(
        $token
    ) {
        $this->token = $token;

        return $this;
    }

    /**
     * @param $username
     * @param $password
     * @param null $clientId
     * @param null $clientSecret
     * @return User
     */
    public function authenticate(
        $username,
        $password,
        $clientId = null,
        $clientSecret = null
    ) {
        if (!$clientId) {
            $clientId = config('json_api.client_id');
        }

        if (!$clientSecret) {
            $clientSecret = config('json_api.secret');
        }

        $response = $this->formData([
            "grant_type" => "password",
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "username" => $username,
            "password" => $password
        ])
            ->post('oauth/token');

        //Check JWT
        $payload = \Firebase\JWT\JWT::decode($response->body['access_token']
            , Storage::get('oauth-public.key')
            , ['RS256']);

        $user = new \Dorvidas\JsonApiClient\User();
        $user->id = $payload->sub;

        session(['jwt' => $response->body['access_token']]);

        return $user;
    }
}