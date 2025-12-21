<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Config\TranslationDiscoveryConfig;
use GaiaTools\TypeBridge\Support\TranslationIndex;
use GaiaTools\TypeBridge\Tests\Fixtures\Enums\TestStatus;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class TranslationIndexTest extends TestCase
{
    #[Test]
    public function it_skips_missing_locale_directories_and_still_loads_existing_ones(): void
    {
        $config = new TranslationDiscoveryConfig(
            langPaths: [
                resource_path('non-existent-lang-root'), // should trigger the `continue` branch
                __DIR__.'/../../Fixtures/lang',          // valid fixtures in this repository
            ]
        );

        $index = new TranslationIndex(locale: 'en', config: $config);

        $ref = new \ReflectionEnum(TestStatus::class);

        // keys exist in tests/Fixtures/lang/en/enums.php under TestStatus
        $this->assertTrue($index->hasAnyForEnum('TestStatus', $ref));
    }
}
