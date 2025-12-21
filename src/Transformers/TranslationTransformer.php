<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Transformers;

use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use GaiaTools\TypeBridge\Support\TranslationResolver;
use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use SplFileInfo;

final class TranslationTransformer implements Transformer
{
    use TranslationResolver;
    public function __construct(
        private readonly GeneratorConfig $config,
        private readonly TranslationSyntaxAdapter $syntaxAdapter,
        private readonly ?TranslationDiscoveryConfig $discoveryConfig = null,
    ) {}

    /**
     * @param  array{locale: string, flat: bool}  $source
     */
    public function transform(mixed $source): TransformedTranslation
    {
        $locale = $source['locale'];
        $flat = $source['flat'];

        $data = $this->readAndMerge($locale);

        $data = $this->normalizeClassLikeKeys($data);

        $data = $this->syntaxAdapter->transform($data);

        $data = $flat ? $this->dotFlatten($data) : $data;

        $outputPath = resource_path($this->config->translationOutputPath);

        return new TransformedTranslation(
            locale: $locale,
            data: $data,
            isFlat: $flat,
            outputPath: $outputPath,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readAndMerge(string $locale): array
    {
        $roots = $this->getLangRoots();

        $anyFound = false;
        $final = [];

        foreach ($roots as $root) {
            $langDir = $this->buildLocaleDir($root, $locale);
            if (! File::isDirectory($langDir)) {
                continue;
            }

            $anyFound = true;
            $current = $this->loadLocaleDir($langDir);

            // Merge this root into final; later roots override earlier ones
            $final = array_replace_recursive($final, $current);
        }

        if (! $anyFound) {
            throw new InvalidArgumentException('Locale directory not found for locale: '.$locale);
        }

        return $final;
    }

    /**
     * @return list<string>
     */
    private function getLangRoots(): array
    {
        $paths = ($this->discoveryConfig ?? TranslationDiscoveryConfig::fromConfig())->langPaths;

        // Ensure result is a proper list<string> (0..n consecutive integer keys)
        /** @var list<string> */
        return array_values($paths);
    }

    
}
