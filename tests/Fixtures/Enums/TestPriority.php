<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Tests\Fixtures\Enums;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;

#[GenerateEnum(requiresComments: true)]
enum TestPriority: int
{
    /** High priority */
    case HIGH = 1;

    /** Medium priority */
    case MEDIUM = 2;

    /** Low priority */
    case LOW = 3;
}
