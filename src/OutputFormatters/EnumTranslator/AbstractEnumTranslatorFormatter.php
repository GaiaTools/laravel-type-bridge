<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\OutputFormatters\EnumTranslator;

use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\ValueObjects\TransformedEnumTranslator;
use InvalidArgumentException;

abstract class AbstractEnumTranslatorFormatter implements OutputFormatter
{
    public function __construct(
        protected readonly string $i18nFramework = 'i18next'
    ) {}

    public function format(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnumTranslator);

        return match ($this->i18nFramework) {
            'vue-i18n' => $this->formatVueI18n($transformed),
            'i18next' => $this->formatI18next($transformed),
            'react-i18next' => $this->formatReactI18next($transformed),
            default => throw new InvalidArgumentException(
                "Unsupported i18n framework: {$this->i18nFramework}. ".
                'Supported: vue-i18n, i18next, react-i18next'
            ),
        };
    }

    /**
     * Format for Vue I18n.
     * Default implementation delegates to shared framework formatter.
     * Override in subclass if framework-specific handling needed.
     */
    protected function formatVueI18n(TransformedEnumTranslator $transformed): string
    {
        return $this->formatWithEngine($transformed, 'composable', 'VueI18nEngine');
    }

    /**
     * Format for i18next.
     * Default implementation delegates to shared framework formatter.
     * Override in subclass if framework-specific handling needed.
     */
    protected function formatI18next(TransformedEnumTranslator $transformed): string
    {
        return $this->formatWithEngine($transformed, 'function', 'I18nextEngine');
    }

    /**
     * Format for react-i18next.
     * Default implementation delegates to shared framework formatter.
     * Override in subclass if framework-specific handling needed.
     */
    protected function formatReactI18next(TransformedEnumTranslator $transformed): string
    {
        return $this->formatWithEngine($transformed, 'hook', 'ReactI18nextEngine');
    }

    /**
     * Shared engine-driven formatter implementation.
     * Override in subclass to provide language-specific formatting (JS vs TS).
     *
     * @param  string  $docType  Documentation type for JSDoc (e.g., 'composable', 'function', 'hook')
     * @param  string  $engine  The engine symbol name (e.g., 'VueI18nEngine', 'I18nextEngine', 'ReactI18nextEngine')
     */
    abstract protected function formatWithEngine(
        TransformedEnumTranslator $transformed,
        string $docType,
        string $engine
    ): string;
}
