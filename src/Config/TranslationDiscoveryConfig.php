<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Config;

use Illuminate\Support\Arr;

use function str_contains;

final readonly class TranslationDiscoveryConfig
{
    /**
     * @param  array<int,string>  $langPaths  Directories that may contain translation locales
     */
    public function __construct(
        public array $langPaths,
    ) {}

    public static function fromConfig(): self
    {
        // Read optional configured paths; allow strings or arrays. Globs are allowed.
        /** @var array<string,mixed>|null $cfg */
        $cfg = config()->get('type-bridge.translations');
        $configured = [];
        if (is_array($cfg)) {
            $raw = Arr::get($cfg, 'lang_paths');
            foreach ((array) ($raw ?? []) as $p) {
                if (is_string($p) && $p !== '') {
                    $configured[] = $p;
                }
            }
        }

        // Fallback: Laravel's default lang directory
        $defaults = [
            base_path('lang'),
        ];

        $candidates = ! empty($configured) ? $configured : $defaults;

        // Resolve globs and keep only existing directories; de-duplicate while preserving order
        $resolved = [];
        foreach ($candidates as $candidate) {
            $paths = str_contains($candidate, '*') ? (glob($candidate) ?: []) : [$candidate];
            foreach ($paths as $path) {
                if (is_dir($path) && ! in_array($path, $resolved, true)) {
                    $resolved[] = $path;
                }
            }
        }

        return new self(
            langPaths: $resolved,
        );
    }
}
