<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use Peast\Peast;
use Peast\Syntax\Node\ExportNamedDeclaration;
use Peast\Syntax\Node\Identifier;
use Peast\Syntax\Node\Literal;
use Peast\Syntax\Node\ObjectExpression;
use Peast\Syntax\Node\Program;
use Peast\Syntax\Node\Property;
use Peast\Syntax\Node\VariableDeclaration;
use Peast\Syntax\Node\VariableDeclarator;

final class EnumFileParser
{
    /**
     * Parse a generated enum file (TS or JS) produced by this package and extract
     * the enum name and case keys and values.
     *
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    public static function parseFile(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        $contents = is_string($contents) ? $contents : '';

        return self::parseString($contents);
    }

    /**
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    public static function parseString(string $contents): ?array
    {
        if (class_exists(Peast::class)) {
            $result = self::parseWithAst($contents);
            if ($result !== null) {
                return $result;
            }
        }

        return self::parseWithRegex($contents);
    }

    /**
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    private static function parseWithAst(string $contents): ?array
    {
        $ast = self::createAst($contents);
        if ($ast === null) {
            return null;
        }

        foreach ($ast->getBody() as $node) {
            $result = self::tryParseExportNode($node);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    private static function createAst(string $contents): ?Program
    {
        $cleanedContents = preg_replace('/}\s+as\s+const\s*;/', '};', $contents) ?? $contents;

        try {
            return Peast::latest($cleanedContents, [
                'sourceType' => Peast::SOURCE_TYPE_MODULE,
            ])->parse();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    private static function tryParseExportNode(object $node): ?array
    {
        if (! $node instanceof ExportNamedDeclaration) {
            return null;
        }

        $declaration = $node->getDeclaration();
        if (! $declaration instanceof VariableDeclaration) {
            return null;
        }

        return self::parseDeclarators($declaration->getDeclarations());
    }

    /**
     * @param iterable<VariableDeclarator> $declarators
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    private static function parseDeclarators(iterable $declarators): ?array
    {
        foreach ($declarators as $declarator) {
            $result = self::parseDeclarator($declarator);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    private static function parseDeclarator(VariableDeclarator $declarator): ?array
    {
        $init = $declarator->getInit();
        if (! $init instanceof ObjectExpression) {
            return null;
        }

        $id = $declarator->getId();
        if (! $id instanceof Identifier) {
            return null;
        }

        $name = $id->getName();
        $parsedEntries = self::extractObjectProperties($init);

        return [
            'name' => $name,
            'cases' => $parsedEntries['cases'],
            'entries' => $parsedEntries['entries'],
        ];
    }

    /**
     * @return array{cases:array<int,string>,entries:array<string,string>}
     */
    private static function extractObjectProperties(ObjectExpression $objectExpression): array
    {
        $cases = [];
        $entries = [];

        foreach ($objectExpression->getProperties() as $property) {
            // Properties are always Property nodes in this context, so skip the instanceof check
            $key = self::extractPropertyKey($property);
            if ($key === null) {
                continue;
            }

            $value = self::extractValue($property->getValue());

            if (! in_array($key, $cases, true)) {
                $cases[] = $key;
            }

            $entries[$key] = $value;
        }

        return [
            'cases' => $cases,
            'entries' => $entries,
        ];
    }

    private static function extractPropertyKey(Property $property): ?string
    {
        $keyNode = $property->getKey();

        if (method_exists($keyNode, 'getName')) {
            return $keyNode->getName();
        }

        if (method_exists($keyNode, 'getValue')) {
            return $keyNode->getValue();
        }

        return null;
    }

    /**
     * @return array{name:string,cases:array<int,string>,entries:array<string,string>}|null
     */
    private static function parseWithRegex(string $contents): ?array
    {
        $contents = self::normalizeAndClean($contents);

        if (! preg_match('#export\s+const\s+(\w+)\s*=\s*\{([\s\S]*?)}\s*(?:as\s+const)?\s*;#m', $contents, $m)) {
            return null;
        }

        $name = $m[1];
        $body = $m[2];

        $parsedEntries = self::extractEntriesFromBody($body);

        return [
            'name' => $name,
            'cases' => $parsedEntries['cases'],
            'entries' => $parsedEntries['entries'],
        ];
    }

    private static function normalizeAndClean(string $contents): string
    {
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = preg_replace('#/\*[\s\S]*?\*/#', '', $contents) ?? $contents;
        $contents = preg_replace('#(^|\n)\s*//.*#', '$1', $contents) ?? $contents;

        return $contents;
    }

    /**
     * @return array{cases:array<int,string>,entries:array<string,string>}
     */
    private static function extractEntriesFromBody(string $body): array
    {
        $cases = [];
        $entries = [];

        $keyPattern = "#\n?\s*([A-Za-z_]\w*)\s*:\s*#";

        if (! preg_match_all($keyPattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
            return ['cases' => $cases, 'entries' => $entries];
        }

        $matchCount = count($matches[1]);

        for ($index = 0; $index < $matchCount; $index++) {
            $key = $matches[1][$index][0];

            // Calculate value start position using arithmetic
            $matchData = $matches[0][$index];
            $matchOffset = (int) $matchData[1];
            $matchLength = strlen($matchData[0]);
            $valueStart = $matchOffset;
            $valueStart += $matchLength;

            // Find the end of this value (next key or closing brace)
            $nextIndex = $index + 1;
            $hasNextMatch = $nextIndex < $matchCount;
            $nextKeyPos = $hasNextMatch
                ? (int) $matches[0][$nextIndex][1]
                : strlen($body);

            $valueLength = $nextKeyPos - $valueStart;
            $valuePart = substr($body, $valueStart, $valueLength);
            $value = self::extractValueFromString($valuePart);

            if (! in_array($key, $cases, true)) {
                $cases[] = $key;
            }

            $entries[$key] = $value;
        }

        return ['cases' => $cases, 'entries' => $entries];
    }

    private static function extractValueFromString(string $valuePart): string
    {
        $valuePart = trim($valuePart);

        // Try each pattern and return the first match
        $patterns = [
            "#^'(?:[^'\\\\]|\\\\.)*'#",      // Single-quoted strings
            '#^"(?:[^"\\\\]|\\\\.)*"#',      // Double-quoted strings
            '#^[^,\n}]+#',                    // Unquoted values
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $valuePart, $match)) {
                return trim($match[0]);
            }
        }

        return '';
    }

    private static function extractValue(object $node): string
    {
        if ($node instanceof Literal) {
            return self::extractLiteralValue($node);
        }

        return self::extractNodeValue($node);
    }

    private static function extractLiteralValue(Literal $node): string
    {
        $raw = $node->getRaw();
        if ($raw !== '') {
            return $raw;
        }

        $value = $node->getValue();
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }

        return self::convertToString($value);
    }

    private static function extractNodeValue(object $node): string
    {
        if (method_exists($node, 'getName')) {
            $name = $node->getName();
            return is_string($name) ? $name : '';
        }

        if (method_exists($node, 'getValue')) {
            $value = $node->getValue();
            return is_string($value) ? $value : self::convertToString($value);
        }

        return '';
    }

    /**
     * Safely convert a value to string.
     *
     * @param mixed $value
     */
    private static function convertToString($value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_null($value)) {
            return '';
        }

        return '';
    }
}
