<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Locale;

use TwoIzi\Guard\Config\Config;

final class Translator
{
    private array $cache = [];

    public function __construct(private Config $config, private ?string $localeDir = null)
    {
        $this->localeDir ??= dirname(__DIR__, 2) . '/locales';
    }

    public function resolve(string $requested): string
    {
        $available = array_values(array_filter((array)$this->config->get('localization.available', ['en']), 'is_string'));
        $default = (string)$this->config->get('localization.default', 'en');
        $requested = trim(str_replace('_', '-', $requested));
        if ($requested !== '' && preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/', $requested)) {
            foreach ($available as $candidate) {
                if (strcasecmp($candidate, $requested) === 0) {
                    return $candidate;
                }
            }
            $base = strtolower(explode('-', $requested)[0]);
            foreach ($available as $candidate) {
                if (strtolower(explode('-', $candidate)[0]) === $base) {
                    return $candidate;
                }
            }
        }
        return in_array($default, $available, true) ? $default : ($available[0] ?? 'en');
    }

    public function bundle(string $requested): array
    {
        $locale = $this->resolve($requested);
        $messages = $this->load($locale);
        $fallback = $locale === 'en' ? [] : $this->load('en');
        return [
            'locale' => $locale,
            'messages' => array_merge($fallback, $messages),
        ];
    }

    private function load(string $locale): array
    {
        if (isset($this->cache[$locale])) {
            return $this->cache[$locale];
        }
        $file = rtrim((string)$this->localeDir, '/') . '/' . $locale . '.json';
        if (!is_file($file)) {
            return $this->cache[$locale] = [];
        }
        $decoded = json_decode((string)file_get_contents($file), true);
        return $this->cache[$locale] = is_array($decoded) ? $decoded : [];
    }
}
