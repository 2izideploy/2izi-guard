<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Risk;

final class BrowserSignals
{
    public static function sanitize(mixed $input): array
    {
        $in = is_array($input) ? $input : [];
        return [
            'js' => ($in['js'] ?? false) === true,
            'page_age_ms' => self::boundedInt($in['page_age_ms'] ?? 0, 0, 86_400_000),
            'pointer' => ($in['pointer'] ?? false) === true,
            'keyboard' => ($in['keyboard'] ?? false) === true,
            'touch' => ($in['touch'] ?? false) === true,
            'visibility_changes' => self::boundedInt($in['visibility_changes'] ?? 0, 0, 100),
            'form_bound' => ($in['form_bound'] ?? false) === true,
        ];
    }

    private static function boundedInt(mixed $value, int $min, int $max): int
    {
        if (!is_int($value) && !is_numeric($value)) {
            return $min;
        }
        return max($min, min($max, (int)$value));
    }
}
