<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Providers\RouteServiceProvider;

class RedirectIfAuthenticatedTest extends TestCase
{
    /**
     * Test that an authenticated user is redirected.
     */
    public function testAuthenticatedUserIsRedirected()
    {
        Auth::shouldReceive('guard')->with(null)->andReturnSelf();
        Auth::shouldReceive('check')->andReturn(true);

        $middleware = new RedirectIfAuthenticated();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Next middleware');
        });

        // Gunakan URL lengkap untuk membandingkan
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(url(RouteServiceProvider::HOME), $response->headers->get('Location'));
    }

    /**
     * Test that an unauthenticated user proceeds to the next middleware.
     */
    public function testUnauthenticatedUserProceeds()
    {
        Auth::shouldReceive('guard')->with(null)->andReturnSelf();
        Auth::shouldReceive('check')->andReturn(false);

        $middleware = new RedirectIfAuthenticated();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Next middleware');
        });

        $this->assertEquals('Next middleware', $response->getContent());
    }
}
