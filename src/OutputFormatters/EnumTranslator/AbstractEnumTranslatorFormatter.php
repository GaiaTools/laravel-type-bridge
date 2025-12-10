<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;

abstract class AbstractEnumTranslatorFormatter implements OutputFormatter
{
    public function __construct(
        protected readonly string $i18nFramework = 'vue-i18n'
    ) {}

    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnumTranslator);

        return match($this->i18nFramework) {
            'vue-i18n' => $this->formatVueI18n($transformed),
            'i18next' => $this->formatI18next($transformed),
            'laravel' => $this->formatLaravel($transformed),
            'vanilla' => $this->formatVanilla($transformed),
            default => throw new \InvalidArgumentException(
                "Unsupported i18n framework: {$this->i18nFramework}. Supported: vue-i18n, i18next, laravel, vanilla"
            ),
        };
    }

    abstract protected function formatVueI18n(TransformedEnumTranslator $transformed): string;
    abstract protected function formatI18next(TransformedEnumTranslator $transformed): string;
    abstract protected function formatLaravel(TransformedEnumTranslator $transformed): string;
    abstract protected function formatVanilla(TransformedEnumTranslator $transformed): string;
}
