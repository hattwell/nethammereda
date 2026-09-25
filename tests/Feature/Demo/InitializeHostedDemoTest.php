<?php

namespace Tests\Feature\Demo;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InitializeHostedDemoTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDirectory = sys_get_temp_dir().'/nethammereda-hosted-test-'.Str::uuid();
        mkdir($this->tempDirectory);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        foreach (glob($this->tempDirectory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tempDirectory);
        parent::tearDown();
    }

    private function useFreshDemoDatabase(): string
    {
        $path = $this->tempDirectory.'/demo.sqlite';
        config([
            'lunch.hosted_demo' => true,
            'lunch.hosted_demo_db_path' => $path,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $path,
        ]);
        DB::purge('sqlite');
        return $path;
    }

    public function test_initializer_refuses_execution_without_hosted_demo_flag(): void
    {
        $this->useFreshDemoDatabase();
        config(['lunch.hosted_demo' => false]);
        $this->artisan('demo:initialize-hosted')->assertFailed();
        $this->assertFileDoesNotExist($this->tempDirectory.'/demo.sqlite');
    }

    public function test_initializer_refuses_database_path_outside_dedicated_demo_path(): void
    {
        $this->useFreshDemoDatabase();
        config(['database.connections.sqlite.database' => $this->tempDirectory.'/other.sqlite']);
        $this->artisan('demo:initialize-hosted')->assertFailed();
        $this->assertFileDoesNotExist($this->tempDirectory.'/other.sqlite');
    }

    public function test_initializer_seeds_new_database_and_retries_without_erasing_visitor_data(): void
    {
        $path = $this->useFreshDemoDatabase();
        $this->artisan('demo:initialize-hosted')->assertSuccessful();
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, MenuItem::query()->count());
        $visitor = User::query()->create([
            'name' => 'Test visitor',
            'email' => 'test-visitor@example.invalid',
            'is_active' => true,
        ]);
        $this->artisan('demo:initialize-hosted')->assertSuccessful();
        $this->assertNotNull(User::query()->find($visitor->id));
    }

    public function test_initializer_refuses_unrecognized_nonempty_database(): void
    {
        $path = $this->useFreshDemoDatabase();
        file_put_contents($path, 'not a hosted demo database');
        $this->artisan('demo:initialize-hosted')->assertFailed();
        $this->assertSame('not a hosted demo database', file_get_contents($path));
    }
}
