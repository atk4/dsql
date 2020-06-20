<?php

declare(strict_types=1);

namespace atk4\dsql;

@trigger_error('Use atk4\dsql\Oracle\AbstractQuery instead', E_USER_DEPRECATED);

/**
 * @deprecated use Oracle\AbstractQuery instead - will be removed dec-2020
 */
abstract class Query_Oracle_Abstract extends Oracle\AbstractQuery
{
}
