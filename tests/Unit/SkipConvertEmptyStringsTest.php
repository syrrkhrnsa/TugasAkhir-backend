<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Middleware\SkipConvertEmptyStrings;

class SkipConvertEmptyStringsTest extends TestCase
{
    /**
     * Test that the middleware allows the request to pass through without modification.
     */
    public function testMiddlewareAllowsRequestToPassThrough()
    {
        $middleware = new SkipConvertEmptyStrings();
        $request = Request::create('/test', 'POST', ['field' => '']);

        $response = $middleware->handle($request, function ($req) {
            return new Response('Next middleware');
        });

        $this->assertEquals('Next middleware', $response->getContent());
    }
}
