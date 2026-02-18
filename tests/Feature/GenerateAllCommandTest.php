<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

require_once __DIR__.'/../Support/GenerateAllCommandOverrides.php';

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Console\Commands\GenerateAllCommand;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsonTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\JsTranslationFormatter;
use GaiaTools\TypeBridge\OutputFormatters\Translation\TsTranslationFormatter;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestRole;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestStatus;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;

final class GenerateAllCommandTest extends TestCase
{
    #[Test]
    public function it_generates_enums_translations_and_translators(): void
    {
        config([
            'type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums'],
            'type-bridge.enum_translators.translator_output_path' => 'test-output/translators',
        ]);

        $this->artisan('type-bridge:generate')
            ->expectsOutputToContain('Generating enums...')
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generating enum translator composables...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);

        $this->assertDirectoryExists(resource_path('test-output/enums'));
        $this->assertDirectoryExists(resource_path('test-output/translations'));
        $this->assertDirectoryExists(resource_path('test-output/translators'));
    }

    #[Test]
    public function it_limits_generation_to_specific_enums(): void
    {
        config([
            'type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums'],
            'type-bridge.enum_translators.translator_output_path' => 'test-output/translators',
        ]);

        $this->artisan('type-bridge:generate', [
            '--enums' => ['TestStatus'],
        ])
            ->expectsOutputToContain('Generating enums...')
            ->expectsOutputToContain('Generated')
            ->assertExitCode(0);

        $enumOutput = resource_path('test-output/enums');
        $files = File::files($enumOutput);
        $names = array_map(static fn ($file) => $file->getFilename(), $files);

        $this->assertContains('TestStatus.ts', $names);
        $this->assertNotContains('TestRole.ts', $names);

        $translatorOutput = resource_path('test-output/translators');
        $translatorFiles = File::exists($translatorOutput) ? File::files($translatorOutput) : [];
        $translatorNames = array_map(static fn ($file) => $file->getFilename(), $translatorFiles);

        $this->assertContains('useTestStatusTranslator.ts', $translatorNames);
        $this->assertNotContains('useTestPriorityTranslator.ts', $translatorNames);
    }

    #[Test]
    public function it_fails_when_enum_format_is_invalid(): void
    {
        $this->artisan('type-bridge:generate', [
            '--format' => 'css',
        ])
            ->expectsOutputToContain('Invalid enum format "css". Supported: ts, js.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_translations_format_is_invalid(): void
    {
        $this->artisan('type-bridge:generate', [
            '--format' => 'ts',
            '--translations-format' => 'xml',
        ])
            ->expectsOutputToContain('Invalid translations format "xml". Supported: ts, js, json.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_warns_and_fails_when_no_enums_match_filter(): void
    {
        config([
            'type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums'],
            'type-bridge.enum_translators.translator_output_path' => 'test-output/translators',
        ]);

        $this->artisan('type-bridge:generate', [
            '--enums' => ['MissingEnum'],
        ])
            ->expectsOutputToContain('Some enums were not found and will be skipped: MissingEnum')
            ->expectsOutputToContain('No matching enums were found to generate.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_warns_when_some_enums_missing_and_filters_by_short_and_fqcn(): void
    {
        config([
            'type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums'],
            'type-bridge.enum_translators.translator_output_path' => 'test-output/translators',
        ]);

        $this->artisan('type-bridge:generate', [
            '--enums' => ['TestStatus,MissingEnum', TestRole::class],
        ])
            ->expectsOutputToContain('Some enums were not found and will be skipped: MissingEnum')
            ->expectsOutputToContain('Generating enums...')
            ->assertExitCode(0);

        $enumOutput = resource_path('test-output/enums');
        $files = File::files($enumOutput);
        $names = array_map(static fn ($file) => $file->getFilename(), $files);

        $this->assertContains('TestStatus.ts', $names);
        $this->assertContains('TestRole.ts', $names);
        $this->assertNotContains('TestPriority.ts', $names);
    }

    #[Test]
    public function it_generates_with_locale_flat_and_json_format(): void
    {
        config([
            'type-bridge.enum_translators.discovery.include_paths' => [__DIR__.'/../Fixtures/Enums'],
            'type-bridge.enum_translators.translator_output_path' => 'test-output/translators',
        ]);

        $this->artisan('type-bridge:generate', [
            'locale' => 'en',
            '--flat' => true,
            '--format' => 'js',
            '--translations-format' => 'json',
        ])
            ->expectsOutputToContain('Generating enums...')
            ->expectsOutputToContain('Generating translations...')
            ->expectsOutputToContain('Generating enum translator composables...')
            ->assertExitCode(0);

        $enumOutput = resource_path('test-output/enums');
        $enumFiles = File::files($enumOutput);
        $enumNames = array_map(static fn ($file) => $file->getFilename(), $enumFiles);
        $this->assertContains('TestStatus.js', $enumNames);

        $translationOutput = resource_path('test-output/translations');
        $translationFiles = File::files($translationOutput);
        $translationNames = array_map(static fn ($file) => $file->getFilename(), $translationFiles);
        $this->assertContains('en.json', $translationNames);

        $translatorOutput = resource_path('test-output/translators');
        $translatorFiles = File::exists($translatorOutput) ? File::files($translatorOutput) : [];
        $translatorNames = array_map(static fn ($file) => $file->getFilename(), $translatorFiles);
        $this->assertContains('useTestStatusTranslator.js', $translatorNames);
    }

    #[Test]
    public function it_parses_enum_filters_and_handles_preg_split_failure(): void
    {
        $command = new GenerateAllCommand;
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')
            ->with('enums')
            ->willReturn(['TestStatus,TestRole', 'force-preg-split-false', 123, 'TestStatus']);
        $command->setInput($input);

        $parsed = $this->invokePrivate($command, 'parseEnumFilter');
        $this->assertSame(['TestStatus', 'TestRole'], $parsed);

        $command = new GenerateAllCommand;
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')
            ->with('enums')
            ->willReturn('TestStatus');
        $command->setInput($input);

        $parsed = $this->invokePrivate($command, 'parseEnumFilter');
        $this->assertSame([], $parsed);
    }

    #[Test]
    public function it_handles_filtering_and_translation_discovery_helpers(): void
    {
        $command = new GenerateAllCommand;

        $discovered = collect([
            new ReflectionEnum(TestStatus::class),
            new ReflectionEnum(TestRole::class),
        ]);

        $missing = ['ignore'];
        $all = $this->invokePrivate($command, 'filterEnums', [$discovered, [], &$missing]);
        $this->assertSame([], $missing);
        $this->assertCount(2, $all);

        $missing = [];
        $filters = ['TestStatus', TestRole::class, 'MissingEnum'];
        $filtered = $this->invokePrivate($command, 'filterEnums', [$discovered, $filters, &$missing]);
        $this->assertCount(2, $filtered);
        $this->assertSame(['MissingEnum'], $missing);

        $langRoot = __DIR__.'/../Fixtures/lang';
        $missingRoot = __DIR__.'/../Fixtures/lang-missing';
        $discoveryConfig = new TranslationDiscoveryConfig([$missingRoot, $langRoot]);

        $items = $this->invokePrivate($command, 'buildTranslationDiscoveryItems', [null, false, $discoveryConfig]);
        $this->assertSame([['locale' => 'en', 'flat' => false]], $items);

        $items = $this->invokePrivate($command, 'buildTranslationDiscoveryItems', ['en', true, $discoveryConfig]);
        $this->assertSame(['locale' => 'en', 'flat' => true], $items);

        $formatter = $this->invokePrivate($command, 'makeTranslationFormatter', ['json']);
        $this->assertInstanceOf(JsonTranslationFormatter::class, $formatter);

        $formatter = $this->invokePrivate($command, 'makeTranslationFormatter', ['js']);
        $this->assertInstanceOf(JsTranslationFormatter::class, $formatter);

        $formatter = $this->invokePrivate($command, 'makeTranslationFormatter', ['ts']);
        $this->assertInstanceOf(TsTranslationFormatter::class, $formatter);
    }

    private function invokePrivate(object $target, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $args);
    }
}
