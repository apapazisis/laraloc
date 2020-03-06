<?php

namespace App\Modules\Locale;

use Illuminate\Http\Request;
use Locale;

class LanguageHandler
{
    protected $config;
    protected $app;
    private $defaultLocale;
    private $supportedLanguages;
    private $request;
    private $use_intl = false;

    public function __construct($defaultLocale, $supportedLanguages, Request $request)
    {
        $this->app = app();
        $this->config = $this->app['config'];
        $this->defaultLocale = $defaultLocale;
        $this->request = $request;

        if (class_exists('Locale')) {
            $this->use_intl = true;

            foreach ($supportedLanguages as $key => $supportedLanguage) {
                if (!isset($supportedLanguage['lang'])) {
                    $supportedLanguage['lang'] = Locale::canonicalize($key);
                } else {
                    $supportedLanguage['lang'] = Locale::canonicalize($supportedLanguage['lang']);
                }

                if (isset($supportedLanguage['regional'])) {
                    $supportedLanguage['regional'] = Locale::canonicalize($supportedLanguage['regional']);
                }
                $this->supportedLanguages[$key] = $supportedLanguage;
            }
        } else {
            $this->supportedLanguages = $supportedLanguages;
        }
    }

    public function handle()
    {
        if ($this->request->user() !== null) {
            $userLocale = $this->request->user()->language->locale;

            if (!empty($this->supportedLanguages[$userLocale])) {
                return $userLocale;
            }
        }

        if ($this->use_intl && !empty($this->request->server('HTTP_ACCEPT_LANGUAGE'))) {
            $http_accept_language = $this->request->getPreferredLanguage(array_keys($this->config->get('locale.supported_locales')));

            if (!empty($this->supportedLanguages[$http_accept_language])) {
                return $http_accept_language;
            }
        }

        if ($this->request->server('REMOTE_HOST')) {
            $remote_host = explode('.', $this->request->server('REMOTE_HOST'));
            $lang = strtolower(end($remote_host));

            if (!empty($this->supportedLanguages[$lang])) {
                return $lang;
            }
        }

        return $this->defaultLocale;
    }
}
