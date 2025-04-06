<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;
use App\Providers\BroadcastServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProviderTest extends TestCase
{
    /**
     * Test the boot method of the BroadcastServiceProvider.
     *
     * @return void
     */
    public function testBootMethodDoesNotThrowErrors()
    {
        // Jangan memock Broadcast, cukup jalankan boot
        Broadcast::routes();

        // Buat instance dari provider dan panggil boot()
        $provider = new BroadcastServiceProvider(app());

        // Pastikan tidak ada error saat eksekusi
        $this->expectNotToPerformAssertions();
        $provider->boot();
    }
}
