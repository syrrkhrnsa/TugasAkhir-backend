<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\NotificationController;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\DatabaseNotification;

class NotificationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testIndexReturnsNotificationsForAuthenticatedUser()
    {
        $user = Mockery::mock('App\\Models\\User');
        $notifications = Mockery::mock(DatabaseNotificationCollection::class);

        Auth::shouldReceive('user')->once()->andReturn($user);
        $user->shouldReceive('getAttribute')->with('unreadNotifications')->andReturn($notifications);

        // Tambahkan ekspektasi jsonSerialize agar tidak error
        $notifications->shouldReceive('jsonSerialize')->andReturn([]);

        $controller = new NotificationController();
        $response = $controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
    }


    public function testIndexReturnsUnauthorizedForUnauthenticatedUser()
    {
        Auth::shouldReceive('user')->once()->andReturn(null);

        $controller = new NotificationController();
        $response = $controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->status());
    }

    public function testMarkAsReadMarksNotificationAsRead()
    {
        $user = Mockery::mock('App\Models\User');
        $notification = Mockery::mock(DatabaseNotification::class);

        Auth::shouldReceive('user')->once()->andReturn($user);
        $user->shouldReceive('notifications')->andReturnSelf();
        $user->shouldReceive('where')->with('id', '123')->andReturnSelf();
        $user->shouldReceive('first')->andReturn($notification);

        $notification->shouldReceive('markAsRead')->once();

        $controller = new NotificationController();
        $response = $controller->markAsRead('123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
    }

    public function testMarkAsReadReturnsSuccessEvenIfNotificationNotFound()
    {
        $user = Mockery::mock('App\Models\User');

        Auth::shouldReceive('user')->once()->andReturn($user);
        $user->shouldReceive('notifications')->andReturnSelf();
        $user->shouldReceive('where')->with('id', '123')->andReturnSelf();
        $user->shouldReceive('first')->andReturn(null);

        $controller = new NotificationController();
        $response = $controller->markAsRead('123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
    }

    public function testMarkAllAsReadMarksAllUnreadNotificationsAsRead()
    {
        $user = Mockery::mock('App\Models\User');
        $notifications = Mockery::mock(DatabaseNotificationCollection::class);

        Auth::shouldReceive('user')->once()->andReturn($user);
        $user->shouldReceive('getAttribute')->with('unreadNotifications')->andReturn($notifications);
        $notifications->shouldReceive('markAsRead')->once();

        $controller = new NotificationController();
        $response = $controller->markAllAsRead();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
    }

    public function testNotificationsReturnsNotificationsForAuthenticatedUser()
    {
        $user = Mockery::mock('App\\Models\\User');
        $notifications = Mockery::mock(DatabaseNotificationCollection::class);

        Auth::shouldReceive('user')->once()->andReturn($user);
        $user->shouldReceive('getAttribute')->with('unreadNotifications')->andReturn($notifications);

        // Pastikan jsonSerialize() bisa dipanggil tanpa error
        $notifications->shouldReceive('jsonSerialize')->andReturn([]);

        $controller = new NotificationController();
        $response = $controller->notifications();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
    }
    public function testNotificationsReturnsUnauthorizedForGuestUser()
    {
        Auth::shouldReceive('user')->once()->andReturn(null);

        $controller = new NotificationController();
        $response = $controller->notifications();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->status());
    }

}
