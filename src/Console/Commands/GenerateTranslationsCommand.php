<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use GaiaTools\TypeBridge\Discoverers\SimpleDiscoverer;
use GaiaTools\TypeBridge\Generators\TranslationGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsonTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\TsTranslationFormatter;
use GaiaTools\TypeBridge\Transformers\TranslationTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTranslationsCommand extends Command
{
    protected $signature = 'type-bridge:translations {locale?} {--flat} {--format=}';

    protected $description = 'Generate frontend translation files from Laravel lang files';

    public function handle(TranslationSyntaxAdapter $syntaxAdapter): int
    {
        $localeArg = $this->argument('locale');
        $locale = is_string($localeArg) ? $localeArg : null;
        $flat = (bool) $this->option('flat');

        $generatorConfig = GeneratorConfig::fromConfig();
        $formatOpt = $this->option('format');
        $format = is_string($formatOpt) ? $formatOpt : $generatorConfig->outputFormat;
        $formatter = $this->makeFormatter($format);

        // Build discovery config and items
        $discoveryConfig = $this->buildDiscoveryConfig();
        $items = $this->buildDiscoveryItems($locale, $flat, $discoveryConfig);

        // Create generator pipeline
        $generator = $this->createGenerator($items, $generatorConfig, $syntaxAdapter, $discoveryConfig, $formatter);

        $this->components->info('Generating translations...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d translation file(s)', $files->count()));

        return self::SUCCESS;
    }

    private function makeFormatter(string $format): JsonTranslationFormatter|JsTranslationFormatter|TsTranslationFormatter
    {
        return match ($format) {
            'json' => new JsonTranslationFormatter,
            'js' => new JsTranslationFormatter,
            default => new TsTranslationFormatter,
        };
    }

    private function buildDiscoveryConfig(): TranslationDiscoveryConfig
    {
        return TranslationDiscoveryConfig::fromConfig();
    }

    /**
     * @return array<string,mixed>|list<array{locale:string,flat:bool}>
     */
    private function buildDiscoveryItems(?string $locale, bool $flat, TranslationDiscoveryConfig $discoveryConfig): array
    {
        if (filled($locale)) {
            return ['locale' => $locale, 'flat' => $flat];
        }

        $locales = $this->discoverLocales($discoveryConfig);

        return array_map(static fn (string $loc): array => ['locale' => $loc, 'flat' => $flat], $locales);
    }

    /**
     * @return list<string>
     */
    private function discoverLocales(TranslationDiscoveryConfig $discoveryConfig): array
    {
        $locales = [];
        foreach ($discoveryConfig->langPaths as $root) {
            if (! File::isDirectory($root)) {
                continue;
            }
            foreach (File::directories($root) as $dir) {
                $locales[] = basename($dir);
            }
        }

        $locales = array_values(array_unique($locales));
        sort($locales);

        /** @var list<string> $locales */
        return $locales;
    }

    private function createGenerator(
        mixed $items,
        GeneratorConfig $generatorConfig,
        TranslationSyntaxAdapter $syntaxAdapter,
        TranslationDiscoveryConfig $discoveryConfig,
        JsonTranslationFormatter|JsTranslationFormatter|TsTranslationFormatter $formatter,
    ): TranslationGenerator {
        $discoverer = new SimpleDiscoverer($items);
        $transformer = new TranslationTransformer($generatorConfig, $syntaxAdapter, $discoveryConfig);
        $writer = new GeneratedFileWriter;

        return new TranslationGenerator($discoverer, $transformer, $formatter, $writer);
    }
}
