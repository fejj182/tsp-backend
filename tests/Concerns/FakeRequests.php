<?php

namespace Tests\Concerns;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

trait FakeRequests
{
    protected $history;

    protected function setUpClient()
    {
        $this->history = [];
        $this->mockHandler = new MockHandler();

        $handler = HandlerStack::create($this->mockHandler);
        $handler->push(Middleware::history($this->history));

        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }

    protected function addFakeJsonResponse(
        $body = null,
        $status = 200,
        array $headers = ['Content-Type' => 'application/json']
    ) {
        if (!is_null($body) && !is_string($body)) {
            $body = json_encode($body);
        }

        $this->mockHandler->append(new Response($status, $headers, $body));
    }

    protected function addErrorResponse()
    {
        $this->mockHandler->append(new Response(500, [], null));
    }

    protected function assertGuzzleNotCalled()
    {
        $this->assertTrue(count($this->history) === 0, 'Guzzle was called ' . count($this->history) . ' times.');
    }
}
