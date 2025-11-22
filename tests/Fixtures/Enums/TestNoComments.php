<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(requiresComments: true)]
enum TestNoComments: string
{
    // Intentionally no doc comments to trigger validation path
    case FOO = 'foo';
    case BAR = 'bar';
}
