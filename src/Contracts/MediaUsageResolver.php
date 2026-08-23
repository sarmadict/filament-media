<?php

namespace Sarmadict\FilamentMedia\Contracts;

use Sarmadict\FilamentMedia\Models\MediaFile;

interface MediaUsageResolver
{
    /** @return list<array{label: string, count: int}> */
    public function usages(MediaFile $media): array;
}
