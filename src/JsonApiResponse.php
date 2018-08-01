<?php

namespace Dorvidas\JsonApiClient;

use WoohooLabs\Yang\JsonApi\Hydrator\ClassHydrator;
use WoohooLabs\Yang\JsonApi\Request\JsonApiRequestBuilder;
use WoohooLabs\Yang\JsonApi\Schema\Document;
use Dorvidas\JsonApiClient\Exceptions\ApiValidationException;

class JsonApiResponse
{
    public $response;
    public $body;
    public $data;
    public $errors;
    public $meta;
    public $status;
    protected $throwException;

    /**
     * @param \GuzzleHttp\Psr7\Response $response
     * @param bool $throwException
     * @return null
     */
    public function __construct(\GuzzleHttp\Psr7\Response $response, $throwException = true)
    {
        $this->response = $response;
        $this->throwException = $throwException;
    }

    /**
     * Prepare data from response received by API. Set data, meta, errors variables
     * If status is non 2xx throw appropriate exceptions.
     * @return null
     */
    public function prepare()
    {
        $this->status = $this->response->getStatusCode();

        $rawResponseData = $this->response->getBody()->getContents();

        $this->body = $rawResponseData ? \GuzzleHttp\json_decode($rawResponseData, true) : '';

        $this->errors = isset($this->body['errors']) ? $this->body['errors'] : [];

        if ($this->throwException && (substr($this->status, 0, 1) != 2)) {
            $this->throwException($this->status, $this->errors);
        }

        //Set data
        if ($this->body) {
            //This happens when array was expected but it is empty
            if (empty($this->body['data'])) {
                $this->data = collect([]);
            } else {
                $document = Document::createFromArray($this->body);
                $hydrator = new ClassHydrator();
                $hydrated = $hydrator->hydrate($document);
                $this->data = is_array($hydrated) ? collect($hydrated) : $hydrated;
            }
        }

        //Set meta
        if (isset($this->body['meta'])) {
            $this->meta = $this->body['meta'];
        }
    }

    /**
     * Throw appropriate exception based on status code
     * @param int $status Status code
     * @param array $errors Errors
     * @return null
     */
    private function throwException($status, array $errors)
    {
        if ($status == 422) {
            throw new ApiValidationException($errors);
        } elseif (substr($status, 0, 1) == 4) {
            abort($status, 'API Client status code ' . $status);
        }
    }

    /**
     * Get appropriate meta value
     * @param string $key
     * @return string|null
     */
    public function meta($key)
    {
        return $this->meta[$key] ?? null;
    }

    /**
     * Get status HTTP status code of an request
     * @return int
     */
    public function status()
    {
        return $this->response->getStatusCode();
    }
}