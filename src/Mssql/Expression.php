<?php

declare(strict_types=1);

namespace atk4\dsql\Mssql;

use atk4\dsql\Expression as BaseExpression;

/**
 * Perform query operation on MSSQL server.
 */
class Expression extends BaseExpression
{
    use ExpressionTrait;

    /**
     * Field, table and alias name escaping symbol.
     *
     * @var string
     */
    protected $escape_char = ']';
}
