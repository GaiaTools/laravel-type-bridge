<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Generators;

use GaiaTools\TypeBridge\ValueObjects\TransformedTranslation;

final class TranslationGenerator extends AbstractBridgeGenerator
{
    public function getName(): string
    {
        return 'translations';
    }

    protected function buildFilePath(mixed $transformed): string
    {
        assert($transformed instanceof TransformedTranslation);

        $filename = sprintf('%s.%s', $transformed->locale, $this->formatter->getExtension());

        return sprintf('%s/%s', $transformed->outputPath, $filename);
    }
}
