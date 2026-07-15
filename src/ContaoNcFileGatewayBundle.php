<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoNcFileGatewayBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
