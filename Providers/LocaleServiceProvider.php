<?php

namespace App\Modules\Locale\Providers;

use App\Modules\Locale\Localization;
use App\Modules\Locale\Macros\LocalizedRoutesMacro;
use Illuminate\Support\ServiceProvider;

class LocaleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerMacros();
        $this->registerBindings();
        $this->registerHelpers();
    }

    protected function registerBindings()
    {
        $this->app->bind('localization', function () {
            return new Localization();
        });
    }

    protected function registerMacros()
    {
        LocalizedRoutesMacro::register();
    }

    protected function registerHelpers()
    {
        foreach (glob(app_path('Modules/Locale/Helpers/*.php')) as $helper) {
            require_once $helper;
        }
    }
}
