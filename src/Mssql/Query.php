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

    protected $escape_char = ']';

    protected $expression_class = Expression::class;

    protected $template_insert = 'begin try'
        . "\n" . 'insert[option] into [table_noalias] ([set_fields]) values ([set_values])'
        . "\n" . 'end try begin catch if ERROR_NUMBER() = 544 begin'
        . "\n" . 'set IDENTITY_INSERT [table_noalias] on'
        . "\n" . 'insert[option] into [table_noalias] ([set_fields]) values ([set_values])'
        . "\n" . 'set IDENTITY_INSERT [table_noalias] off'
        . "\n" . 'end end  catch';

    public function _render_limit()
    {
        if (isset($this->args['limit'])) {
            $cnt = (int) $this->args['limit']['cnt'];
            $shift = (int) $this->args['limit']['shift'];

            return (!isset($this->args['order']) ? ' order by (select null)' : '')
                . ' offset ' . $shift . ' rows'
                . ' fetch next ' . $cnt . ' rows only';
        }
    }

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('string_agg({}, \'' . $delimeter . '\')', [$field]);
    }
}
