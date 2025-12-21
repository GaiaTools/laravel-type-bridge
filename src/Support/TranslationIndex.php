<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use Illuminate\Support\Facades\File;
use ReflectionEnum;
use UnitEnum;

/**
 * Lightweight translation index for existence checks against Laravel lang files.
 */
final class TranslationIndex
{
    use TranslationResolver;

    /** @var array<string, mixed>|null */
    private ?array $flat = null;

    public function __construct(
        private readonly ?string $locale = null,
        private readonly ?TranslationDiscoveryConfig $config = null,
    ) {}

    /**
     * @param  ReflectionEnum<UnitEnum>  $enum
     */
    public function hasAnyForEnum(string $translationPrefix, ReflectionEnum $enum): bool
    {
        $flat = $this->flat() ?: [];

        foreach ($enum->getCases() as $case) {
            $key = $translationPrefix.'.'.$case->name;
            if (array_key_exists($key, $flat)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load and cache a flattened view of translations for the chosen locale.
     *
     * @return array<string, mixed>
     */
    private function flat(): array
    {
        if ($this->flat !== null) {
            return $this->flat;
        }

        $locale = $this->locale ?? config()->string('app.locale', 'en');

        $roots = ($this->config ?? TranslationDiscoveryConfig::fromConfig())->langPaths;
        $merged = [];

        foreach ($roots as $root) {
            $dir = $this->buildLocaleDir($root, $locale);
            if (! File::isDirectory($dir)) {
                continue;
            }

            $current = $this->loadLocaleDir($dir);
            // Normalize class-like keys (e.g., FQCN) to short names to mirror TranslationTransformer
            $current = $this->normalizeClassLikeKeys($current);
            $merged = array_replace_recursive($merged, $current);
        }

        $this->flat = $this->dotFlatten($merged);

        return $this->flat;
    }

    // Methods moved to TranslationResolver trait
}
