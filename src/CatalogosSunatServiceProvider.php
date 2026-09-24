<?php

namespace Aeunius\CatalogosSunat;

use Aeunius\CatalogosSunat\Rules\CodigoCatalogo;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CatalogosSunatServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('catalogos-sunat')
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        // Una sola instancia: los catálogos se leen una vez por proceso.
        $this->app->singleton(Catalogos::class);
    }

    public function packageBooted(): void
    {
        // Regla en texto: codigo_catalogo:02
        //
        // El mensaje se resuelve al validar y no al arrancar la aplicación, para
        // que respete el idioma activo en ese momento. Si la aplicación define
        // validation.codigo_catalogo en sus traducciones, ese mensaje manda.
        ValidatorFacade::extend('codigo_catalogo', function (string $attribute, mixed $value, array $parameters, Validator $validator): bool {
            if (! isset($parameters[0]) || ! is_string($parameters[0])) {
                throw new \InvalidArgumentException('La regla codigo_catalogo necesita el número de catálogo: codigo_catalogo:02.');
            }

            $failure = null;

            (new CodigoCatalogo($parameters[0]))->validate($attribute, $value, function (string $message) use (&$failure): PotentiallyTranslatedString {
                return $failure = new PotentiallyTranslatedString($message, $this->app->make('translator'));
            });

            if ($failure === null) {
                return true;
            }

            $validator->fallbackMessages['codigo_catalogo'] = (string) $failure;

            return false;
        });
    }
}
