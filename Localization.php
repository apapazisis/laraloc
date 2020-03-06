<?php

namespace App\Modules\Locale;

use App\Modules\Locale\Exceptions\SupportedLocalesNotDefined;
use App\Modules\Locale\Exceptions\UnsupportedLocaleException;

class Localization
{
    protected $config;
    protected $view;
    protected $translator;
    protected $router;
    protected $request;
    protected $url;
    protected $app;
    protected $baseUrl;
    protected $defaultLocale;
    protected $supportedLocales;
    protected $currentLocale = false;
    protected $translatedRoutes = [];
    protected $routeName;

    public function __construct()
    {
        $this->app = app();

        $this->config = $this->app['config'];
        $this->view = $this->app['view'];
        $this->translator = $this->app['translator'];
        $this->router = $this->app['router'];
        $this->request = $this->app['request'];
        $this->url = $this->app['url'];

        // set default locale
        $this->defaultLocale = $this->config->get('app.locale');
        $supportedLocales = $this->getSupportedLocales();

        if (empty($supportedLocales[$this->defaultLocale])) {
            throw new UnsupportedLocaleException('Laravel default locale is not in the supportedLocales array.');
        }
    }

    /**
     * Set and return current locale.
     *
     * @param null $locale
     * @return string|null
     * @throws SupportedLocalesNotDefined
     */
    public function setLocale($locale = null)
    {
        if (empty($locale) || !is_string($locale)) {
            $locale = $this->request->segment(1);
        }

        if (!empty($this->supportedLocales[$locale])) {
            $this->currentLocale = $locale;
        } else {
            $locale = null;
            $this->currentLocale = $this->getCurrentLocale();
        }

        $this->app->setLocale($this->currentLocale);
        $regional = $this->getCurrentLocaleRegional();
        $suffix = $this->config->get('locale.utf8suffix');

        if ($regional) {
            setlocale(LC_TIME, $regional . $suffix);
            setlocale(LC_MONETARY, $regional . $suffix);
        }

        return $locale;
    }

    public function getLocalizedURL($locale = null)
    {
        if ($locale === null) {
            $locale = $this->getCurrentLocale();
        }

        if (!$this->isLocaleSupported($locale)) {
            throw new UnsupportedLocaleException('Locale \''.$locale.'\' is not in the list of supported locales.');
        }

        $url = null;

        if (!empty($locale)) {
            $url = $locale . DIRECTORY_SEPARATOR;
        }

        if ($this->checkUrl($url)) {
            return $url;
        }

        return $this->createUrlFromUri($url);
    }

    /**
     * Returns default locale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }


    /**
     * @return array
     * @throws \App\Modules\Locale\Exceptions\SupportedLocalesNotDefined
     */
    public function getSupportedLocales(): array
    {
        if (!empty($this->supportedLocales)) {
            return $this->supportedLocales;
        }

        $locales = $this->config->get('locale.supported_locales');

        if (empty($locales) || !is_array($locales)) {
            throw new SupportedLocalesNotDefined();
        }

        $this->supportedLocales = $locales;

        return $locales;
    }

    /**
     * Return an array of all supported Locales but in the order the user
     * has specified in the config file. Useful for the language selector.
     *
     * @return array
     * @throws SupportedLocalesNotDefined
     */
    public function getLocalesOrder()
    {
        $locales = $this->getSupportedLocales();

        $order = $this->config->get('locale.localesOrder');

        uksort($locales, function ($a, $b) use ($order) {
            $pos_a = array_search($a, $order);
            $pos_b = array_search($b, $order);
            return $pos_a - $pos_b;
        });

        return $locales;
    }

    /**
     * @return mixed|string
     * @throws SupportedLocalesNotDefined
     */
    public function getCurrentLocale()
    {
        if ($this->currentLocale) {
            return $this->currentLocale;
        }

        if (!$this->app->runningInConsole()) {
            return (new LanguageHandler($this->defaultLocale, $this->getSupportedLocales(), $this->request))->handle();
        }

        // or get application default language
        return $this->config->get('app.locale');
    }

    public function getCurrentLocaleRegional()
    {
        if (isset($this->supportedLocales[$this->getCurrentLocale()]['regional'])) {
            return $this->supportedLocales[$this->getCurrentLocale()]['regional'];
        }

        return;
    }

    public function isLocaleSupported($locale): bool
    {
        $locales = $this->getSupportedLocales();

        if ($locale !== false && empty($locales[$locale])) {
            return false;
        }

        return true;
    }

    public function transRoute($routeName)
    {
        if (!in_array($routeName, $this->translatedRoutes)) {
            $this->translatedRoutes[] = $routeName;
        }

        return $this->translator->get($routeName);
    }

    protected function checkUrl($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public function createUrlFromUri($uri)
    {
        $uri = ltrim($uri, '/');

        if (empty($this->baseUrl)) {
            return app('url')->to($uri);
        }

        return $this->baseUrl . $uri;
    }

    public function getURLFromRouteNameTranslated($locale, $transKeyName)
    {
        if (is_null($locale) || !is_string($locale)) {
            $locale = $this->getCurrentLocale();
        }

        if (!$this->isLocaleSupported($locale)) {
            throw new UnsupportedLocaleException('Locale \''.$locale.'\' is not in the list of supported locales.');
        }

        $route = '/' . $locale;

        if (is_string($locale) && $this->translator->has($transKeyName, $locale)) {
            $translation = $this->translator->get($transKeyName, [], $locale);
            $route .= '/' . $translation;
        }

        if (empty($route)) {
            return false;
        }

        return rtrim($this->createUrlFromUri($route), '/');
    }
}
