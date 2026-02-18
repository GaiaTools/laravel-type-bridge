<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Support\EnumFileParser;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;

class GenerateEnumsCommand extends Command
{
    protected $signature = 'type-bridge:enums {--format=} {--check} {--dirty}';

    protected $description = 'Generate frontend enum files from PHP enums';

    public function handle(): int
    {
        $enumConfig = EnumDiscoveryConfig::fromConfig();
        $generatorConfig = GeneratorConfig::fromConfig();

        $optFormat = $this->option('format');
        $format = is_string($optFormat) && $optFormat !== ''
            ? $optFormat
            : (string) $generatorConfig->outputFormat;

        $discoverer = new EnumDiscoverer($enumConfig, new EnumTokenParser);
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = $format === 'js' ? new JsEnumFormatter : new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($discoverer, $transformer, $formatter, $writer);

        if ($this->option('check')) {
            return $this->checkEnums($discoverer, $transformer, $format);
        }

        if ($this->option('dirty')) {
            return $this->generateDirtyEnums($discoverer, $transformer, $generator, $format);
        }

        $this->components->info('Generating enums...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d enum file(s)', $files->count()));

        return self::SUCCESS;
    }

    private function generateDirtyEnums(
        EnumDiscoverer $discoverer,
        EnumTransformer $transformer,
        EnumGenerator $generator,
        string $format,
    ): int {
        $this->components->info('Generating dirty enums...');

        $extension = $this->resolveExtension($format);
        $backend = $this->buildBackendState($discoverer, $transformer);
        $diffs = $this->computeDiffs($backend, $extension);

        if ($diffs === []) {
            $this->components->info('No dirty enums found.');

            return self::SUCCESS;
        }

        $dirtyNames = array_keys($diffs);
        $discovered = $discoverer->discover();

        $dirtyEnums = $discovered->filter(
            fn ($reflection) => in_array($reflection->getShortName(), $dirtyNames, true)
        );

        $files = $generator->generateFor($dirtyEnums);

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
        $result = [];

        foreach ($discoverer->discover() as $reflection) {
            $transformed = $transformer->transform($reflection);

            // Build associative map of case => formatted value as emitted in generated files
            $cases = [];
            foreach ($transformed->cases as $c) {
                $cases[$c->name] = $this->formatValue($c->value);
            }

            /** @var array<string,string> $cases */
            $result[$transformed->name] = [
                'path' => $transformed->outputPath,
                'cases' => $cases,
            ];
        }

        /** @var array<string,array{path:string,cases:array<string,string>}> $result */
        return $result;
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

            /** @var array<string,string> $backendMap */
            $backendMap = $info['cases'];

            $backendKeys = array_keys($backendMap);
            $frontendKeys = array_keys($frontendEntries);

            $added = [];
            $removed = [];

            // New keys
            foreach (array_diff($backendKeys, $frontendKeys) as $k) {
                $added[] = $k.': '.$backendMap[$k];
            }
            // Removed keys
            foreach (array_diff($frontendKeys, $backendKeys) as $k) {
                $removed[] = $k.': '.$frontendEntries[$k];
            }
            // Changed values for existing keys
            foreach (array_intersect($backendKeys, $frontendKeys) as $k) {
                $bVal = $backendMap[$k];
                $fVal = $frontendEntries[$k];
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

        $parsed = EnumFileParser::parseFile($filePath);
        if ($parsed !== null && strcasecmp($parsed['name'], $enumName) === 0) {
            /** @var array<string,string> */
            return $parsed['entries'];
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
            $this->line(sprintf('  <fg=%s>%s %s</>', $color, $sign, $text));

            return;
        }

        $this->line(sprintf($prefix, $text));
    }

    /**
     * Determine whether console output supports decoration (colors/styles).
     */
    private function isDecorated(): bool
    {
        /** @var mixed $out */
        $out = $this->output;

        /** @phpstan-ignore-next-line method_exists on OutputStyle is always true per phpdoc */
        return is_object($out) && method_exists($out, 'isDecorated') ? (bool) $out->isDecorated() : false;
    }

    private function formatValue(string|int $value): string
    {
        if (is_string($value)) {
            return \GaiaTools\TypeBridge\Support\StringQuoter::quoteJs($value);
        }

        return (string) $value;
    }
}
