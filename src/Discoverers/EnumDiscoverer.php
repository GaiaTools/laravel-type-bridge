<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Discoverers;

use GaiaTools\TypeBridge\Attributes\GenerateEnum;
use GaiaTools\TypeBridge\Config\EnumDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Discoverer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpToken;
use ReflectionEnum;
use SplFileInfo;
use UnitEnum;

final class EnumDiscoverer implements Discoverer
{
    public function __construct(
        private readonly EnumDiscoveryConfig $config,
    ) {}

    /**
     * @return Collection<int, mixed>
     */
    public function discover(): Collection
    {
        $paths = collect($this->config->paths);

        /** @var Collection<int, string> $classes */
        $classes = $paths->flatMap(function (string $path): Collection {
            return collect(File::allFiles($path))
                ->map(fn (SplFileInfo $file) => $file->getPathname())
                ->filter(fn (string $filePath) => Str::endsWith($filePath, '.php'))
                ->flatMap(function (string $filepath): array {
                    return $this->extractEnumFqcnsFromFile($filepath);
                })
                ->unique()
                ->values();
        });

        /** @var Collection<int, class-string<UnitEnum>> $enums */
        $enums = $classes->filter(function (string $class): bool {
            if (! enum_exists($class)) {
                return false;
            }

            $ref = new ReflectionEnum($class);
            $hasAttribute = ! empty($ref->getAttributes(GenerateEnum::class));

            // Always include enums with GenerateEnum attribute
            if ($hasAttribute) {
                return true;
            }

            // Include backed enums only if generateBackedEnums is enabled
            return $this->config->generateBackedEnums && $ref->isBacked();
        })->values();

        $result = $enums
            ->map(function (string $enumClass) {
                /** @var class-string<UnitEnum> $enumClass */
                return new ReflectionEnum($enumClass);
            })
            ->filter(fn (ReflectionEnum $ref): bool => $this->shouldInclude($ref))
            ->values();

        $asArray = $result->all();

        /** @var list<mixed> $asArray */
        return collect($asArray);
    }

    /**
     * Extract fully-qualified enum names declared in a PHP file by parsing its tokens.
     *
     * @return array<int, string>
     */
    private function extractEnumFqcnsFromFile(string $filepath): array
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
     * @param  list<object>  $tokens  objects exposing is(int):bool and text():string
     * @return array<int, string>
     */
    private function parseTokensForEnums(array $tokens): array
    {
        $namespace = '';
        $fqcns = [];

        $count = count($tokens);
        $i = 0;
        while ($i < $count) {
            $tok = $tokens[$i];

            // Capture namespace Foo\Bar; (semicolon-style)
            if ($this->tokIs($tok, T_NAMESPACE)) {
                [$namespace, $i] = $this->consumeNamespace($tokens, $i + 1, $count);

                continue;
            }

            // Find enum declarations: enum Name
            if ($this->isEnumKeywordToken($tok)) {
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
     * Determines whether the provided token represents the 'enum' keyword.
     *
     * @param  mixed  $tok
     */
    private function isEnumKeywordToken($tok): bool
    {
        return $this->tokIs($tok, T_ENUM);
    }

    /**
     * Consumes tokens that form a namespace declaration and returns the fully-qualified namespace
     * and the next cursor position to continue scanning from.
     *
     * @param  list<object>  $tokens
     * @return array{0:string,1:int}
     */
    private function consumeNamespace(array $tokens, int $i, int $count): array
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
     * Reads the name that follows the 'enum' keyword and returns it along with the index
     * from which scanning should continue.
     *
     * @param  list<object>  $tokens
     * @return array{0:string,1:int}
     */
    private function consumeNameAfterEnum(array $tokens, int $i, int $count): array
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

            // The first non-whitespace, non-symbol after 'enum' should be the name
            return [$text, $i + 1];
        }

        return ['', $i];
    }

    /**
     * @param  mixed  $tok
     */
    private function tokText($tok): string
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
     * @param  mixed  $tok
     */
    private function tokIs($tok, int $id): bool
    {
        if (is_object($tok) && method_exists($tok, 'is')) {
            return (bool) $tok->is($id);
        }

        return false;
    }

    /**
     * @param  ReflectionEnum<UnitEnum>  $reflection
     */
    private function shouldInclude(ReflectionEnum $reflection): bool
    {
        $short = $reflection->getShortName();
        $fqcn = $reflection->getName();

        $excludedEnums = array_map(static fn ($v) => mb_strtolower($v), $this->config->excludes);

        return ! in_array(mb_strtolower($short), $excludedEnums, true)
            && ! in_array(mb_strtolower($fqcn), $excludedEnums, true);
    }
}
