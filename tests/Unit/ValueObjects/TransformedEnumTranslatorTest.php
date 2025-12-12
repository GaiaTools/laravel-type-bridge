<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\ValueObjects;

use GaiaTools\TypeBridge\Tests\TestCase;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;
use PHPUnit\Framework\Attributes\Test;

class TransformedEnumTranslatorTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_all_properties(): void
    {
        $vo = new TransformedEnumTranslator(
            name: 'useStatusTranslator',
            enumName: 'Status',
            translationKey: 'enums.status',
            enumImportPath: '@/enums/Status',
            outputPath: 'js/composables/generated/useStatusTranslator.ts'
        );

        $this->assertSame('useStatusTranslator', $vo->name);
        $this->assertSame('Status', $vo->enumName);
        $this->assertSame('enums.status', $vo->translationKey);
        $this->assertSame('@/enums/Status', $vo->enumImportPath);
        $this->assertSame('js/composables/generated/useStatusTranslator.ts', $vo->outputPath);
    }
}
