<?php

declare(strict_types=1);

namespace Atk4\Dsql;

if (!class_exists(\SebastianBergmann\CodeCoverage\CodeCoverage::class, false)) {
    'trigger_error'('Use atk4\dsql\Oracle\Query instead', E_USER_DEPRECATED);
}

/**
 * @deprecated use Oracle\Query instead - will be removed dec-2020
 */
class Query_Oracle extends Oracle\Query
{
}
