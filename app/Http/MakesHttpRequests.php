<?php

namespace App\Http;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Inspector;

trait MakesHttpRequests
{
    protected $client;

    protected function get($url, $options = [], $parseResponse = 'json')
    {
        return $this->executeRequest('get', $url, $options, $parseResponse);
    }

    protected function post($url, $options = [], $parseResponse = 'json')
    {
        return $this->executeRequest('post', $url, $options, $parseResponse);
    }

    protected function executeRequest($method, $url, $options = [], $parseResponse = 'json') {
        try {
            $response = $this->client->{$method}($url, $options);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception(RequestException::getResponseBodySummary($response));
            }
        } catch (Exception $e) {
            $message = 'Http request failed';

            if ($e instanceof RequestException && !is_null($e->getResponse())) {
                $summary = RequestException::getResponseBodySummary($e->getResponse());
                $message .= " ({$summary})";
            } else {
                $message .= " ({$e->getMessage()})";
            }

            Inspector::reportException($e);
            throw new Exception($message, 0, $e);
        }

        $contents = $response->getBody()->getContents();

        switch ($parseResponse) {
            case 'json':
                return json_decode($contents);
            default:
                return $contents;
        }
    }
}
