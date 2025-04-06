<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrustHosts;
use Mockery;

class TrustHostsTest extends TestCase
{
    /**
     * Test that the middleware returns the expected trusted hosts.
     */
    public function testHostsMethodReturnsExpectedValues()
    {
        // Create an instance of the middleware
        $middleware = new TrustHosts($this->app);

        // Using reflection to access the protected method 'allSubdomainsOfApplicationUrl'
        $reflection = new \ReflectionMethod(TrustHosts::class, 'allSubdomainsOfApplicationUrl');
        $reflection->setAccessible(true); // Make the method accessible

        // Call the method using reflection
        $subdomainsResult = $reflection->invoke($middleware);

        // Correcting the expected result to be an array
        $expected = [$subdomainsResult];

        // Now, calling the hosts method
        $result = $middleware->hosts();

        // Assert that the result is an array containing the expected value
        $this->assertEquals($expected, $result);
    }
}
