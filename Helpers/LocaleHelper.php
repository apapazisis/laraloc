<?php

if (!function_exists('rt')) {
    function rt(string $route)
    {
        return Localization::transRoute($route);
    }
}
