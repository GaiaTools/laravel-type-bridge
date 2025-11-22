<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;

class GenerateEnumsCommand extends Command
{
    protected $signature = 'type-bridge:enums {--format=}';

    protected $description = 'Generate frontend enum files from PHP enums';

    public function handle(): int
    {
        $enumConfig = EnumDiscoveryConfig::fromConfig();
        $generatorConfig = GeneratorConfig::fromConfig();

        $format = $this->option('format') ?? $generatorConfig->outputFormat;

        $discoverer = new EnumDiscoverer($enumConfig);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = $format === 'js' ? new JsEnumFormatter : new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);

        $this->components->info('Generating enums...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d enum file(s)', $files->count()));

        return self::SUCCESS;
    }
}
