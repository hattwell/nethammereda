<?php

namespace Tests\Feature\Demo;

use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\OrderCycle;
use App\Models\User;
use Database\Seeders\DemoHostedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DemoHostedSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_hosted_seeder_refuses_unguarded_invocation(): void
    {
        $this->expectException(LogicException::class);
        $this->seed(DemoHostedSeeder::class);
    }

    public function test_hosted_seeder_creates_only_fictional_menu_and_viewer_without_public_admin_password(): void
    {
        config(['lunch.hosted_demo_bootstrap_authorized' => true]);
        $this->seed(DemoHostedSeeder::class);

        $viewer = User::query()->where('role', UserRole::DemoViewer)->firstOrFail();
        $this->assertNotEquals('password', $viewer->password);
        $this->assertNull(User::query()->where('role', UserRole::Admin)->first());
        $this->assertGreaterThan(0, MenuItem::query()->count());
        $this->assertSame(1, OrderCycle::query()->count());
    }
}
