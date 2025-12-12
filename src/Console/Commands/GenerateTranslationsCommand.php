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

class GenerateTranslationsCommand extends Command
{
    protected $signature = 'type-bridge:translations {locale} {--flat} {--format=}';

    protected $description = 'Generate frontend translation files from Laravel lang files';

    public function handle(TranslationSyntaxAdapter $syntaxAdapter): int
    {
        $locale = $this->argument('locale');
        $flat = (bool) $this->option('flat');

        $generatorConfig = GeneratorConfig::fromConfig();
        $format = $this->option('format') ?? $generatorConfig->outputFormat;

        $formatter = match ($format) {
            'json' => new JsonTranslationFormatter,
            'js' => new JsTranslationFormatter,
            default => new TsTranslationFormatter,
        };

        // Wrap user input in a discoverer
        $discoverer = new SimpleDiscoverer(['locale' => $locale, 'flat' => $flat]);
        // Pass discovery config built from type-bridge config so behavior is fully config-driven
        $discoveryConfig = TranslationDiscoveryConfig::fromConfig();
        $transformer = new TranslationTransformer($generatorConfig, $syntaxAdapter, $discoveryConfig);
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $this->components->info('Generating translations...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d translation file(s)', $files->count()));

        return self::SUCCESS;
    }
}
