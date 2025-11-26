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
    protected $signature = 'type-bridge:enums {--format=} {--check}';

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

        if ($this->option('check')) {
            return $this->checkEnums($discoverer, $transformer, $format);
        }

        $this->components->info('Generating enums...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d enum file(s)', $files->count()));

        return self::SUCCESS;
    }

    private function checkEnums(EnumDiscoverer $discoverer, EnumTransformer $transformer, string $format): int
    {
        $this->components->info('Checking enums against previously generated frontend files...');

        $extension = $this->resolveExtension($format);
        $backend = $this->buildBackendState($discoverer, $transformer);
        $diffs = $this->computeDiffs($backend, $extension);

        if ($diffs === []) {
            $this->components->info('✅ Enums are in sync with generated frontend files.');
            return self::SUCCESS;
        }

        $this->reportDiffs($diffs, $format);

        return self::FAILURE;
    }

    private function resolveExtension(string $format): string
    {
        return $format === 'js' ? 'js' : 'ts';
    }

    /**
     * Build the current backend snapshot of enums: [ name => { path, cases{key=>value} } ]
     *
     * @return array<string,array{path:string,cases:array<string,string>}>
     */
    private function buildBackendState(EnumDiscoverer $discoverer, EnumTransformer $transformer): array
    {
        $discovered = $discoverer->discover();

        $backend = $discovered->mapWithKeys(function ($reflection) use ($transformer) {
            $transformed = $transformer->transform($reflection);
            // Build associative map of case => formatted value as emitted in generated files
            $cases = $transformed->cases
                ->mapWithKeys(function ($c) {
                    $value = $this->formatValue($c->value);
                    return [$c->name => $value];
                })
                ->all();
            return [$transformed->name => [
                'path' => $transformed->outputPath,
                'cases' => $cases,
            ]];
        });

        // Convert Illuminate Collection to plain array for easier downstream handling
        return $backend->toArray();
    }

    /**
     * Compute added/removed diffs for each enum based on keys and values.
     *
     * @param  array<string,array{path:string,cases:array<string,string>}>  $backend
     * @return array<string,array{file:string,added:array<int,string>,removed:array<int,string>}>
     */
    private function computeDiffs(array $backend, string $extension): array
    {
        $diffs = [];

        foreach ($backend as $enumName => $info) {
            $filePath = rtrim($info['path'], '/').'/'.$enumName.'.'.$extension;
            $frontendEntries = $this->loadFrontendCases($filePath, (string) $enumName);

            // $info['cases'] and $frontendEntries are associative: key => valueString
            $backendMap = collect($info['cases']);
            $frontendMap = collect($frontendEntries);

            $backendKeys = $backendMap->keys();
            $frontendKeys = $frontendMap->keys();

            $added = [];
            $removed = [];

            // New keys
            foreach ($backendKeys->diff($frontendKeys) as $k) {
                $added[] = $k.': '.$backendMap->get($k);
            }
            // Removed keys
            foreach ($frontendKeys->diff($backendKeys) as $k) {
                $removed[] = $k.': '.$frontendMap->get($k);
            }
            // Changed values for existing keys
            foreach ($backendKeys->intersect($frontendKeys) as $k) {
                $bVal = (string) $backendMap->get($k);
                $fVal = (string) $frontendMap->get($k);
                if ($bVal !== $fVal) {
                    // Treat as addition of new value and removal of old value
                    $added[] = $k.': '.$bVal;
                    $removed[] = $k.': '.$fVal;
                }
            }

            if ($added !== [] || $removed !== []) {
                $diffs[$enumName] = [
                    'file' => $filePath,
                    'added' => $added,
                    'removed' => $removed,
                ];
            }
        }

        return $diffs;
    }

    /**
     * Load and parse the previously generated enum file, returning its case=>value map.
     * Returns an empty array when the file doesn't exist or doesn't match the enum name.
     *
     * @return array<string,string>
     */
    private function loadFrontendCases(string $filePath, string $enumName): array
    {
        if (! is_file($filePath)) {
            return [];
        }

        $parsed = \GaiaTools\TypeBridge\Support\EnumFileParser::parseFile($filePath);
        if ($parsed !== null && strcasecmp($parsed['name'], $enumName) === 0) {
            // Return associative map of key => value string for comparison
            return isset($parsed['entries']) && is_array($parsed['entries']) ? $parsed['entries'] : [];
        }

        return [];
    }

    /**
     * Print diffs and the hint to regenerate.
     *
     * @param  array<string,array{file:string,added:array<int,string>,removed:array<int,string>}>  $diffs
     */
    private function reportDiffs(array $diffs, string $format): void
    {
        $this->components->error('❌ Enums differ from generated frontend files:');

        foreach ($diffs as $name => $d) {
            $this->line('');
            // Keep header line unstyled for predictable CI/test output
            $this->line(sprintf('%s (%s)', $name, $d['file']));

            $this->reportAddedLines($d['added']);
            $this->reportRemovedLines($d['removed']);
        }

        $this->line('');
        $this->components->info('Run `php artisan type-bridge:enums'.($format ? ' --format='.$format : '').'` to regenerate.');
    }

    /**
     * Print all added lines (green + ... when decorated; plain otherwise).
     *
     * @param  array<int,string>  $lines
     */
    private function reportAddedLines(array $lines): void
    {
        foreach ($lines as $text) {
            $this->writeDiffLine('+', $text, 'green');
        }
    }

    /**
     * Print all removed lines (red - ... when decorated; plain otherwise).
     *
     * @param  array<int,string>  $lines
     */
    private function reportRemovedLines(array $lines): void
    {
        foreach ($lines as $text) {
            $this->writeDiffLine('-', $text, 'red');
        }
    }

    /**
     * Shared line writer for diffs with optional color based on console decoration.
     */
    private function writeDiffLine(string $sign, string $text, ?string $color = null): void
    {
        $prefix = sprintf('  %s %%s', $sign);
        if ($this->isDecorated() && $color !== null) {
            $this->line(sprintf("  <fg=%s>%s %s</>", $color, $sign, $text));
            return;
        }

        $this->line(sprintf($prefix, $text));
    }

    /**
     * Determine whether console output supports decoration (colors/styles).
     */
    private function isDecorated(): bool
    {
        return method_exists($this->output, 'isDecorated') ? (bool) $this->output->isDecorated() : false;
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return \GaiaTools\TypeBridge\Support\StringQuoter::quoteJs($value);
        }

        return (string) $value;
    }
}
