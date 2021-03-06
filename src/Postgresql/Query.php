<?php

declare(strict_types=1);

namespace Atk4\Dsql\Postgresql;

use Atk4\Dsql\Query as BaseQuery;

class Query extends BaseQuery
{
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

    public function groupConcat($field, string $delimiter = ',')
    {
        return $this->expr('string_agg({}, [])', [$field, $delimiter]);
    }
}
