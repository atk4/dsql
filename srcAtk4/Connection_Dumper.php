<?php

declare(strict_types=1);

namespace Atk4\Dsql;

if (!class_exists(\SebastianBergmann\CodeCoverage\CodeCoverage::class, false)) {
    'trigger_error'('Use atk4\dsql\Debug\Stopwatch\Connection instead', E_USER_DEPRECATED);
}

/**
 * @deprecated will be removed in dec-2020
 */
class Connection_Dumper extends Debug\Stopwatch\Connection
{
}
