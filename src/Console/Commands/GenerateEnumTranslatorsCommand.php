<?php

// src/Console/Commands/GenerateEnumTranslatorsCommand.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Discoverers\EnumTranslatorDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumTranslatorGenerator;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\JsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\TsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Support\TranslationIndex;
use GaiaTools\TypeBridge\Transformers\EnumTranslatorTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;
use ReflectionEnum;
use UnitEnum;

class GenerateEnumTranslatorsCommand extends Command
{
    private const CHECK = '✅';
    private const CROSS = '❌';

    protected $signature = 'type-bridge:enum-translators {--format=} {--dry : Show what would be generated and why, without writing files}';

    protected $description = 'Generate enum translator composables from PHP enums';

    public function handle(): int
    {
        $translatorConfig = EnumTranslatorDiscoveryConfig::fromConfig();
        $generatorConfig = GeneratorConfig::fromConfig();

        $format = $this->resolveFormat($generatorConfig);
        $i18nLibrary = $this->resolveI18nLibrary();
        $feEnums = $this->discoverFeEnums();
        $translationIndex = $this->createTranslationIndex();

        if ($this->option('dry')) {
            return $this->runDry($translatorConfig, $feEnums, $translationIndex);
        }

        $discoverer = new EnumTranslatorDiscoverer($translatorConfig, new EnumTokenParser, $feEnums, $translationIndex);
        $transformer = new EnumTranslatorTransformer($translatorConfig);
        $formatter = $this->buildFormatter($format, $i18nLibrary);
        $writer = new GeneratedFileWriter;

        $generator = new EnumTranslatorGenerator($discoverer, $transformer, $formatter, $writer);

        $this->components->info('Generating enum translator composables...');

        $files = $generator->generate();

        $this->components->info(sprintf('Generated %d enum translator file(s)', $files->count()));

        return self::SUCCESS;
    }

    private function resolveFormat(GeneratorConfig $generatorConfig): string
    {
        $optFormat = $this->option('format');

        return is_string($optFormat) && $optFormat !== ''
            ? $optFormat
            : (string) $generatorConfig->outputFormat;
    }

    private function resolveI18nLibrary(): string
    {
        return config()->string('type-bridge.i18n_library', 'vue-i18n');
    }

    /**
     * @return list<class-string<UnitEnum>>
     */
    private function discoverFeEnums(): array
    {
        $feEnumDiscoverer = new EnumDiscoverer(EnumDiscoveryConfig::fromConfig(), new EnumTokenParser());

        /** @var list<class-string<UnitEnum>> $names */
        $names = $feEnumDiscoverer->discover()
            ->map(fn (ReflectionEnum $r) => $r->getName())
            ->all();

        return $names;
    }

    private function createTranslationIndex(): TranslationIndex
    {
        return new TranslationIndex();
    }

    /**
     * Execute the dry-run flow: discover unfiltered candidates and print checks.
     *
     * @param  array<int, string>  $feEnums
     */
    private function runDry(
        EnumTranslatorDiscoveryConfig $translatorConfig,
        array $feEnums,
        TranslationIndex $translationIndex
    ): int {
        $candidates = $this->discoverDryCandidates($translatorConfig);

        $headers = ['Enum', 'Prefix', 'In FE generation set', 'Has translations'];
        $rows = [];
        $stats = [
            'checked' => 0,
            'eligible' => 0,
            'notFrontendGeneratedEnum' => 0,
            'noTrans' => 0,
        ];

        foreach ($candidates as $item) {
            $evaluation = $this->evaluateDryCandidate($item, $feEnums, $translationIndex);

            $rows[] = [
                $evaluation['enumFqcn'],
                $evaluation['prefix'],
                $evaluation['isFrontendGeneratedEnum'] ? self::CHECK : self::CROSS,
                $evaluation['hasTrans'] ? self::CHECK : self::CROSS,
            ];

            $this->accumulateDryStats($evaluation, $stats);
        }

        $this->renderDryTable($headers, $rows);
        $this->renderDrySummary($stats);

        return self::SUCCESS;
    }

    /**
     * Discover unfiltered candidates for dry run.
     */
    private function discoverDryCandidates(EnumTranslatorDiscoveryConfig $translatorConfig)
    {
        $dryDiscoverer = new EnumTranslatorDiscoverer($translatorConfig, new EnumTokenParser());

        return $dryDiscoverer->discover();
    }

    /**
     * @param  array{reflection: ReflectionEnum, translationKey: string}  $item
     * @param  array<int,string>  $feEnums
     * @return array{enumFqcn:string,prefix:string,isFrontendGeneratedEnum:bool,hasTrans:bool}
     */
    private function evaluateDryCandidate(array $item, array $feEnums, TranslationIndex $translationIndex): array
    {
        /** @var ReflectionEnum $ref */
        $ref = $item['reflection'];
        /** @var string $prefix */
        $prefix = $item['translationKey'];

        $isFrontendGeneratedEnum = in_array($ref->getName(), $feEnums, true);
        $hasTrans = $translationIndex->hasAnyForEnum($prefix, $ref);

        return [
            'enumFqcn' => $ref->getName(),
            'prefix' => $prefix,
            'isFrontendGeneratedEnum' => $isFrontendGeneratedEnum,
            'hasTrans' => $hasTrans,
        ];
    }

    /**
     * Update stats for the dry run summary.
     *
     * @param  array{isFrontendGeneratedEnum:bool,hasTrans:bool}  $evaluation
     * @param  array{checked:int,eligible:int,notFrontendGeneratedEnum:int,noTrans:int}  $stats
     */
    private function accumulateDryStats(array $evaluation, array &$stats): void
    {
        $stats['checked']++;
        if ($evaluation['isFrontendGeneratedEnum'] && $evaluation['hasTrans']) {
            $stats['eligible']++;
        }
        if (! $evaluation['isFrontendGeneratedEnum']) {
            $stats['notFrontendGeneratedEnum']++;
        }
        if (! $evaluation['hasTrans']) {
            $stats['noTrans']++;
        }
    }

    /**
     * Render the dry-run results table.
     *
     * @param  array<int,string>  $headers
     * @param  array<int,array<int,string>>  $rows
     */
    private function renderDryTable(array $headers, array $rows): void
    {
        $this->table($headers, $rows);
    }

    /**
     * Render the dry-run summary lines.
     *
     * @param  array{checked:int,eligible:int,notFrontendGeneratedEnum:int,noTrans:int}  $stats
     */
    private function renderDrySummary(array $stats): void
    {
        $this->newLine();
        $this->components->info(sprintf('Checked %d enum(s).', $stats['checked']));
        $this->components->info(sprintf('Eligible translations: %d', $stats['eligible']));
        $this->components->info(sprintf('Not in FE generation set: %d', $stats['notFrontendGeneratedEnum']));
        $this->components->info(sprintf('Ineligible translations: %d', $stats['noTrans']));
    }

    private function buildFormatter(string $format, string $i18nLibrary): JsEnumTranslatorFormatter|TsEnumTranslatorFormatter
    {
        return $format === 'js'
            ? new JsEnumTranslatorFormatter($i18nLibrary)
            : new TsEnumTranslatorFormatter($i18nLibrary);
    }
}
