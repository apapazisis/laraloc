<?php

if (!function_exists('rt')) {
    function rt(string $route)
    {
        return Localization::transRoute($route);
    }
}

if (!function_exists('route_locale')) {
    function route_locale($transKeyName, $locale = null)
    {
        return Localization::getURLFromRouteNameTranslated($locale, $transKeyName);
    }
}
