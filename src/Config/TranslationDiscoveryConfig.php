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
        // Read configured paths and resolve candidates; fall back to Laravel's default
        /** @var array<string,mixed>|null $cfg */
        $cfg = config()->get('type-bridge.translations');

        $configured = self::configuredLangPaths($cfg);
        $candidates = $configured ?: [base_path('lang')];

        return new self(langPaths: self::resolveCandidates($candidates));
    }

    /**
     * @param  array<string,mixed>|null  $cfg
     * @return array<int,string>
     */
    private static function configuredLangPaths(?array $cfg): array
    {
        if (! is_array($cfg)) {
            return [];
        }

        $raw = Arr::get($cfg, 'lang_paths');
        $out = [];
        foreach ((array) ($raw ?? []) as $p) {
            if (is_string($p) && $p !== '') {
                $out[] = $p;
            }
        }

        return $out;
    }

    /**
     * Resolve globs and keep only existing directories; de-duplicate while preserving order.
     *
     * @param  array<int,string>  $candidates
     * @return array<int,string>
     */
    private static function resolveCandidates(array $candidates): array
    {
        $resolved = [];
        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '*')) {
                $paths = glob($candidate) ?: [];
            } else {
                $paths = [$candidate];
            }
            foreach ($paths as $path) {
                self::appendUniqueExistingDir($resolved, $path);
            }
        }

        return $resolved;
    }

    /**
     * @param  array<int,string>  $list
     */
    private static function appendUniqueExistingDir(array &$list, string $path): void
    {
        if (is_dir($path) && ! in_array($path, $list, true)) {
            $list[] = $path;
        }
    }
}
