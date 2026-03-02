<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Unit\Support;

use GaiaTools\TypeBridge\Support\EnumDiffing;
use GaiaTools\TypeBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class EnumDiffingTest extends TestCase
{
    #[Test]
    public function it_diffs_added_removed_and_changed_entries_without_prefix(): void
    {
        $backend = [
            'A' => '1',
            'B' => '2',
            'C' => '3',
        ];
        $frontend = [
            'B' => '2',
            'C' => '4',
            'D' => '5',
        ];

        $diff = EnumDiffing::diffEntries($backend, $frontend, '');

        $this->assertSame(
            ['A: 1', 'C: 3'],
            $diff['added'],
        );
        $this->assertSame(
            ['D: 5', 'C: 4'],
            $diff['removed'],
        );
    }

    #[Test]
    public function it_diffs_groups_when_one_side_is_missing(): void
    {
        $backendGroups = [
            'Roles' => [
                'kind' => 'record',
                'entries' => [
                    'ADMIN' => 'Admin',
                    'USER' => 'User',
                ],
            ],
        ];
        $frontendGroups = [
            'Colors' => [
                'kind' => 'array',
                'entries' => [
                    '0' => 'red',
                    '1' => 'blue',
                ],
            ],
        ];

        $diff = EnumDiffing::diffGroups($backendGroups, $frontendGroups);

        $this->assertSame(
            ['Roles.ADMIN: Admin', 'Roles.USER: User'],
            $diff['added'],
        );
        $this->assertSame(
            ['Colors[0]: red', 'Colors[1]: blue'],
            $diff['removed'],
        );
    }

    #[Test]
    public function it_diffs_group_kind_and_entries_with_prefix_rules(): void
    {
        $backendGroups = [
            'Flags' => [
                'kind' => 'array',
                'entries' => [
                    '0' => 'on',
                    '1' => 'off',
                    '2' => 'maybe',
                ],
            ],
        ];
        $frontendGroups = [
            'Flags' => [
                'kind' => 'record',
                'entries' => [
                    '0' => 'on',
                    '1' => 'OFF',
                    '3' => 'auto',
                ],
            ],
        ];

        $diff = EnumDiffing::diffGroups($backendGroups, $frontendGroups);

        $this->assertSame(
            [
                'Flags[@kind]: array',
                'Flags[2]: maybe',
                'Flags[1]: off',
            ],
            $diff['added'],
        );
        $this->assertSame(
            [
                'Flags[@kind]: record',
                'Flags[3]: auto',
                'Flags[1]: OFF',
            ],
            $diff['removed'],
        );
    }
}
