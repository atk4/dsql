<?php

declare(strict_types=1);

namespace atk4\dsql;

@trigger_error('Use atk4\dsql\Oracle\Version12\Connection instead', E_USER_DEPRECATED);

/**
 * @deprecated use Oracle\Version12\Connection instead - will be removed dec-2020
 */
class Connection_Oracle12 extends Oracle\Version12\Connection
{
}
