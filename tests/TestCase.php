<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    //

    public function createApplication()
    {
        $app = parent::createApplication();
        $this->ensureSafeTestApplication($app);

        Storage::fake('public');
        Storage::fake('r2');

        return $app;
    }

    protected function ensureSafeTestApplication(Application $app): void
    {
        if (! $app->environment('testing') || $app['config']->get('database.default') !== 'mysql') {
            throw new RuntimeException('Tests must run in the testing environment on MySQL sharm4you_test.');
        }

        $connection = $app['db']->connection();
        if ($connection->getDatabaseName() !== 'sharm4you_test'
            || $connection->selectOne('SELECT DATABASE() AS name')->name !== 'sharm4you_test') {
            throw new RuntimeException('Refusing to refresh a database other than sharm4you_test.');
        }

        foreach (['cache.default' => 'array', 'session.driver' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array'] as $key => $expected) {
            if ($app['config']->get($key) !== $expected) {
                throw new RuntimeException('Unsafe test configuration: '.$key.' must be '.$expected.'.');
            }
        }
    }
}
