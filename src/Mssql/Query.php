<?php

declare(strict_types=1);

namespace atk4\dsql\Mssql;

use atk4\dsql\Query as BaseQuery;

/**
 * Perform query operation on MSSQL server.
 */
class Query extends BaseQuery
{
    use ExpressionTrait;

    /**
     * Field, table and alias name escaping symbol.
     *
     * @var string
     */
    protected $escape_char = ']';

    /** @var string Expression classname */
    protected $expression_class = Expression::class;
}
