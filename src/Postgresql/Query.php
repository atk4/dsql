<?php

declare(strict_types=1);

namespace atk4\dsql\Postgresql;

use atk4\dsql\Query as BaseQuery;

class Query extends BaseQuery
{
    protected $escape_char = '"';

    protected $template_update = 'update [table][join] set [set] [where]';
    protected $template_replace;

    public function _render_limit()
    {
        if (isset($this->args['limit'])) {
            return ' limit ' .
                (int) $this->args['limit']['cnt'] .
                ' offset ' .
                (int) $this->args['limit']['shift'];
        }
    }

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('string_agg({}, [])', [$field, $delimeter]);
    }
}
