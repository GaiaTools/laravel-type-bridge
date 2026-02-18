<?php

declare(strict_types=1);

namespace GaiaTools\TypeBridge\Generators;

use GaiaTools\TypeBridge\ValueObjects\TransformedEnum;
use Illuminate\Support\Collection;

final class EnumGenerator extends AbstractBridgeGenerator
{
    public function getName(): string
    {
        return 'enums';
    }

    /**
     * @param  Collection<int,mixed>  $discovered
     * @return Collection<int, \GaiaTools\TypeBridge\ValueObjects\GeneratedFile>
     */
    public function generateFor(Collection $discovered): Collection
    {
        return $this->generateFrom($discovered);
    }

    protected function buildFilePath(mixed $transformed): string
    {
        assert($transformed instanceof TransformedEnum);

        $filename = sprintf('%s.%s', $transformed->name, $this->formatter->getExtension());

        return sprintf('%s/%s', $transformed->outputPath, $filename);
    }
}
