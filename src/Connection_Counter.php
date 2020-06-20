<?php

declare(strict_types=1);

namespace atk4\dsql;

@trigger_error('Use atk4\dsql\Debug\Profiler\Connection instead', E_USER_DEPRECATED);

/**
 * @deprecated will be removed in dec-2020
 */
class Connection_Counter extends Debug\Profiler\Connection
{
}
