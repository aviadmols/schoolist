<?php
declare(strict_types=1);

namespace App\Services;

class I18n
{
    private string $lang;
    private array $translations = [];

    public function __construct(string $lang = 'he')
    {
        $this->lang = $lang;
        $this->loadTranslations();
        $GLOBALS['i18n'] = $this;
    }

    private function loadTranslations(): void
    {
        $file = CONFIG_PATH . '/lang/' . $this->lang . '.php';
        if (file_exists($file)) {
            $this->translations = require $file;
        }
    }

    public function t(string $key, array $params = []): string
    {
        $value = $this->translations[$key] ?? $key;
        
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $value = str_replace("{{$k}}", $v, $value);
            }
        }
        
        return $value;
    }

    public function lang(): string
    {
        return $this->lang;
    }
}















