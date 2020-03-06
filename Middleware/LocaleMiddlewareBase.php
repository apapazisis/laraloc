<?php

namespace App\Modules\Locale\Middleware;


class LocaleMiddlewareBase
{
    protected $except;

    protected function shouldIgnore($request)
    {
        $this->except = config('locale.ignored_urls', []);

        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
