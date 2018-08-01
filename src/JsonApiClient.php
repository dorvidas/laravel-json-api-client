<?php

namespace Dorvidas\JsonApiClient;

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
    protected $query = [];
    protected $limit;
    protected $offset;
    protected $formData;
    protected $jsonData;
    protected $json = [];
    protected $throwException = true;

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
     * @param array $query
     * @return $this
     */
    public function withQuery(array $query)
    {
        $this->query = $query;
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

    public function throwException($status = true)
    {
        $this->throwException = $status;
        return $this;
    }

    /**
     * Build query params array
     * @return array
     */
    public function buildQuery()
    {
        $query = [];
        if ($this->query) {
            $query = $this->query;
        }
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

    public function request($type, $url)
    {
        $params['headers'] = $this->getHeaders();
        $params['query'] = $this->buildQuery();

        if (isset($this->jsonData)) {
            $params['json'] = $this->jsonData;
        }
        if (isset($this->formData)) {
            $params['form_params'] = $this->formData;
        }
        $response = $this->client->request($type, $url, $params);

        $jsonApiResponse = new JsonApiResponse($response, $this->throwException);
        $jsonApiResponse->prepare();
        return $jsonApiResponse;
    }

    /**
     * Do a GET request to API
     * @param $url
     * @return JsonApiResponse|null
     */
    public function get($url)
    {
        return $this->request('GET', $url);
    }

    /**
     * Do a POST request to API
     * @param $url
     * @return JsonApiResponse|null
     */
    public function post($url)
    {
        return $this->request('POST', $url);
    }

    /**
     * Do a PATCH request to API
     * @param $url
     * @return JsonApiResponse|null
     */
    public function patch($url)
    {
        return $this->request('PATCH', $url);
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
    public function token($token)
    {
        $this->token = $token;

        return $this;
    }
}