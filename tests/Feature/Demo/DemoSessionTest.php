<?php

namespace Tests\Feature\Demo;

use App\Enums\OrderCycleStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderCycle;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_session_is_not_available_in_normal_mode(): void
    {
        config(['lunch.hosted_demo' => false]);

        $this->postJson('/api/auth/demo-session')->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_sessions_are_separate_users_with_usable_tokens_and_no_password(): void
    {
        config(['lunch.hosted_demo' => true]);

        $first = $this->postJson('/api/auth/demo-session')->assertCreated()
            ->assertJsonPath('data.user.role', UserRole::User->value)
            ->json('data');
        $second = $this->postJson('/api/auth/demo-session')->assertCreated()->json('data');

        $this->assertNotEquals($first['user']['id'], $second['user']['id']);
        $this->assertNotEquals($first['token'], $second['token']);
        $this->assertNull(User::findOrFail($first['user']['id'])->password);
        $this->withToken($first['token'])->getJson('/api/me')
            ->assertOk()->assertJsonPath('data.id', $first['user']['id']);
        Auth::forgetGuards();
        $this->withToken($second['token'])->getJson('/api/me')
            ->assertOk()->assertJsonPath('data.id', $second['user']['id']);
    }

    public function test_demo_visitor_cannot_repeat_another_visitors_order(): void
    {
        config(['lunch.hosted_demo' => true]);

        $first = $this->postJson('/api/auth/demo-session')->assertCreated()->json('data');
        $second = $this->postJson('/api/auth/demo-session')->assertCreated()->json('data');
        $cycle = OrderCycle::query()->create([
            'title' => 'Demo week',
            'starts_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
            'status' => OrderCycleStatus::Open,
        ]);
        $order = Order::query()->create([
            'user_id' => $second['user']['id'],
            'order_cycle_id' => $cycle->id,
            'status' => OrderStatus::Submitted,
            'total_price' => 0,
        ]);

        $this->withToken($first['token'])->postJson("/api/my-orders/{$order->id}/repeat")
            ->assertNotFound();
    }

    public function test_public_password_and_telegram_sign_in_are_unavailable_in_demo_mode(): void
    {
        config(['lunch.hosted_demo' => true]);

        foreach (['/api/auth/login', '/api/auth/telegram', '/api/auth/telegram-login'] as $uri) {
            $this->postJson($uri)->assertNotFound();
        }

        $this->postJson('/api/telegram/webhook')->assertNotFound();
        $this->get('/auth/telegram')->assertNotFound();
    }

    public function test_demo_order_writes_are_throttled_per_visitor(): void
    {
        config(['lunch.hosted_demo' => true]);
        $first = $this->postJson('/api/auth/demo-session')->assertCreated()->json('data');
        $this->withToken($first['token']);

        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/my-order/submit')->assertStatus(422);
        }

        $this->postJson('/api/my-order/submit')->assertStatus(429);
    }

    public function test_demo_session_creation_is_throttled(): void
    {
        config(['lunch.hosted_demo' => true]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/demo-session')->assertCreated();
        }

        $this->postJson('/api/auth/demo-session')->assertStatus(429);
    }
}
