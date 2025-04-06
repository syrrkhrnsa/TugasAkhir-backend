<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response; // Gunakan Illuminate\Http\Response, bukan Symfony
use App\Http\Middleware\Cors;

class CorsMiddlewareTest extends TestCase
{
    /**
     * Test that the CORS middleware adds the correct headers.
     *
     * @return void
     */
    public function testCorsHeadersAreApplied()
    {
        $middleware = new Cors();

        // Mock request dan response
        $request = Request::create('/test', 'GET');
        $response = new Response(); // Gunakan Illuminate\Http\Response

        // Jalankan middleware
        $response = $middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        // Assertions untuk memastikan header CORS ditambahkan
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, PUT, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
    }
}
