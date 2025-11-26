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
     * Build the current backend snapshot of enums: [ name => { path, cases[] } ]
     *
     * @return array<string,array{path:string,cases:array<int,string>}>
     */
    private function buildBackendState(EnumDiscoverer $discoverer, EnumTransformer $transformer): array
    {
        $discovered = $discoverer->discover();

        $backend = $discovered->mapWithKeys(function ($reflection) use ($transformer) {
            $transformed = $transformer->transform($reflection);
            $caseNames = $transformed->cases->map(fn ($c) => $c->name)->values()->all();
            return [$transformed->name => [
                'path' => $transformed->outputPath,
                'cases' => $caseNames,
            ]];
        });

        // Convert Illuminate Collection to plain array for easier downstream handling
        return $backend->toArray();
    }

    /**
     * Compute added/removed case diffs for each enum.
     *
     * @param  array<string,array{path:string,cases:array<int,string>}>  $backend
     * @return array<string,array{file:string,added:array<int,string>,removed:array<int,string>}>
     */
    private function computeDiffs(array $backend, string $extension): array
    {
        $diffs = [];

        foreach ($backend as $enumName => $info) {
            $filePath = rtrim($info['path'], '/').'/'.$enumName.'.'.$extension;
            $frontendCases = $this->loadFrontendCases($filePath, (string) $enumName);

            $backendSet = collect($info['cases']);
            $frontendSet = collect($frontendCases);

            $added = $backendSet->diff($frontendSet)->values()->all();
            $removed = $frontendSet->diff($backendSet)->values()->all();

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
     * Load and parse the previously generated enum file, returning its case keys.
     * Returns an empty array when the file doesn't exist or doesn't match the enum name.
     *
     * @return array<int,string>
     */
    private function loadFrontendCases(string $filePath, string $enumName): array
    {
        if (! is_file($filePath)) {
            return [];
        }

        $parsed = \GaiaTools\TypeBridge\Support\EnumFileParser::parseFile($filePath);
        if ($parsed !== null && strcasecmp($parsed['name'], $enumName) === 0) {
            return $parsed['cases'];
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
            $this->line(sprintf('%s (%s)', $name, $d['file']));
            $decorated = method_exists($this->output, 'isDecorated') ? $this->output->isDecorated() : false;
            foreach ($d['added'] as $a) {
                if ($decorated) {
                    $this->line(sprintf('  <fg=green>+ %s</>', $a));
                } else {
                    $this->line(sprintf('  + %s', $a));
                }
            }
            foreach ($d['removed'] as $r) {
                if ($decorated) {
                    $this->line(sprintf('  <fg=red>- %s</>', $r));
                } else {
                    $this->line(sprintf('  - %s', $r));
                }
            }
        }

        $this->line('');
        $this->components->info('Run `php artisan type-bridge:enums'.($format ? ' --format='.$format : '').'` to regenerate.');
    }
}
