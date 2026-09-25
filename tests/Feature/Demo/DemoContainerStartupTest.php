<?php

namespace Tests\Feature\Demo;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class DemoContainerStartupTest extends TestCase
{
    public function test_start_script_refuses_to_boot_without_safe_demo_environment(): void
    {
        $process = new Process(['/bin/sh', base_path('docker/start.sh')], base_path(), [
            'HOSTED_DEMO' => 'false',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'DB_DATABASE' => '/var/www/html/storage/demo.sqlite',
        ]);
        $process->run();

        $this->assertFalse($process->isSuccessful());
        $this->assertStringContainsString('Refusing hosted demo startup', $process->getErrorOutput());
    }

    public function test_start_script_refuses_wrong_database_even_with_demo_enabled(): void
    {
        $process = new Process(['/bin/sh', base_path('docker/start.sh')], base_path(), [
            'HOSTED_DEMO' => 'true',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => '/other/database.sqlite',
        ]);
        $process->run();

        $this->assertFalse($process->isSuccessful());
        $this->assertStringContainsString('Refusing hosted demo startup', $process->getErrorOutput());
    }
}
