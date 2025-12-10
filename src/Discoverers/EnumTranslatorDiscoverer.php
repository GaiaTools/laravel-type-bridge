<?php

namespace GaiaTools\TypeBridge\Discoverers;

use GaiaTools\TypeBridge\Attributes\GenerateTranslator;
use GaiaTools\TypeBridge\Config\EnumTranslatorDiscoveryConfig;
use GaiaTools\TypeBridge\Contracts\Discoverer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpToken;
use ReflectionEnum;
use SplFileInfo;

final class EnumTranslatorDiscoverer implements Discoverer
{
    public function __construct(
        private readonly EnumTranslatorDiscoveryConfig $config,
    ) {}

    public function discover(): Collection
    {
        $paths = collect($this->config->discoveryPaths);

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

        /** @var Collection<int, array{reflection: ReflectionEnum, translationKey: string}> */
        $result = $classes
            ->filter(fn (string $class) => enum_exists($class))
            ->map(fn (string $enumClass) => new ReflectionEnum($enumClass))
            ->filter(fn (ReflectionEnum $ref) => $this->shouldInclude($ref))
            ->map(function (ReflectionEnum $ref) {
                return [
                    'reflection' => $ref,
                    'translationKey' => $this->getTranslationKey($ref),
                ];
            })
            ->filter(fn (array $item) => $item['translationKey'] !== null)
            ->values();

        return $result;
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
     * @param  list<object>  $tokens
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

            // First non-whitespace, non-symbol after 'enum' is the name
            return [$text, $i + 1];
        }

        return ['', $i];
    }

    private function tokText(mixed $tok): string
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

    private function tokIs(mixed $tok, int $id): bool
    {
        if (is_object($tok) && method_exists($tok, 'is')) {
            return (bool) $tok->is($id);
        }

        return false;
    }

    private function shouldInclude(ReflectionEnum $reflection): bool
    {
        $short = $reflection->getShortName();
        $fqcn = $reflection->getName();

        $excludedEnums = array_map(
            static fn ($v) => mb_strtolower($v),
            $this->config->excludes
        );

        return !in_array(mb_strtolower($short), $excludedEnums, true)
            && !in_array(mb_strtolower($fqcn), $excludedEnums, true);
    }

    private function getTranslationKey(ReflectionEnum $reflection): ?string
    {
        $attributes = $reflection->getAttributes(GenerateTranslator::class);

        if (!empty($attributes)) {
            $attribute = $attributes[0]->newInstance();

            if (!$attribute->generateComposable) {
                return null;
            }

            if ($attribute->translationKey) {
                return $attribute->translationKey;
            }
        }

        // Convention: Use class basename as translation key
        return $reflection->getShortName();
    }
}
