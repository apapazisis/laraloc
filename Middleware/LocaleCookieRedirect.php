<?php

namespace App\Modules\Locale\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use App\Modules\Locale\Facades\Localization;

class LocaleCookieRedirect extends LocaleMiddlewareBase
{
    public function handle($request, Closure $next)
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $params = explode('/', $request->path());
        $locale = $request->cookie('locale', false);

        if (!empty($params[0]) && Localization::isLocaleSupported($params[0])) {
            return $next($request)
                ->withCookie(cookie()->forever('locale', $params[0]));
        }

        if ($locale === false){
            $locale = Localization::getCurrentLocale();
        }

        if ($locale && Localization::isLocaleSupported($locale)) {
            $redirection = Localization::getLocalizedURL($locale);

            return (new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']))
                ->withCookie(cookie()->forever('locale', $params[0]));
        }

        return $next($request);
    }
}
