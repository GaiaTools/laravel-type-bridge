<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Feature;

use GaiaTools\TypeBridge\Contracts\FileEnumerator;
use GaiaTools\TypeBridge\Support\EnforcingFileEnumerator;
use GaiaTools\TypeBridge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Mockery;
use SplFileInfo;
use UnexpectedValueException;

final class PublishConfigCommandTest extends TestCase
{
    private string $configPath;
    private string $envPath;
    private string $envExamplePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configPath = config_path('type-bridge.php');
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');

        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }

        // Remove TS indicators that could affect “js default” tests
        $tsconfigPath = base_path('tsconfig.json');
        if (file_exists($tsconfigPath)) {
            unlink($tsconfigPath);
        }

        $testTsFile = resource_path('js/app.ts');
        if (file_exists($testTsFile)) {
            unlink($testTsFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }

        $tsconfigPath = base_path('tsconfig.json');
        if (file_exists($tsconfigPath)) {
            unlink($tsconfigPath);
        }

        $testTsFile = resource_path('js/app.ts');
        if (file_exists($testTsFile)) {
            unlink($testTsFile);
        }

        parent::tearDown();
    }

    public function test_publishes_config_successfully_with_typescript_detected_via_enumerator_and_i18next(): void
    {
        // Force TS detection without touching the real filesystem scan:
        $files = Mockery::mock(FileEnumerator::class);
        $files->shouldReceive('enumerate')
            ->once()
            ->andReturn([new SplFileInfo(base_path('resources/js/app.ts'))]);

        $this->app->instance(FileEnumerator::class, $files);

        // package.json -> i18next
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode([
            'dependencies' => ['i18next' => '^21.0.0'],
        ]));

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsOutputToContain('Output format: ts')
                ->expectsOutputToContain('i18n library: i18next')
                ->expectsQuestion('Would you like to add these settings to your .env file?', false)
                ->assertExitCode(0);

            $this->assertFileExists($this->configPath);

            $config = file_get_contents($this->configPath);
            $this->assertStringContainsString("env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts')", $config);
            $this->assertStringContainsString("env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next')", $config);
        } finally {
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }

    public function test_defaults_to_javascript_when_enumerator_returns_no_ts_files(): void
    {
        $files = Mockery::mock(FileEnumerator::class);

        $files->shouldReceive('enumerate')
            ->withArgs(function (string $dir): bool {
                return in_array($dir, [
                    resource_path('js'),
                    resource_path('frontend'),
                    base_path('resources/ts'),
                ], true);
            })
            ->times(3)
            ->andReturn([]);

        $this->app->instance(FileEnumerator::class, $files);

        // package.json -> vue-i18n
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode([
            'dependencies' => ['vue-i18n' => '^9.0.0'],
        ]));

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsOutputToContain('Output format: js')
                ->expectsOutputToContain('i18n library: vue-i18n')
                ->expectsQuestion('Would you like to add these settings to your .env file?', false)
                ->assertExitCode(0);

            $config = file_get_contents($this->configPath);
            $this->assertStringContainsString("env('TYPE_BRIDGE_OUTPUT_FORMAT', 'js')", $config);
            $this->assertStringContainsString("env('TYPE_BRIDGE_I18N_LIBRARY', 'vue-i18n')", $config);
        } finally {
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }

    public function test_enforcing_file_enumerator_throws_when_inner_yields_non_splfileinfo(): void
    {
        // This is the branch you could never hit with RecursiveDirectoryIterator.
        $inner = Mockery::mock(FileEnumerator::class);
        $inner->shouldReceive('enumerate')
            ->once()
            ->andReturn([new \stdClass()]);

        $enforcing = new EnforcingFileEnumerator($inner);

        $this->expectException(UnexpectedValueException::class);

        foreach ($enforcing->enumerate(base_path()) as $_) {
            // no-op; force iteration
        }
    }

    public function test_force_flag_overwrites_without_prompting(): void
    {
        if (! is_dir(dirname($this->configPath))) {
            mkdir(dirname($this->configPath), 0755, true);
        }
        file_put_contents($this->configPath, '<?php return [];');

        $this->artisan('type-bridge:publish', ['--force' => true])
            ->expectsQuestion('Would you like to add these settings to your .env file?', false)
            ->assertExitCode(0);

        $this->assertNotSame('<?php return [];', file_get_contents($this->configPath));
    }

    public function test_updates_env_file_when_confirmed(): void
    {
        $originalEnv = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;

        file_put_contents($this->envPath, "APP_NAME=Laravel\nAPP_ENV=local\n");

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsQuestion('Would you like to add these settings to your .env file?', true)
                ->expectsOutput('✅ Environment file updated!')
                ->assertExitCode(0);

            $env = file_get_contents($this->envPath);
            $this->assertStringContainsString('# Type Bridge Configuration', $env);
            $this->assertStringContainsString('TYPE_BRIDGE_OUTPUT_FORMAT=', $env);
            $this->assertStringContainsString('TYPE_BRIDGE_I18N_LIBRARY=', $env);
        } finally {
            if ($originalEnv !== null) {
                file_put_contents($this->envPath, $originalEnv);
            } elseif (file_exists($this->envPath)) {
                unlink($this->envPath);
            }
        }
    }

    public function test_cancel_overwrite_when_config_exists(): void
    {
        // Arrange: existing config file
        if (! is_dir(dirname($this->configPath))) {
            mkdir(dirname($this->configPath), 0755, true);
        }
        file_put_contents($this->configPath, '<?php return ["existing" => true];');

        // package.json minimal to avoid notices
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode(['dependencies' => new \stdClass()]));

        try {
            $this->artisan('type-bridge:publish')
                ->expectsQuestion('Config file already exists. Overwrite?', false)
                ->expectsOutput('Publishing cancelled.')
                ->assertExitCode(0);

            // Ensure file was not changed
            $this->assertSame('<?php return ["existing" => true];', file_get_contents($this->configPath));
        } finally {
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }

    public function test_updates_env_example_when_present(): void
    {
        // Arrange .env and .env.example
        $originalEnv = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
        $originalEnvExample = file_exists($this->envExamplePath) ? file_get_contents($this->envExamplePath) : null;

        file_put_contents($this->envPath, "APP_NAME=Laravel\n");
        file_put_contents($this->envExamplePath, "APP_NAME=LaravelExample\n");

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsQuestion('Would you like to add these settings to your .env file?', true)
                ->expectsOutputToContain('Updated .env.example')
                ->assertExitCode(0);

            $envExample = file_get_contents($this->envExamplePath);
            $this->assertStringContainsString('# Type Bridge Configuration', $envExample);
            $this->assertStringContainsString('TYPE_BRIDGE_OUTPUT_FORMAT=', $envExample);
            $this->assertStringContainsString('TYPE_BRIDGE_I18N_LIBRARY=', $envExample);
        } finally {
            // restore
            if ($originalEnv !== null) {
                file_put_contents($this->envPath, $originalEnv);
            } elseif (file_exists($this->envPath)) {
                unlink($this->envPath);
            }

            if ($originalEnvExample !== null) {
                file_put_contents($this->envExamplePath, $originalEnvExample);
            } elseif (file_exists($this->envExamplePath)) {
                unlink($this->envExamplePath);
            }
        }
    }

    public function test_env_existing_keys_are_updated_not_duplicated(): void
    {
        $originalEnv = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;

        // Pre-seed with existing keys
        file_put_contents($this->envPath, "APP_NAME=Laravel\nTYPE_BRIDGE_OUTPUT_FORMAT=js\nTYPE_BRIDGE_I18N_LIBRARY=vue-i18n\n");

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsQuestion('Would you like to add these settings to your .env file?', true)
                ->assertExitCode(0);

            $env = file_get_contents($this->envPath);
            // Should not contain header because keys were already present
            $this->assertStringNotContainsString('# Type Bridge Configuration', $env);
            // Keys should be updated (either ts/js and specific library depending on detection)
            $this->assertMatchesRegularExpression('/^TYPE_BRIDGE_OUTPUT_FORMAT=.+/m', $env);
            $this->assertMatchesRegularExpression('/^TYPE_BRIDGE_I18N_LIBRARY=.+/m', $env);
            // Only one occurrence of each
            $this->assertSame(1, preg_match_all('/^TYPE_BRIDGE_OUTPUT_FORMAT=/m', $env));
            $this->assertSame(1, preg_match_all('/^TYPE_BRIDGE_I18N_LIBRARY=/m', $env));
        } finally {
            if ($originalEnv !== null) {
                file_put_contents($this->envPath, $originalEnv);
            } elseif (file_exists($this->envPath)) {
                unlink($this->envPath);
            }
        }
    }

    public function test_detects_react_i18next_as_i18next(): void
    {
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode([
            'dependencies' => ['react-i18next' => '^12.0.0'],
        ]));

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsOutputToContain('i18n library: i18next')
                ->expectsQuestion('Would you like to add these settings to your .env file?', false)
                ->assertExitCode(0);
        } finally {
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }

    public function test_handles_invalid_package_json_gracefully(): void
    {
        // Create a directory named package.json to make file_get_contents fail
        $packageJsonPath = base_path('package.json');
        $original = null;
        if (file_exists($packageJsonPath)) {
            $original = file_get_contents($packageJsonPath);
            unlink($packageJsonPath);
        }
        mkdir($packageJsonPath);

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsQuestion('Would you like to add these settings to your .env file?', false)
                ->assertExitCode(0);
        } finally {
            // cleanup
            if (is_dir($packageJsonPath)) {
                rmdir($packageJsonPath);
            }
            if ($original !== null) {
                file_put_contents($packageJsonPath, $original);
            }
        }
    }

    public function test_detects_typescript_via_tsconfig_json(): void
    {
        // Ensure no stray TS indicators from other sources
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode(['dependencies' => new \stdClass()]));

        $tsconfigPath = base_path('tsconfig.json');
        $originalTsconfig = file_exists($tsconfigPath) ? file_get_contents($tsconfigPath) : null;
        file_put_contents($tsconfigPath, '{}');

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsOutputToContain('Output format: ts')
                ->expectsQuestion('Would you like to add these settings to your .env file?', false)
                ->assertExitCode(0);

            $this->assertFileExists($this->configPath);
            $this->assertStringContainsString(
                "env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts')",
                file_get_contents($this->configPath)
            );
        } finally {
            // restore tsconfig
            if ($originalTsconfig !== null) {
                file_put_contents($tsconfigPath, $originalTsconfig);
            } elseif (file_exists($tsconfigPath)) {
                unlink($tsconfigPath);
            }

            // restore package.json
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }

    public function test_detect_output_format_from_package_json_dependencies_hits_line_103(): void
    {
        // Ensure no tsconfig.json exists so we don't take the early return branch
        $tsconfigPath = base_path('tsconfig.json');
        $originalTsconfig = file_exists($tsconfigPath) ? file_get_contents($tsconfigPath) : null;
        if (file_exists($tsconfigPath)) {
            unlink($tsconfigPath);
        }

        // Provide package.json with a TS-indicating dependency so detectOutputFormat() returns 'ts'
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode([
            // Use devDependencies to exercise the array_merge path too
            'devDependencies' => [
                'vue-tsc' => '^1.0.0',
            ],
        ], JSON_PRETTY_PRINT));

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsOutputToContain('Output format: ts')
                ->expectsQuestion('Would you like to add these settings to your .env file?', false)
                ->assertExitCode(0);

            $this->assertFileExists($this->configPath);
            $config = file_get_contents($this->configPath);
            $this->assertStringContainsString("env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts')", $config);
        } finally {
            // restore tsconfig
            if ($originalTsconfig !== null) {
                file_put_contents($tsconfigPath, $originalTsconfig);
            } elseif (file_exists($tsconfigPath)) {
                unlink($tsconfigPath);
            }

            // restore package.json
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }

    public function test_update_single_env_file_returns_early_when_env_files_missing(): void
    {
        // Ensure .env and .env.example do not exist
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }
        if (file_exists($this->envExamplePath)) {
            unlink($this->envExamplePath);
        }

        // Use a simple package.json to avoid notices
        $packageJsonPath = base_path('package.json');
        $originalPackageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
        file_put_contents($packageJsonPath, json_encode(['dependencies' => new \stdClass()]));

        try {
            $this->artisan('type-bridge:publish', ['--force' => true])
                ->expectsQuestion('Would you like to add these settings to your .env file?', true)
                ->expectsOutput('✅ Environment file updated!')
                ->assertExitCode(0);

            // Since files were missing, updateSingleEnvFile returns early and no files are created
            $this->assertFileDoesNotExist($this->envPath);
            $this->assertFileDoesNotExist($this->envExamplePath);
        } finally {
            if ($originalPackageJson !== null) {
                file_put_contents($packageJsonPath, $originalPackageJson);
            } elseif (file_exists($packageJsonPath)) {
                unlink($packageJsonPath);
            }
        }
    }
}
