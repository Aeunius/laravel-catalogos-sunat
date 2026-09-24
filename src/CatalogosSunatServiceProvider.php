<?php

namespace Aeunius\CatalogosSunat;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CatalogosSunatServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('catalogos-sunat');
    }

    public function packageRegistered(): void
    {
        // Una sola instancia: los catálogos se leen una vez por proceso.
        $this->app->singleton(Catalogos::class);
    }
}
