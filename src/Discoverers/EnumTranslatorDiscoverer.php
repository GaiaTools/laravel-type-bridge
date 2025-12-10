<?php

namespace GaiaTools\TypeBridge\Discoverers;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;
use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionEnum;
use SplFileInfo;

final class EnumTranslatorDiscoverer implements Discoverer
{
    public function __construct(
        private readonly EnumTranslatorDiscoveryConfig $config,
        private readonly EnumTokenParser $tokenParser,
    ) {
    }

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

        /** @var Collection<int, array{reflection: ReflectionEnum, translationKey: string}> */
        $result = $classes
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

        return $result;
    }


    private function shouldInclude(ReflectionEnum $reflection): bool
    {
        $short = $reflection->getShortName();
        $fqcn = $reflection->getName();

        $excludedEnums = array_map(
            static fn ($v) => mb_strtolower($v),
            $this->config->excludes
        );

        return !in_array(mb_strtolower($short), $excludedEnums, true)
            && !in_array(mb_strtolower($fqcn), $excludedEnums, true);
    }

    private function getTranslationKey(ReflectionEnum $reflection): ?string
    {
        $attributes = $reflection->getAttributes(GenerateTranslator::class);

        if (!empty($attributes)) {
            $attribute = $attributes[0]->newInstance();

            if (!$attribute->generateComposable) {
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
