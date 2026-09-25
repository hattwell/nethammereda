<?php

namespace Tests\Feature\Demo;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DemoHostedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_entry_is_unavailable_without_hosted_demo_mode(): void
    {
        config(['lunch.hosted_demo' => false]);

        $this->get('/demo/admin')->assertNotFound();
    }

    public function test_viewer_can_open_only_safe_admin_pages_and_cannot_mutate(): void
    {
        config(['lunch.hosted_demo' => true]);
        $viewer = User::factory()->create([
            'role' => UserRole::DemoViewer,
            'is_active' => true,
            'email' => 'viewer@example.invalid',
        ]);
        $visitor = User::factory()->create([
            'role' => UserRole::User,
            'email' => 'visitor-secret@example.invalid',
        ]);

        $this->get('/demo/admin')->assertRedirect('/admin');
        $this->assertAuthenticatedAs($viewer, 'web');
        $this->get('/admin')->assertOk()->assertSee('Панель управления')
            ->assertDontSee('visitor-secret@example.invalid');
        $this->get('/admin/menu-items')->assertOk()->assertDontSee('Добавить блюдо');
        $this->get('/admin/menu-categories')->assertOk();
        foreach (['/admin/users', '/admin/orders', '/admin/order-cycles', '/admin/menu-items/create', '/admin/menu-items/1/edit'] as $uri) {
            self::assertSame(403, $this->get($uri)->status(), $uri);
        }
        self::assertSame(403, $this->post(route('default-livewire.update'), [])->status(), 'Livewire update');
        self::assertSame(405, $this->post('/admin/menu-items')->status(), '/admin/menu-items has no POST route');
        $this->patchJson('/api/me/profile', ['full_name' => 'Mutated viewer'])->assertForbidden();
        $this->assertDatabaseMissing('users', [
            'id' => $viewer->id,
            'full_name' => 'Mutated viewer',
        ]);
    }

    public function test_viewer_list_shows_fictional_menu_without_edit_link_or_visitor_data(): void
    {
        config([
            'lunch.hosted_demo' => true,
            'lunch.hosted_demo_bootstrap_authorized' => true,
        ]);
        $this->seed(DemoHostedSeeder::class);
        User::factory()->create(['email' => 'private-visitor@example.invalid']);

        $this->get('/demo/admin')->assertRedirect('/admin');
        $this->get('/admin/menu-items')
            ->assertOk()
            ->assertSee('Суп овощной')
            ->assertDontSee('private-visitor@example.invalid')
            ->assertDontSee('Добавить блюдо')
            ->assertDontSee('/admin/menu-items/1/edit');
    }

    public function test_guest_and_normal_visitor_cannot_open_admin(): void
    {
        config(['lunch.hosted_demo' => true]);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(['role' => UserRole::User]), 'web');
        $this->get('/admin')->assertForbidden();
    }
}
