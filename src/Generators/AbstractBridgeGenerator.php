<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Generators;

use GaiaTools\TypeBridge\Config\GeneratorConfig;
use GaiaTools\TypeBridge\Contracts\BridgeGenerator;
use GaiaTools\TypeBridge\Contracts\Discoverer;
use GaiaTools\TypeBridge\Contracts\OutputFormatter;
use GaiaTools\TypeBridge\Contracts\Transformer;
use GaiaTools\TypeBridge\ValueObjects\GeneratedFile;
use GaiaTools\TypeBridge\Writers\GeneratedFileWriter;
use Illuminate\Support\Collection;

abstract class AbstractBridgeGenerator implements BridgeGenerator
{
    public function __construct(
        protected readonly Discoverer $discoverer,
        protected readonly Transformer $transformer,
        protected readonly OutputFormatter $formatter,
        protected readonly GeneratedFileWriter $writer,
    ) {}

    public function generate(): Collection
    {
        $discovered = $this->discoverer->discover();

        return $this->generateFrom($discovered);
    }

    abstract protected function buildFilePath(mixed $transformed): string;

    /**
     * @template TDiscovered
     *
     * @param  Collection<int, TDiscovered>  $discovered
     * @return Collection<int, GeneratedFile>
     */
    protected function generateFrom(Collection $discovered): Collection
    {
        return $discovered->map(function (mixed $item) {
            $transformed = $this->transformer->transform($item);
            $formatted = $this->formatter->format($transformed);

            $filePath = $this->buildFilePath($transformed);
            $content = $this->wrapContent($formatted);

            $generatedFile = new GeneratedFile($filePath, $content);
            $this->writer->write($generatedFile);

            return $generatedFile;
        });
    }

    protected function wrapContent(string $content): string
    {
        // Split formatted content into actual lines before processing max-len
        // Normalize newlines to "\n" then explode; trim trailing newlines to avoid extra blank line at EOF
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $normalized = rtrim($normalized, "\n");
        $contentLines = explode("\n", $normalized);

        $header = [];

        if ($this->formatter->getExtension() !== 'json') {
            $header = GeneratedFile::GENERATED_FILE_WARNING;
        }

        // Prepend the generated file multi-line warning and a blank line
        $lines = array_merge($header, [''], $contentLines);

        // Read max line length from config; allow disabling via <= 0
        $config = GeneratorConfig::fromConfig();
        $maxLen = $config->maxLineLength > 0 ? $config->maxLineLength : null;

        return GeneratedFile::fromLines('temp', $lines, $maxLen)->contents;
    }
}
