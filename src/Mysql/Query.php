<?php

declare(strict_types=1);

namespace atk4\dsql\Mysql;

use atk4\dsql\Query as BaseQuery;

/**
 * Perform query operation on MySQL server.
 */
class Query extends BaseQuery
{
    protected $escape_char = '`';

    /** @var string Expression classname */
    protected $expression_class = Expression::class;

    protected $template_update = 'update [table][join] set [set] [where]';

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('group_concat({} separator [])', [$field, $delimeter]);
    }
}
