<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumGroupFileParser;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class EnumGroupFileParserTest extends TestCase
{
    #[Test]
    public function it_parses_array_and_record_groups(): void
    {
        $ts = <<<'TS'
export const Sample = {
    ALPHA: 'alpha',
};

export const CustomerValues = [
    Sample.ALPHA,
    'extra',
] as const;

export const LoadValues = {
    ALPHA: Sample.ALPHA,
    "custom-key": 'custom',
} as const;
TS;

        $groups = EnumGroupFileParser::parseString($ts, 'Sample');

        $this->assertArrayHasKey('CustomerValues', $groups);
        $this->assertArrayHasKey('LoadValues', $groups);

        $this->assertSame('array', $groups['CustomerValues']['kind']);
        $this->assertSame('Sample.ALPHA', $groups['CustomerValues']['entries']['0']);
        $this->assertSame("'extra'", $groups['CustomerValues']['entries']['1']);

        $this->assertSame('record', $groups['LoadValues']['kind']);
        $this->assertSame('Sample.ALPHA', $groups['LoadValues']['entries']['ALPHA']);
        $this->assertSame("'custom'", $groups['LoadValues']['entries']['custom-key']);
    }

    #[Test]
    public function it_skips_invalid_record_lines(): void
    {
        $ts = <<<'TS'
export const Sample = {
    ALPHA: 'alpha',
};

export const LoadValues = {
    INVALID_LINE,
    ALPHA: Sample.ALPHA,
};
TS;

        $groups = EnumGroupFileParser::parseString($ts, 'Sample');

        $this->assertSame(['ALPHA' => 'Sample.ALPHA'], $groups['LoadValues']['entries']);
    }

    #[Test]
    public function parse_file_returns_empty_for_missing_path(): void
    {
        $groups = EnumGroupFileParser::parseFile(resource_path('missing/Enum.ts'), 'Missing');

        $this->assertSame([], $groups);
    }
}
