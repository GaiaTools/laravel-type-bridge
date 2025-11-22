<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Transformers;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\ValueObjects\EnumCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use UnitEnum;

final class EnumTransformer implements Transformer
{
    public function __construct(
        private readonly GeneratorConfig $config,
    ) {}

    public function transform(mixed $source): TransformedEnum
    {
        assert($source instanceof ReflectionEnum);

        $attribute = $this->getAttribute($source);
        $requiresComments = ($attribute && $attribute->requiresComments);

        $cases = $this->extractCases($source, $requiresComments);

        $outputPath = resource_path($this->config->enumOutputPath);

        return new TransformedEnum(
            name: $source->getShortName(),
            cases: $cases,
            namespace: $source->getNamespaceName(),
            outputPath: $outputPath,
        );
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @return Collection<int, EnumCase>
     */
    private function extractCases(ReflectionEnum $reflection, bool $requiresComments): Collection
    {
        return collect($reflection->getCases())
            ->filter(fn (ReflectionEnumBackedCase|ReflectionEnumUnitCase $case) => $case instanceof ReflectionEnumBackedCase)
            ->map(function (ReflectionEnumBackedCase $case) use ($requiresComments, $reflection) {
                if ($requiresComments && ! $case->getDocComment()) {
                    throw new \RuntimeException(
                        "Enum {$reflection->getName()} case {$case->getName()} is missing a doc comment."
                    );
                }

                return new EnumCase(
                    name: $case->getName(),
                    value: $case->getBackingValue(),
                    docComment: $case->getDocComment() ?: null,
                );
            });
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     */
    private function getAttribute(ReflectionEnum $reflection): ?GenerateEnum
    {
        /** @var array<int, ReflectionAttribute<GenerateEnum>> $attrs */
        $attrs = $reflection->getAttributes(GenerateEnum::class);

        if ($attrs === []) {
            return null;
        }

        /** @var GenerateEnum $instance */
        $instance = $attrs[0]->newInstance();

        return $instance;
    }
}
