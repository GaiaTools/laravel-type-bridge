<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Discoverers;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionEnum;
use SplFileInfo;
use UnitEnum;

final class EnumDiscoverer implements Discoverer
{
    public function __construct(
        private readonly EnumDiscoveryConfig $config,
        private readonly EnumTokenParser $tokenParser,
    ) {}

    /**
     * @return Collection<int, ReflectionEnum<UnitEnum>>
     */
    public function discover(): Collection
    {
        $paths = collect($this->config->paths);

        /** @var Collection<int, string> $classes */
        $classes = $paths->flatMap(function (string $path): Collection {
            return collect(File::allFiles($path))
                ->map(fn (SplFileInfo $file) => $file->getPathname())
                ->filter(fn (string $filePath) => Str::endsWith($filePath, '.php'))
                ->flatMap(function (string $filepath): array {
                    return $this->extractEnumFqcnsFromFile($filepath);
                })
                ->unique()
                ->values();
        });

        /** @var Collection<int, class-string<UnitEnum>> $enums */
        $enums = $classes->filter(function (string $class): bool {
            if (! enum_exists($class)) {
                return false;
            }

            $ref = new ReflectionEnum($class);
            $hasAttribute = ! empty($ref->getAttributes(GenerateEnum::class));

            // Always include enums with GenerateEnum attribute
            if ($hasAttribute) {
                return true;
            }

            // Include backed enums only if generateBackedEnums is enabled
            return $this->config->generateBackedEnums && $ref->isBacked();
        })->values();

        $result = $enums
            ->map(function (string $enumClass) {
                /** @var class-string<UnitEnum> $enumClass */
                return new ReflectionEnum($enumClass);
            })
            ->filter(fn (ReflectionEnum $ref): bool => $this->shouldInclude($ref))
            ->values();

        /** @var Collection<int, ReflectionEnum<UnitEnum>> $result */
        return $result->values();
    }

    /**
     * Extract fully-qualified enum names declared in a PHP file by parsing its tokens.
     *
     * @return array<int, string>
     */
    private function extractEnumFqcnsFromFile(string $filepath): array
    {
        return $this->tokenParser->extractEnumFqcnsFromFile($filepath);
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     */
    private function shouldInclude(ReflectionEnum $reflection): bool
    {
        $short = $reflection->getShortName();
        $fqcn = $reflection->getName();

        $excludedEnums = array_map(static fn ($v) => mb_strtolower($v), $this->config->excludes);

        return ! in_array(mb_strtolower($short), $excludedEnums, true)
            && ! in_array(mb_strtolower($fqcn), $excludedEnums, true);
    }
}
