<?php

namespace Dorvidas\JsonApiClient;

use Dorvidas\JsonApiClient\Exceptions\ApiValidationException;
use WoohooLabs\Yang\JsonApi\Hydrator\ClassHydrator;
use WoohooLabs\Yang\JsonApi\Request\JsonApiRequestBuilder;
use WoohooLabs\Yang\JsonApi\Schema\Document;

class JsonApiResponse
{
    public $response;
    public $body;
    public $data;
    public $errors;
    public $meta;



    public function __construct(\GuzzleHttp\Psr7\Response $response)
    {
        $this->response = $response;
    }

    public function parse()
    {
        $body = $this->response->getBody()->getContents();
        $this->respondImmediately($body);
        if ($body == '') {
            return null;
        } else {
            $decoded = \GuzzleHttp\json_decode($body, true);

            //Set body
            $this->body = $decoded;

            //Set data
            if (isset($decoded['data'])) {
                $document = Document::createFromArray($decoded);
                $hydrator = new ClassHydrator();
                $hydrated = $hydrator->hydrate($document);
                $this->data = is_array($hydrated)? collect($hydrated): $hydrated;
            }

            //Set meta
            if (isset($decoded['meta'])) {
                $this->meta = $decoded['meta'];
            }
        }
        return $this;
    }

    private function respondImmediately($body)
    {
        $status = $this->response->getStatusCode();
        if ($status == 422) {
            throw new ApiValidationException($body);
        } elseif (substr($status, 0, 1) == 4) {
            abort($status, 'API Client status code ' . $status . ' Body:' . $body);
        }
    }

    public function meta($key)
    {
        return $this->meta[$key] ?? null;
    }

    public function status()
    {
        return $this->response->getStatusCode();
    }
}