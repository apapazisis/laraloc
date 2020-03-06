<?php

namespace App\Modules\Locale\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use App\Modules\Locale\Facades\Localization;
use Illuminate\Http\Request;

class LocaleSessionRedirect extends LocaleMiddlewareBase
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $params = explode('/', $request->path());
        $locale = session('locale', false);

        if (!empty($params[0]) && Localization::islocaleSupported($params[0])) {
            session(['locale' => $params[0]]);

            return $next($request);
        }

        if ($locale === false) {
            $locale = Localization::getCurrentLocale();
        }

        if ($locale && Localization::islocaleSupported($locale)) {
            app('session')->reflash(); // keep all of the flash data for an additional request
            $redirection = Localization::getLocalizedURL($locale);

            return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
        }

        return $next($request);
    }
}
