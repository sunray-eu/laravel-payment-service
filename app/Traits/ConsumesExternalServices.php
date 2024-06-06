<?php

namespace App\Traits;

use GuzzleHttp\Client;

/**
 * Trait ConsumesExternalServices
 * @package App\Traits
 *
 * Provides a method to make requests to external services.
 */
trait ConsumesExternalServices
{
    /**
     * Make a request to an external service.
     *
     * @param string $method HTTP method (e.g., 'GET', 'POST').
     * @param string $requestUrl The URL to make the request to.
     * @param array $queryParams Query parameters to include in the request.
     * @param array $formParams Form parameters to include in the request.
     * @param array $headers Headers to include in the request.
     * @param bool $isJsonRequest Indicates if the request is a JSON request.
     * @return mixed The response from the external service.
     */
    public function makeRequest($method, $requestUrl, $queryParams = [], $formParams = [], $headers = [], $isJsonRequest = false)
    {
        $client = new Client([
            'base_uri' => $this->baseUri,
        ]);

        // Resolve authorization if the method exists in the class using this trait
        if (method_exists($this, 'resolveAuthorization')) {
            $this->resolveAuthorization($queryParams, $formParams, $headers);
        }

        // Make the HTTP request to the external service
        $response = $client->request($method, $requestUrl, [
            $isJsonRequest ? 'json' : 'form_params' => $formParams,
            'headers' => $headers,
            'query' => $queryParams,
        ]);

        // Get the response body contents
        $response = $response->getBody()->getContents();

        // Decode the response if the method exists in the class using this trait
        if (method_exists($this, 'decodeResponse')) {
            $response = $this->decodeResponse($response);
        }

        return $response;
    }
}
