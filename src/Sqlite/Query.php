<?php

declare(strict_types=1);

namespace atk4\dsql\Sqlite;

use atk4\dsql\Query as BaseQuery;

/**
 * Perform query operation on SQLite server.
 */
class Query extends BaseQuery
{
    protected $template_truncate = 'delete [from] [table_noalias]';

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('group_concat({}, [])', [$field, $delimeter]);
    }
}
