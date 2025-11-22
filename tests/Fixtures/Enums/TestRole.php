<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum]
enum TestRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
