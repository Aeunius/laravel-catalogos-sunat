<?php

namespace Aeunius\CatalogosSunat\Tests;

use Aeunius\CatalogosSunat\CatalogosSunatServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CatalogosSunatServiceProvider::class,
        ];
    }
}
