<?php

/**
 * Charge un fichier .env (KEY=VALUE) dans un tableau associatif.
 */
class Env
{
    public static function load(string $path): array
    {
        $vars = [];

        if (!is_file($path)) {
            return $vars;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value);
        }

        return $vars;
    }
}
