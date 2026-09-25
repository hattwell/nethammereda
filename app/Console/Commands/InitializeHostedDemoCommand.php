<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\OrderCycle;
use App\Models\User;
use Database\Seeders\DemoHostedSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InitializeHostedDemoCommand extends Command
{
    protected $signature = 'demo:initialize-hosted';

    protected $description = 'Initialize only a new, isolated hosted demo SQLite database';

    public function handle(): int
    {
        $expected = (string) config('lunch.hosted_demo_db_path');
        $database = (string) config('database.connections.sqlite.database');

        if (
            ! config('lunch.hosted_demo')
            || config('database.default') !== 'sqlite'
            || filled(config('database.connections.sqlite.url'))
            || $expected === ''
            || ! str_starts_with($expected, '/')
            || $database !== $expected
            || is_link($expected)
            || ($this->laravel->environment('production') && $expected !== storage_path('demo.sqlite'))
        ) {
            $this->error('Refusing demo initialization: demo mode and dedicated SQLite database path are required.');
            return self::FAILURE;
        }

        if (! is_dir(dirname($expected)) || ! is_writable(dirname($expected))) {
            $this->error('Demo database directory is missing or not writable.');
            return self::FAILURE;
        }

        if (is_file($expected) && filesize($expected) > 0) {
            try {
                $valid = Schema::hasTable('users')
                    && Schema::hasTable('menu_items')
                    && Schema::hasTable('order_cycles')
                    && User::query()->where('role', UserRole::DemoViewer)->exists()
                    && MenuItem::query()->exists()
                    && OrderCycle::query()->exists();
            } catch (Throwable) {
                $valid = false;
            }

            if (! $valid) {
                $this->error('Existing SQLite database is not a recognized hosted demo. Refusing to overwrite it.');
                return self::FAILURE;
            }

            $this->info('Existing hosted demo preserved.');
            return self::SUCCESS;
        }

        if (! is_file($expected) && file_put_contents($expected, '') === false) {
            $this->error('Cannot create demo database.');
            return self::FAILURE;
        }

        // Never call demo:reset or migrate:fresh on an already initialized DB.
        DB::purge('sqlite');
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        config(['lunch.hosted_demo_bootstrap_authorized' => true]);
        try {
            return $this->call('db:seed', ['--class' => DemoHostedSeeder::class, '--force' => true]);
        } finally {
            config(['lunch.hosted_demo_bootstrap_authorized' => false]);
        }
    }
}
