<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

enum TestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
