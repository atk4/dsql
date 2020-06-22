<?php

declare(strict_types=1);

namespace atk4\dsql;

if (!class_exists(\SebastianBergmann\CodeCoverage\CodeCoverage::class, false)) {
    'trigger_error'('Use atk4\dsql\Postgresql\Connection instead', E_USER_DEPRECATED);
}

/**
 * @deprecated use Postgresql\Connection instead - will be removed dec-2020
 */
class Connection_PgSQL extends Postgresql\Connection
{
}
