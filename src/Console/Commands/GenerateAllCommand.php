<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\TranslationSyntaxAdapter;
use GaiaTools\TypeBridge\Discoverers\EnumDiscoverer;
use GaiaTools\TypeBridge\Discoverers\EnumTranslatorDiscoverer;
use GaiaTools\TypeBridge\Discoverers\SimpleDiscoverer;
use GaiaTools\TypeBridge\Generators\EnumGenerator;
use GaiaTools\TypeBridge\Generators\EnumTranslatorGenerator;
use GaiaTools\TypeBridge\Generators\TranslationGenerator;
use GaiaTools\TypeBridge\OutputFormatters\Enum\JsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Enum\TsEnumFormatter;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\JsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\OutputFormatters\EnumTranslator\TsEnumTranslatorFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsonTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\TsTranslationFormatter;
use GaiaTools\TypeBridge\Support\EnumTokenParser;
use GaiaTools\TypeBridge\Support\TranslationIndex;
use GaiaTools\TypeBridge\Transformers\EnumTransformer;
use GaiaTools\TypeBridge\Transformers\EnumTranslatorTransformer;
use GaiaTools\TypeBridge\Transformers\TranslationTransformer;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionEnum;
use UnitEnum;

final class GenerateAllCommand extends Command
{
    protected $signature = 'type-bridge:generate
        {locale? : Locale to generate translations for (defaults to all discovered locales)}
        {--flat : Generate flat translation keys instead of nested objects}
        {--format= : Output format for enums and enum translators (ts|js)}
        {--translations-format= : Output format for translations (ts|js|json)}
        {--enums=* : Limit generation to specific enums by short name or FQCN}';

    protected $description = 'Generate enums, translations, and enum translator helpers in one command';

    public function handle(TranslationSyntaxAdapter $syntaxAdapter): int
    {
        $generatorConfig = GeneratorConfig::fromConfig();

        $format = $this->resolveEnumFormat($generatorConfig);
        $translationsFormat = $this->resolveTranslationsFormat($generatorConfig);
        $status = self::SUCCESS;

        if ($format === null || $translationsFormat === null) {
            $status = self::FAILURE;
        } else {
            $enumFilter = $this->parseEnumFilter();
            $enumDiscoverer = new EnumDiscoverer(EnumDiscoveryConfig::fromConfig(), new EnumTokenParser);
            $discoveredEnums = $enumDiscoverer->discover();

            $missing = [];
            $filteredEnums = $this->filterEnums($discoveredEnums, $enumFilter, $missing);

            if ($enumFilter !== [] && $missing !== []) {
                $this->components->warn(sprintf(
                    'Some enums were not found and will be skipped: %s',
                    implode(', ', $missing)
                ));
            }

            if ($enumFilter !== [] && $filteredEnums->isEmpty()) {
                $this->components->error('No matching enums were found to generate.');
                $status = self::FAILURE;
            } else {
                $this->generateEnums($filteredEnums, $format);
                $this->generateTranslations($syntaxAdapter, $translationsFormat);
                $this->generateEnumTranslators($filteredEnums, $format);
            }
        }

        return $status;
    }

    private function resolveEnumFormat(GeneratorConfig $generatorConfig): ?string
    {
        $optFormat = $this->option('format');
        $format = is_string($optFormat) && $optFormat !== ''
            ? $optFormat
            : (string) $generatorConfig->outputFormat;

        if (! in_array($format, ['ts', 'js'], true)) {
            $this->components->error(sprintf(
                'Invalid enum format "%s". Supported: ts, js.',
                $format
            ));

            return null;
        }

        return $format;
    }

    private function resolveTranslationsFormat(GeneratorConfig $generatorConfig): ?string
    {
        $optFormat = $this->option('translations-format');
        $format = is_string($optFormat) && $optFormat !== ''
            ? $optFormat
            : (string) $generatorConfig->outputFormat;

        if (! in_array($format, ['ts', 'js', 'json'], true)) {
            $this->components->error(sprintf(
                'Invalid translations format "%s". Supported: ts, js, json.',
                $format
            ));

            return null;
        }

        return $format;
    }

