<?php

namespace Tests\Concerns;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

Trait FakeRequests
{
  protected function setUpClient()
  {
    $container = [];
    $this->mockHandler = new MockHandler();
    
    $handler = HandlerStack::create($this->mockHandler);
    $handler->push(Middleware::history($container));

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
}