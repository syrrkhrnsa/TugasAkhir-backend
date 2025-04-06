<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ConvertToPut;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class ConvertToPutMiddlewareTest extends TestCase
{
    public function test_converts_post_to_put_for_specific_route()
    {
        // Buat request POST dengan route name yang sesuai
        $request = Request::create('/sertifikat/1', 'POST');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('PUT', '/sertifikat/{id}', ['as' => 'sertifikat.update']);
            $route->bind($request);
            return $route;
        });

        $middleware = new ConvertToPut();
        $response = $middleware->handle($request, function ($req) {
            $this->assertEquals('PUT', $req->getMethod());
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_does_not_convert_for_non_matching_route()
    {
        // Buat request POST dengan route name yang berbeda
        $request = Request::create('/sertifikat/1', 'POST');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('POST', '/sertifikat/{id}', ['as' => 'sertifikat.create']);
            $route->bind($request);
            return $route;
        });

        $middleware = new ConvertToPut();
        $response = $middleware->handle($request, function ($req) {
            $this->assertEquals('POST', $req->getMethod());
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_does_not_convert_non_post_requests()
    {
        // Buat request GET dengan route name yang sesuai
        $request = Request::create('/sertifikat/1', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/sertifikat/{id}', ['as' => 'sertifikat.update']);
            $route->bind($request);
            return $route;
        });

        $middleware = new ConvertToPut();
        $response = $middleware->handle($request, function ($req) {
            $this->assertEquals('GET', $req->getMethod());
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
    }


}
