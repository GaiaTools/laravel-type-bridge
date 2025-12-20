<?php

namespace GaiaTools\TypeBridge\Discoverers;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;
use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Support\TranslationIndex;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionEnum;
use SplFileInfo;
use UnitEnum;

final class EnumTranslatorDiscoverer implements Discoverer
{
    public function __construct(
        private readonly EnumTranslatorDiscoveryConfig $config,
        private readonly EnumTokenParser $tokenParser,
        /** @var list<class-string<UnitEnum>>|null */
        private readonly ?array $allowedEnums = null,
        private readonly ?TranslationIndex $translationIndex = null,
    ) {}

    /**
     * Discover enum reflections with their translation keys.
     *
     * @return Collection<int, mixed>
     */
    public function discover(): Collection
    {
        $paths = collect($this->config->discoveryPaths);

        /** @var Collection<int, string> $classes */
        $classes = $paths->flatMap(function (string $path): Collection {
            return collect(File::allFiles($path))
                ->map(fn (SplFileInfo $file) => $file->getPathname())
                ->filter(fn (string $filePath) => Str::endsWith($filePath, '.php'))
                ->flatMap(function (string $filepath): array {
                    return $this->tokenParser->extractEnumFqcnsFromFile($filepath);
                })
                ->unique()
                ->values();
        });

        /** @var Collection<int, mixed> $items */
        $items = $classes
            ->filter(fn (string $class) => enum_exists($class))
            ->map(fn (string $enumClass) => new ReflectionEnum($enumClass))
            ->filter(fn (ReflectionEnum $ref) => $this->shouldInclude($ref))
            ->map(function (ReflectionEnum $ref) {
                return [
                    'reflection' => $ref,
                    'translationKey' => $this->getTranslationKey($ref),
                ];
            })
            ->filter(fn (array $item) => $item['translationKey'] !== null)
            ->values();

        // If an allowlist of enums is provided, intersect with it (FE-generated enums)
        if (is_array($this->allowedEnums)) {
            /** @var Collection<int, mixed> $items */
            $items = $items->filter(function (array $item): bool {
                /** @var ReflectionEnum<UnitEnum> $ref */
                $ref = $item['reflection'];
                return in_array($ref->getName(), $this->allowedEnums, true);
            })->values();
        }

        // If a TranslationIndex is available, keep only enums that have at least one translation string
        if ($this->translationIndex !== null) {
            /** @var Collection<int, mixed> $items */
            $items = $items->filter(function (array $item): bool {
                /** @var ReflectionEnum<UnitEnum> $ref */
                $ref = $item['reflection'];
                /** @var string $prefix */
                $prefix = $item['translationKey'];
                return $this->translationIndex->hasAnyForEnum($prefix, $ref);
            })->values();
        }

        return $items;
    }

    /** @param ReflectionEnum<UnitEnum> $reflection */
    private function shouldInclude(ReflectionEnum $reflection): bool
    {
        $short = $reflection->getShortName();
        $fqcn = $reflection->getName();

        $excludedEnums = array_map(
            static fn ($value) => mb_strtolower($value),
            $this->config->excludes
        );

        return ! in_array(mb_strtolower($short), $excludedEnums, true)
            && ! in_array(mb_strtolower($fqcn), $excludedEnums, true);
    }

    /** @param ReflectionEnum<UnitEnum> $reflection */
    private function getTranslationKey(ReflectionEnum $reflection): ?string
    {
        $attributes = $reflection->getAttributes(GenerateTranslator::class);

        if (! empty($attributes)) {
            $attribute = $attributes[0]->newInstance();

            if (! $attribute->generateComposable) {
                return null;
            }

            if ($attribute->translationKey) {
                return $attribute->translationKey;
            }
        }

        // Convention: Use class basename as translation key
        return $reflection->getShortName();
    }
}
