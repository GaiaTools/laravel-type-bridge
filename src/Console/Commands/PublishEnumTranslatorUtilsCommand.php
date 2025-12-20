<?php

namespace GaiaTools\TypeBridge\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishEnumTranslatorUtilsCommand extends Command
{
    protected $signature = 'type-bridge:publish-translator-utils {--force : Overwrite existing files}';

    protected $description = 'Publish the enum translator utility files to the frontend';

    public function handle(): int
    {
        $outputFormat = config('type-bridge.output_format', 'ts');
        $extension = $outputFormat === 'ts' ? 'ts' : 'js';
        $utilsComposablesOutputPath = config('type-bridge.enum_translators.utils_composables_output_path', 'js/composables');
        $utilsLibOutputPath = config('type-bridge.enum_translators.utils_lib_output_path', 'js/lib');

        $stubExtension = '.stub';

        $files = [
            "useTranslator.{$extension}" => [
                'stub' => __DIR__.'/../../../stubs/useTranslator.'.$extension.$stubExtension,
                'destination' => resource_path("{$utilsComposablesOutputPath}/useTranslator.{$extension}"),
            ],
            "createEnumTranslationMap.{$extension}" => [
                'stub' => __DIR__.'/../../../stubs/createEnumTranslationMap.'.$extension.$stubExtension,
                'destination' => resource_path("{$utilsLibOutputPath}/createEnumTranslationMap.{$extension}"),
            ],
            "translators.{$extension}" => [
                'stub' => __DIR__.'/../../../stubs/translators.'.$extension.$stubExtension,
                'destination' => resource_path("{$utilsLibOutputPath}/translators.{$extension}"),
            ]
        ];

        $published = 0;
        $skipped = 0;

        foreach ($files as $name => $paths) {
            if (! File::exists($paths['stub'])) {
                $this->error("Stub file not found: {$paths['stub']}");

                continue;
            }

            $destination = $paths['destination'];

            if (File::exists($destination) && ! $this->option('force')) {
                $this->warn("File already exists: {$name}");
                $skipped++;

                continue;
            }

            // Ensure directory exists
            File::ensureDirectoryExists(dirname($destination));

            // Copy stub to destination
            File::copy($paths['stub'], $destination);

            $this->info("Published: {$name}");
            $published++;
        }

        $this->newLine();
        $this->info("Published {$published} file(s) in {$extension} format, skipped {$skipped} file(s).");

        if ($skipped > 0) {
            $this->comment('Use --force to overwrite existing files.');
        }

        return self::SUCCESS;
    }
}
