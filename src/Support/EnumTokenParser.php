<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Support;

use PhpToken;

/**
 * Parses PHP tokens to extract enum declarations and their fully-qualified class names.
 */
final class EnumTokenParser
{
    /**
     * Extract fully-qualified enum names declared in a PHP file by parsing its tokens.
     *
     * @param  string  $filepath  Path to the PHP file
     * @return array<int, string> Array of fully-qualified enum class names
     */
    public function extractEnumFqcnsFromFile(string $filepath): array
    {
        $code = @file_get_contents($filepath);
        if ($code === false) {
            return [];
        }

        /** @var list<PhpToken> $tokens */
        $tokens = PhpToken::tokenize($code);

        return $this->parseTokensForEnums($tokens);
    }

    /**
     * Parse PHP tokens to find enum declarations and build their FQCNs.
     *
     * @param  list<object>  $tokens  Array of PHP tokens
     * @return array<int, string> Array of fully-qualified enum class names
     */
    public function parseTokensForEnums(array $tokens): array
    {
        $namespace = '';
        $fqcns = [];

        $count = count($tokens);
        $i = 0;
        while ($i < $count) {
            $tok = $tokens[$i];

            // Capture namespace Foo\Bar;
            if ($this->tokIs($tok, T_NAMESPACE)) {
                [$namespace, $i] = $this->consumeNamespace($tokens, $i + 1, $count);

                continue;
            }

            // Find enum declarations
            if ($this->tokIs($tok, T_ENUM)) {
                [$name, $i] = $this->consumeNameAfterEnum($tokens, $i + 1, $count);
                if ($name !== '') {
                    $fqcns[] = trim(($namespace !== '' ? $namespace.'\\' : '').$name, '\\');
                }

                continue;
            }

            $i++;
        }

        return array_values(array_unique($fqcns));
    }

    /**
     * Consume tokens to extract the namespace name.
     *
     * @param  list<object>  $tokens  Array of PHP tokens
     * @param  int  $i  Starting index
     * @param  int  $count  Total token count
     * @return array{0:string,1:int} Tuple of [namespace, new_index]
     */
    public function consumeNamespace(array $tokens, int $i, int $count): array
    {
        $ns = '';
        while ($i < $count) {
            $t = $tokens[$i];
            $text = $this->tokText($t);
            if (trim($text) === '') {
                $i++;

                continue;
            }
            if ($text === ';' || $text === '{') {
                $i++;
                break;
            }
            $ns .= $text;
            $i++;
        }

        return [trim($ns, ' \\'), $i];
    }

    /**
     * Consume tokens to extract the enum name after the 'enum' keyword.
     *
     * @param  list<object>  $tokens  Array of PHP tokens
     * @param  int  $i  Starting index
     * @param  int  $count  Total token count
     * @return array{0:string,1:int} Tuple of [enum_name, new_index]
     */
    public function consumeNameAfterEnum(array $tokens, int $i, int $count): array
    {
        while ($i < $count) {
            $t = $tokens[$i];
            $text = $this->tokText($t);
            if (trim($text) === '') {
                $i++;

                continue;
            }
            if ($text === '{' || $text === '(') {
                return ['', $i];
            }

            // First non-whitespace, non-symbol after 'enum' is the name
            return [$text, $i + 1];
        }

        return ['', $i];
    }

    /**
     * Extract text from a token.
     *
     * @param  mixed  $tok  Token object or string
     * @return string Token text
     */
    public function tokText(mixed $tok): string
    {
        $result = '';
        if (is_object($tok)) {
            if (property_exists($tok, 'text')) {
                $result = (string) $tok->text;
            } elseif (method_exists($tok, 'text')) {
                $result = (string) $tok->text();
            }
        } elseif (is_string($tok)) {
            $result = $tok;
        }

        return $result;
    }

    /**
     * Check if a token matches a specific token ID.
     *
     * @param  mixed  $tok  Token object
     * @param  int  $id  Token ID (e.g., T_ENUM, T_NAMESPACE)
     * @return bool True if token matches the ID
     */
    public function tokIs(mixed $tok, int $id): bool
    {
        if (is_object($tok) && method_exists($tok, 'is')) {
            return (bool) $tok->is($id);
        }

        return false;
    }
}
