<?php

namespace Sarmadict\FilamentMedia\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sarmadict\FilamentMedia\FilamentMediaServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentMediaServiceProvider::class,
        ];
    }
}
