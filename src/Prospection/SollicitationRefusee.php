<?php

namespace App\Prospection;

final class SollicitationRefusee extends \RuntimeException
{
    /** @param list<string> $raisons */
    public function __construct(public readonly array $raisons)
    {
        parent::__construct(implode(' ', $raisons));
    }
}
