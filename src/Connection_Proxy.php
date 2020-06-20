<?php

declare(strict_types=1);

namespace atk4\dsql;

@trigger_error('Use atk4\dsql\ProxyConnection instead', E_USER_DEPRECATED);

/**
 * @deprecated use ProxyConnection instead - will be removed dec-2020
 */
class Connection_Proxy extends ProxyConnection
{
}
