<?php

declare(strict_types=1);

namespace atk4\dsql;

@trigger_error('Use atk4\dsql\Oracle\Version12\Query instead', E_USER_DEPRECATED);

/**
 * @deprecated use Oracle\Version12\Query instead - will be removed dec-2020
 */
class Query_Oracle12c extends Oracle\Version12\Query
{
}
