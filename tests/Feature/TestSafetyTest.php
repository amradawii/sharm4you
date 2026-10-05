<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class TestSafetyTest extends TestCase
{
    public function test_both_photo_disks_are_local_fakes(): void
    {
        foreach (['public', 'r2'] as $disk) {
            $this->assertSame(storage_path('framework/testing/disks/'.$disk), rtrim(Storage::disk($disk)->path(''), '/\\'));
        }
    }

    public function test_guard_rejects_non_testing_environment(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->ensureSafeTestApplication($this->app);
    }

    public function test_guard_rejects_another_database_before_connecting(): void
    {
        config(['database.connections.mysql.database' => 'sharm4you']);
        DB::purge('mysql');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to refresh');
        $this->ensureSafeTestApplication($this->app);
    }

    public function test_guard_rejects_a_database_url_override(): void
    {
        config(['database.connections.mysql.url' => 'mysql://root@127.0.0.1/sharm4you']);
        DB::purge('mysql');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to refresh');
        $this->ensureSafeTestApplication($this->app);
    }

    public function test_guard_rejects_persistent_cache_configuration(): void
    {
        config(['cache.default' => 'database']);
        $this->expectException(RuntimeException::class);
        $this->ensureSafeTestApplication($this->app);
    }
}
