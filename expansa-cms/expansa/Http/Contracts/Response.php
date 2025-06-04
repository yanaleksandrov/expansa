<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

interface Response
{
    public function getContent(): ?string;
}
