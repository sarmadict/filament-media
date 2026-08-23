<?php

namespace Sarmadict\FilamentMedia\Exceptions;

use RuntimeException;

class MediaInUseException extends RuntimeException
{
    /**
     * @param  list<array{label: string, count: int}>  $usages
     */
    public function __construct(public readonly array $usages)
    {
        parent::__construct('The media file is currently in use.');
    }
}
