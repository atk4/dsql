<?php

declare(strict_types=1);

namespace atk4\dsql;

if (!class_exists(\SebastianBergmann\CodeCoverage\CodeCoverage::class, false)) {
    'trigger_error'('Use atk4\dsql\Oracle\Connection instead', E_USER_DEPRECATED);
}

/**
 * @deprecated use Oracle\Connection instead - will be removed dec-2020
 */
class Connection_Oracle extends Oracle\Connection
{
}
