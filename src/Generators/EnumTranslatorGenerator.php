<?php

// src/Generators/EnumTranslatorGenerator.php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Generators;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

final class EnumTranslatorGenerator extends AbstractBridgeGenerator
{
    public function getName(): string
    {
        return 'enum-translators';
    }

    protected function buildFilePath(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnumTranslator);

        $filename = sprintf('%s.%s', $transformed->name, $this->formatter->getExtension());

        return sprintf('%s/%s', $transformed->outputPath, $filename);
    }
}
