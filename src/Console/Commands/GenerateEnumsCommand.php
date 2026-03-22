<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\Support\EnumBackendStateBuilder;
use GaiaTools\TypeBridge\Support\EnumDiffing;
use GaiaTools\TypeBridge\Support\EnumFileParser;
use GaiaTools\TypeBridge\Support\EnumGroupFileParser;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use ReflectionEnum;
use UnitEnum;

class GenerateEnumsCommand extends Command
{
    protected $signature = 'type-bridge:enums {--format=} {--check} {--dirty}';

    protected $description = 'Generate frontend enum files from PHP enums';

    public function handle(): int
    {
        $enumConfig = EnumDiscoveryConfig::fromConfig();
        $generatorConfig = GeneratorConfig::fromConfig();
        $format = $this->resolveFormat($generatorConfig);
        $discoverer = $this->buildDiscoverer($enumConfig);
        $transformer = $this->buildTransformer($generatorConfig);
        $generator = $this->buildGenerator($discoverer, $transformer, $format);
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
        $backend = $this->buildBackend($discoverer, $transformer);
        $diffs = $this->computeDiffs($backend, $extension);
        if ($diffs === []) {
            $this->components->info('No dirty enums found.');

            return self::SUCCESS;
        }
        $dirtyEnums = $this->resolveDirtyEnums($discoverer->discover(), array_keys($diffs));
        $files = $generator->generateFor($dirtyEnums);
        $this->components->info(sprintf('Generated %d enum file(s)', $files->count()));

        return self::SUCCESS;
    }

    private function checkEnums(EnumDiscoverer $discoverer, EnumTransformer $transformer, string $format): int
    {
        $this->components->info('Checking enums against previously generated frontend files...');
        $extension = $this->resolveExtension($format);
        $backend = $this->buildBackend($discoverer, $transformer);
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

    private function resolveFormat(GeneratorConfig $generatorConfig): string
    {
        $optFormat = $this->option('format');

        return is_string($optFormat) && $optFormat !== '' ? $optFormat : (string) $generatorConfig->outputFormat;
    }

    private function buildDiscoverer(EnumDiscoveryConfig $enumConfig): EnumDiscoverer
    {
        return new EnumDiscoverer($enumConfig, new EnumTokenParser);
    }

    private function buildTransformer(GeneratorConfig $generatorConfig): EnumTransformer
    {
        return new EnumTransformer($generatorConfig);
    }

    private function buildGenerator(
        EnumDiscoverer $discoverer,
        EnumTransformer $transformer,
        string $format
    ): EnumGenerator {
        $formatter = $format === 'js' ? new JsEnumFormatter : new TsEnumFormatter;

        return new EnumGenerator($discoverer, $transformer, $formatter, new GeneratedFileWriter);
    }

    /**
     * @return array<string,array{fqcn:string,path:string,cases:array<string,string>,groups:array<string,array{kind:string,entries:array<string,string>}>}>
     */
    private function buildBackend(EnumDiscoverer $discoverer, EnumTransformer $transformer): array
    {
        return (new EnumBackendStateBuilder)->build($discoverer->discover(), $transformer);
    }

    /**
     * @param  Collection<int, ReflectionEnum<UnitEnum>>  $discovered
     * @param  array<int,string>  $dirtyNames
     * @return Collection<int, ReflectionEnum<UnitEnum>>
     */
    private function resolveDirtyEnums(Collection $discovered, array $dirtyNames): Collection
    {
        return $discovered->filter(
            fn (ReflectionEnum $reflection) => in_array($reflection->getShortName(), $dirtyNames, true)
        );
    }

    /**
     * Compute added/removed diffs for each enum based on keys, values, and groups.
     *
     * @param  array<string,array{fqcn:string,path:string,cases:array<string,string>,groups:array<string,array{kind:string,entries:array<string,string>}>}>  $backend
     * @return array<string,array{fqcn:string,file:string,added:array<int,string>,removed:array<int,string>}>
     */
    private function computeDiffs(array $backend, string $extension): array
    {
        $diffs = [];
        foreach ($backend as $enumName => $info) {
            $filePath = rtrim($info['path'], '/').'/'.$enumName.'.'.$extension;
            $frontendCases = $this->loadFrontendCases($filePath, (string) $enumName);
            $frontendGroups = $this->loadFrontendGroups($filePath, (string) $enumName);
            $caseDiff = EnumDiffing::diffEntries($info['cases'], $frontendCases, '');
            $groupDiff = EnumDiffing::diffGroups($info['groups'], $frontendGroups);
            $added = array_merge($caseDiff['added'], $groupDiff['added']);
            $removed = array_merge($caseDiff['removed'], $groupDiff['removed']);
            if ($added !== [] || $removed !== []) {
                $diffs[$enumName] = ['fqcn' => $info['fqcn'], 'file' => $filePath, 'added' => $added, 'removed' => $removed];
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
     * @return array<string,array{kind:string,entries:array<string,string>}>
     */
    private function loadFrontendGroups(string $filePath, string $enumName): array
    {
        if (! is_file($filePath)) {
            return [];
        }

        return EnumGroupFileParser::parseFile($filePath, $enumName);
    }

    /**
     * Print diffs as a table and the hint to regenerate.
     *
     * @param  array<string,array{fqcn:string,file:string,added:array<int,string>,removed:array<int,string>}>  $diffs
     */
    private function reportDiffs(array $diffs, string $format): void
    {
        $this->components->error('❌ Enums differ from generated frontend files:');
        $decorated = $this->isDecorated();
        foreach ($diffs as $d) {
            $this->line('');
            $this->line($d['fqcn']);
            $removed = $this->keyedByCaseName($d['removed']);
            $added = $this->keyedByCaseName($d['added']);
            $rows = [];
            foreach (array_unique(array_merge(array_keys($removed), array_keys($added))) as $key) {
                $rows[] = [
                    isset($removed[$key]) ? $this->formatDiffCell($removed[$key], 'red', $decorated) : '-',
                    isset($added[$key]) ? $this->formatDiffCell($added[$key], 'green', $decorated) : '-',
                ];
            }
            $this->table(['Removed', 'Added'], $rows);
            $unmatchedRemoved = array_diff_key($removed, $added);
            $unmatchedAdded = array_diff_key($added, $removed);
            if ($unmatchedRemoved !== [] && $unmatchedAdded !== []) {
                $this->components->warn('<fg=yellow>⚠</> Unmatched removals and additions detected — if any are renames, update references manually before regenerating.');
            }
        }
        $this->components->info('Run `php artisan type-bridge:enums --dirty'.($format ? ' --format='.$format : '').'` to regenerate.');
    }

    /**
     * @param  array<int,string>  $entries
     * @return array<string,string>
     */
    private function keyedByCaseName(array $entries): array
    {
        $result = [];
        foreach ($entries as $entry) {
            $key = trim(explode(':', $entry, 2)[0]);
            $result[$key] = $entry;
        }

        return $result;
    }

    private function formatDiffCell(string $value, string $color, bool $decorated): string
    {
        if ($value === '') {
            return '-';
        }

        return $decorated ? "<fg=$color>$value</>" : $value;
    }

    private function isDecorated(): bool
    {
        /** @var mixed $out */
        $out = $this->output;

        /** @phpstan-ignore-next-line method_exists on OutputStyle is always true per phpdoc */
        return is_object($out) && method_exists($out, 'isDecorated') && $out->isDecorated();
    }
}
