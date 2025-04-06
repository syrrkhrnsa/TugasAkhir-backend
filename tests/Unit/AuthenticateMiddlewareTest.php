<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

class AuthenticateMiddlewareTest extends TestCase
{
    /**
     * Test that unauthenticated user is redirected to login route for non-json requests
     */
    public function test_redirects_to_login_for_non_json_requests()
    {
        // Create a test route that uses the middleware
        Route::get('/test-auth', function () {
            return 'Authenticated';
        })->middleware(Authenticate::class);

        // Make a non-json request
        $response = $this->get('/test-auth');

        // Should redirect to login route
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that unauthenticated user receives 401 for json requests
     */
    public function test_returns_401_for_json_requests()
    {
        // Create a test route that uses the middleware
        Route::get('/test-auth', function () {
            return 'Authenticated';
        })->middleware(Authenticate::class);

        // Make a json request
        $response = $this->getJson('/test-auth');

        // Should return 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * Test that authenticated user can access the route
     */
    public function test_allows_authenticated_users_to_access_route()
    {
        $user = \App\Models\User::factory()->create();

        // Create a test route that uses the middleware
        Route::get('/test-auth', function () {
            return 'Authenticated';
        })->middleware(Authenticate::class);

        // Make request as authenticated user
        $response = $this->actingAs($user)
            ->get('/test-auth');

        // Should allow access
        $response->assertOk()
            ->assertSee('Authenticated');
    }

    /**
     * Test that authenticated user can access the json route
     */
    public function test_allows_authenticated_users_to_access_json_route()
    {
        $user = \App\Models\User::factory()->create();

        // Create a test route that uses the middleware
        Route::get('/test-auth', function () {
            return response()->json(['message' => 'Authenticated']);
        })->middleware(Authenticate::class);

        // Make json request as authenticated user
        $response = $this->actingAs($user)
            ->getJson('/test-auth');

        // Should allow access
        $response->assertOk()
            ->assertJson(['message' => 'Authenticated']);
    }
}
