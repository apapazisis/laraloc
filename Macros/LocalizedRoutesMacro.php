<?php

namespace App\Modules\Locale\Macros;

use Illuminate\Support\Facades\Route;
use App\Modules\Locale\Facades\Localization;

class LocalizedRoutesMacro
{
    public static function register()
    {
        Route::macro('localized', function ($callback, $options = []) {
            $attributes = [];

            $attributes['prefix'] = Localization::setLocale();
            $attributes['middleware'] = ['localeSessionRedirect', 'localeCookieRedirect'];

            Route::group(array_merge_recursive($attributes, $options), $callback);
        });
    }
}
