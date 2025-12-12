<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateTranslator(translationKey: 'enums.no_composable', generateComposable: false)]
enum TestNoComposable: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
