<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Transformers;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\Support\EnumGroupExtractor;
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
    private readonly EnumGroupExtractor $groupExtractor;

    public function __construct(
        private readonly GeneratorConfig $config,
        ?EnumGroupExtractor $groupExtractor = null,
    ) {
        $this->groupExtractor = $groupExtractor ?? new EnumGroupExtractor;
    }

    public function transform(mixed $source): TransformedEnum
    {
        assert($source instanceof ReflectionEnum);

        $attribute = $this->getAttribute($source);
        $requiresComments = ($attribute && $attribute->requiresComments);

        $cases = $this->extractCases($source, $requiresComments);
        $groups = $this->groupExtractor->extract($source, $attribute ? $attribute->includeMethods : []);

        $outputPath = resource_path($this->config->enumOutputPath);

        return new TransformedEnum(
            name: $source->getShortName(),
            cases: $cases,
            namespace: $source->getNamespaceName(),
            outputPath: $outputPath,
            groups: $groups,
        );
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     * @return Collection<int, EnumCase>
     */
    private function extractCases(ReflectionEnum $reflection, bool $requiresComments): Collection
    {
        return collect($reflection->getCases())
            ->filter($this->isBackedCase())
            ->map(function (ReflectionEnumUnitCase $case) use ($reflection, $requiresComments): EnumCase {
                assert($case instanceof ReflectionEnumBackedCase);

                return $this->buildCase($case, $reflection, $requiresComments);
            });
    }

    /**
     * @return callable(ReflectionEnumBackedCase|ReflectionEnumUnitCase): bool
     */
    private function isBackedCase(): callable
    {
        return static fn (ReflectionEnumBackedCase|ReflectionEnumUnitCase $case) => $case instanceof ReflectionEnumBackedCase;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     */
    private function buildCase(
        ReflectionEnumBackedCase $case,
        ReflectionEnum $reflection,
        bool $requiresComments
    ): EnumCase {
        $this->guardCaseComment($case, $reflection, $requiresComments);

        return new EnumCase(
            name: $case->getName(),
            value: $case->getBackingValue(),
            docComment: $case->getDocComment() ?: null,
        );
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     */
    private function guardCaseComment(
        ReflectionEnumBackedCase $case,
        ReflectionEnum $reflection,
        bool $requiresComments
    ): void {
        if ($requiresComments && ! $case->getDocComment()) {
            throw new \RuntimeException(
                "Enum {$reflection->getName()} case {$case->getName()} is missing a doc comment."
            );
        }
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