    /**
     * @return array<int,string>
     */
    private function parseEnumFilter(): array
    {
        $raw = $this->option('enums');
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $values = [];
        foreach ($raw as $value) {
            if (! is_string($value)) {
                continue;
            }

            $parts = preg_split('/[,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
            if ($parts === false) {
                continue;
            }

            foreach ($parts as $part) {
                $values[] = $part;
            }
        }

        $values = array_values(array_unique($values));

        /** @var array<int,string> $values */
        return $values;
    }

    /**
     * @param  Collection<int, ReflectionEnum<UnitEnum>>  $discovered
     * @param  array<int,string>  $filters
     * @param  array<int,string>  $missing
     * @return Collection<int, ReflectionEnum<UnitEnum>>
     */
    private function filterEnums(Collection $discovered, array $filters, array &$missing): Collection
    {
        if ($filters === []) {
            $missing = [];

            return $discovered->values();
        }

        $filtersLower = array_map(static fn (string $v): string => mb_strtolower($v), $filters);

        $filtered = $discovered
            ->filter(function (ReflectionEnum $ref) use ($filtersLower): bool {
                $short = mb_strtolower($ref->getShortName());
                $fqcn = mb_strtolower($ref->getName());

                return in_array($short, $filtersLower, true)
                    || in_array($fqcn, $filtersLower, true);
            })
            ->values();

        $matched = [];
        foreach ($filtered as $ref) {
            $matched[] = mb_strtolower($ref->getShortName());
            $matched[] = mb_strtolower($ref->getName());
        }

        $missing = [];
        foreach ($filters as $value) {
            if (! in_array(mb_strtolower($value), $matched, true)) {
                $missing[] = $value;
            }
        }

        $missing = array_values(array_unique($missing));

        return $filtered;
    }

    /**
     * @param  Collection<int, ReflectionEnum<UnitEnum>>  $enums
     */
    private function generateEnums(Collection $enums, string $format): void
    {
        $generatorConfig = GeneratorConfig::fromConfig();
        $transformer = new EnumTransformer($generatorConfig);
        $formatter = $format === 'js' ? new JsEnumFormatter : new TsEnumFormatter;
        $writer = new GeneratedFileWriter;

        $generator = new EnumGenerator($this->buildEnumDiscoverer(), $transformer, $formatter, $writer);

        $this->components->info('Generating enums...');
        $files = $generator->generateFor($enums);
        $this->components->info(sprintf('Generated %d enum file(s)', $files->count()));
    }

    private function buildEnumDiscoverer(): EnumDiscoverer
    {
        return new EnumDiscoverer(EnumDiscoveryConfig::fromConfig(), new EnumTokenParser);
    }

    private function generateTranslations(TranslationSyntaxAdapter $syntaxAdapter, string $format): void
    {
        $localeArg = $this->argument('locale');
        $locale = is_string($localeArg) ? $localeArg : null;
        $flat = (bool) $this->option('flat');

        $generatorConfig = GeneratorConfig::fromConfig();
        $discoveryConfig = TranslationDiscoveryConfig::fromConfig();
        $items = $this->buildTranslationDiscoveryItems($locale, $flat, $discoveryConfig);

        $formatter = $this->makeTranslationFormatter($format);
        $discoverer = new SimpleDiscoverer($items);
        $transformer = new TranslationTransformer($generatorConfig, $syntaxAdapter, $discoveryConfig);
        $writer = new GeneratedFileWriter;

        $generator = new TranslationGenerator($discoverer, $transformer, $formatter, $writer);

        $this->components->info('Generating translations...');
        $files = $generator->generate();
        $this->components->info(sprintf('Generated %d translation file(s)', $files->count()));
    }

    /**
     * @return array<string,mixed>|list<array{locale:string,flat:bool}>
     */
    private function buildTranslationDiscoveryItems(
        ?string $locale,
        bool $flat,
        TranslationDiscoveryConfig $discoveryConfig
    ): array {
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

    private function makeTranslationFormatter(
        string $format
    ): JsonTranslationFormatter|JsTranslationFormatter|TsTranslationFormatter {
        return match ($format) {
            'json' => new JsonTranslationFormatter,
            'js' => new JsTranslationFormatter,
            default => new TsTranslationFormatter,
        };
    }

    /**
     * @param  Collection<int, ReflectionEnum<UnitEnum>>  $enums
     */
    private function generateEnumTranslators(Collection $enums, string $format): void
    {
        $translatorConfig = EnumTranslatorDiscoveryConfig::fromConfig();
        $i18nLibrary = config()->string('type-bridge.i18n_library', 'vue-i18n');
        $translationIndex = new TranslationIndex;

        /** @var list<class-string<UnitEnum>> $feEnums */
        $feEnums = $enums
            ->map(fn (ReflectionEnum $ref) => $ref->getName())
            ->values()
            ->all();

        $discoverer = new EnumTranslatorDiscoverer(
            $translatorConfig,
            new EnumTokenParser,
            $feEnums,
            $translationIndex
        );
        $transformer = new EnumTranslatorTransformer($translatorConfig);
        $formatter = $format === 'js'
            ? new JsEnumTranslatorFormatter($i18nLibrary)
            : new TsEnumTranslatorFormatter($i18nLibrary);
        $writer = new GeneratedFileWriter;

        $generator = new EnumTranslatorGenerator($discoverer, $transformer, $formatter, $writer);

        $this->components->info('Generating enum translator composables...');
        $files = $generator->generate();
        $this->components->info(sprintf('Generated %d enum translator file(s)', $files->count()));
    }
}
