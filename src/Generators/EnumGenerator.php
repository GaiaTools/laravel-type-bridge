<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Generators;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;

final class EnumGenerator extends AbstractBridgeGenerator
{
    public function getName(): string
    {
        return 'enums';
    }

    protected function buildFilePath(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnum);

        $filename = sprintf('%s.%s', $transformed->name, $this->formatter->getExtension());

        return sprintf('%s/%s', $transformed->outputPath, $filename);
    }
}
