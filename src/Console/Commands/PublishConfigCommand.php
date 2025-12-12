<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Console\Commands;

use GaiaTools\TypeBridge\Contracts\FileEnumerator;
use Illuminate\Console\Command;

class PublishConfigCommand extends Command
{
    protected $signature = 'type-bridge:publish {--force : Overwrite existing config file}';

    protected $description = 'Publish Type Bridge configuration with smart defaults';

    public function __construct(private readonly FileEnumerator $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('📦 Publishing Type Bridge configuration...');
        $this->newLine();

        $detectedFormat = $this->detectOutputFormat();
        $detectedI18nLibrary = $this->detectI18nLibrary();

        $this->info('🔍 Detected configuration:');
        $this->line("  • Output format: <comment>{$detectedFormat}</comment>");
        $this->line("  • i18n library: <comment>{$detectedI18nLibrary}</comment>");
        $this->newLine();

        $stubPath = __DIR__.'/../../../config/type-bridge.php';
        $configContent = $this->safeGetContents($stubPath);

        $configContent = str_replace(
            "env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts')",
            "env('TYPE_BRIDGE_OUTPUT_FORMAT', '{$detectedFormat}')",
            $configContent
        );

        $configContent = str_replace(
            "env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next')",
            "env('TYPE_BRIDGE_I18N_LIBRARY', '{$detectedI18nLibrary}')",
            $configContent
        );

        $configPath = config_path('type-bridge.php');
        if (file_exists($configPath) && ! $this->option('force')) {
            if (! $this->confirm('Config file already exists. Overwrite?', false)) {
                $this->warn('Publishing cancelled.');

                return self::SUCCESS;
            }
        }

        file_put_contents($configPath, $configContent);

        $this->info('✅ Configuration published successfully!');
        $this->newLine();

        if ($this->confirm('Would you like to add these settings to your .env file?', true)) {
            $this->updateEnvFile($detectedFormat, $detectedI18nLibrary);
            $this->info('✅ Environment file updated!');
            $this->newLine();
        }

        $this->info('Next steps:');
        $this->line('  • Generate enums: <comment>php artisan type-bridge:enums</comment>');
        $this->line('  • Generate translations: <comment>php artisan type-bridge:translations en</comment>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Detect whether the project uses TypeScript or JavaScript
     */
    private function detectOutputFormat(): string
    {
        if (file_exists(base_path('tsconfig.json'))) {
            return 'ts';
        }

        $packageJsonPath = base_path('package.json');
        if (file_exists($packageJsonPath)) {
            $raw = $this->safeGetContents($packageJsonPath);
            $packageJson = json_decode($raw, true);

            // Type guard: ensure json_decode returned an array
            if (! is_array($packageJson)) {
                return 'js';
            }

            // Type guard: ensure properties are arrays before array_merge
            $deps = array_merge(
                is_array($packageJson['dependencies'] ?? null) ? $packageJson['dependencies'] : [],
                is_array($packageJson['devDependencies'] ?? null) ? $packageJson['devDependencies'] : []
            );

            if (isset($deps['typescript']) || isset($deps['vue-tsc']) || isset($deps['@types/node'])) {
                return 'ts';
            }
        }

        $commonDirs = [
            resource_path('js'),
            resource_path('frontend'),
            base_path('resources/ts'),
        ];

        foreach ($commonDirs as $dir) {
            if ($this->hasTypeScriptFiles($dir)) {
                return 'ts';
            }
        }

        return 'js';
    }

    /**
     * Detect which i18n library the project uses
     */
    private function detectI18nLibrary(): string
    {
        $packageJsonPath = base_path('package.json');
        if (! file_exists($packageJsonPath)) {
            return 'i18next';
        }

        $raw = $this->safeGetContents($packageJsonPath);
        $packageJson = json_decode($raw, true);

        // Type guard: ensure json_decode returned an array
        if (! is_array($packageJson)) {
            return 'i18next';
        }

        // Type guard: ensure properties are arrays before array_merge
        $deps = array_merge(
            is_array($packageJson['dependencies'] ?? null) ? $packageJson['dependencies'] : [],
            is_array($packageJson['devDependencies'] ?? null) ? $packageJson['devDependencies'] : []
        );

        if (isset($deps['vue-i18n'])) {
            return 'vue-i18n';
        }

        if (isset($deps['i18next']) || isset($deps['react-i18next'])) {
            return 'i18next';
        }

        return 'i18next';
    }

    /**
     * Check if directory contains TypeScript files
     */
    private function hasTypeScriptFiles(string $path): bool
    {
        foreach ($this->files->enumerate($path) as $file) {
            if ($file->getExtension() === 'ts') {
                return true;
            }
        }

        return false;
    }

    /**
     * Update .env file with detected values
     */
    private function updateEnvFile(string $outputFormat, string $i18nLibrary): void
    {
        $this->updateSingleEnvFile(base_path('.env'), $outputFormat, $i18nLibrary);

        $envExamplePath = base_path('.env.example');
        if (file_exists($envExamplePath)) {
            $this->updateSingleEnvFile($envExamplePath, $outputFormat, $i18nLibrary);
            $this->line('  • Updated .env.example');
        }
    }

    /**
     * Update a single env file with detected values
     */
    private function updateSingleEnvFile(string $filePath, string $outputFormat, string $i18nLibrary): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $envContent = $this->safeGetContents($filePath);
        $updates = [];

        if (str_contains($envContent, 'TYPE_BRIDGE_OUTPUT_FORMAT=')) {
            $envContent = (string) preg_replace(
                '/TYPE_BRIDGE_OUTPUT_FORMAT=.*/',
                "TYPE_BRIDGE_OUTPUT_FORMAT={$outputFormat}",
                $envContent
            );
        } else {
            $updates[] = "TYPE_BRIDGE_OUTPUT_FORMAT={$outputFormat}";
        }

        if (str_contains($envContent, 'TYPE_BRIDGE_I18N_LIBRARY=')) {
            $envContent = (string) preg_replace(
                '/TYPE_BRIDGE_I18N_LIBRARY=.*/',
                "TYPE_BRIDGE_I18N_LIBRARY={$i18nLibrary}",
                $envContent
            );
        } else {
            $updates[] = "TYPE_BRIDGE_I18N_LIBRARY={$i18nLibrary}";
        }

        if (! empty($updates)) {
            $envContent = rtrim($envContent)."\n\n# Type Bridge Configuration\n".implode("\n", $updates)."\n";
        }

        file_put_contents($filePath, $envContent);
    }

    /**
     * Safely read file contents as string
     */
    private function safeGetContents(string $path): string
    {
        $content = @file_get_contents($path);

        return $content === false ? '' : $content;
    }
}
