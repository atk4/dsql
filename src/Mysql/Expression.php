<?php

declare(strict_types=1);

namespace atk4\dsql\Mysql;

use atk4\dsql\Expression as BaseExpression;

/**
 * Perform query operation on MySQL server.
 */
class Expression extends BaseExpression
{
    protected $escape_char = '`';
}
