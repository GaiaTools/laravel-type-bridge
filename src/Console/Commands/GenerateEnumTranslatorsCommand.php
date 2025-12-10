<?php

// src/Console/Commands/GenerateEnumTranslatorsCommand.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Discoverers\EnumTranslatorDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumTranslatorGenerator;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\JsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\TsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Transformers\EnumTranslatorTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;

class GenerateEnumTranslatorsCommand extends Command
{
    protected $signature = 'type-bridge:enum-translators {--format=}';

    protected $description = 'Generate enum translator composables from PHP enums';

    public function handle(): int
    {
        $translatorConfig = EnumTranslatorDiscoveryConfig::fromConfig();

        if (!$translatorConfig->enabled) {
            $this->components->info('Enum translator generation is disabled.');
            return self::SUCCESS;
        }

        $generatorConfig = GeneratorConfig::fromConfig();

        $optFormat = $this->option('format');
        $format = is_string($optFormat) && $optFormat !== ''
            ? $optFormat
            : (string) $generatorConfig->outputFormat;

        $i18nLibrary = config('type-bridge.i18n.library', 'vue-i18n');

        $discoverer = new EnumTranslatorDiscoverer($translatorConfig, new EnumTokenParser());
        $transformer = new EnumTranslatorTransformer($translatorConfig);
        $formatter = $format === 'js'
            ? new JsEnumTranslatorFormatter($i18nLibrary)
            : new TsEnumTranslatorFormatter($i18nLibrary);
        $writer = new GeneratedFileWriter;

        $generator = new EnumTranslatorGenerator($discoverer, $transformer, $formatter, $writer);

        $this->components->info('Generating enum translator composables...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d enum translator file(s)', $files->count()));

        return self::SUCCESS;
    }
}
