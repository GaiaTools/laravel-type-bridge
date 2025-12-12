<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Attributes\GenerateTranslator;

#[GenerateEnum]
#[GenerateTranslator(translationKey: 'enums.status')]
enum TestStatusWithTranslator: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
