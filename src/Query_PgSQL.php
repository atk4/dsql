<?php

declare(strict_types=1);

namespace atk4\dsql;

if (!class_exists(\SebastianBergmann\CodeCoverage\CodeCoverage::class, false)) {
    'trigger_error'('Use atk4\dsql\PgSQL\Query instead', E_USER_DEPRECATED);
}

/**
 * @deprecated use PgSQL\Query instead - will be removed dec-2020
 */
class Query_PgSQL extends PgSQL\Query
{
}
