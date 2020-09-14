<?php

declare(strict_types=1);

namespace atk4\dsql\Oracle;

/**
 * Perform query operation on Oracle server.
 */
class Query extends AbstractQuery
{
    // {{{ for Oracle 11 and lower to support LIMIT with OFFSET

    protected $template_select = '[with]select[option] [field] [from] [table][join][where][group][having][order]';
    protected $template_select_limit = 'select * from (select "__t".*, rownum "__dsql_rownum" [from] ([with]select[option] [field] [from] [table][join][where][group][having][order]) "__t") where "__dsql_rownum" > [limit_start][and_limit_end]';

    public function limit($cnt, $shift = null)
    {
        // This is for pre- 12c version
        $this->template_select = $this->template_select_limit;

        return parent::limit($cnt, $shift);
    }

    /**
     * Renders [limit_start].
     *
     * @return string rendered SQL chunk
     */
    public function _render_limit_start()
    {
        return (int) $this->args['limit']['shift'];
    }

    /**
     * Renders [and_limit_end].
     *
     * @return string rendered SQL chunk
     */
    public function _render_and_limit_end()
    {
        if (!$this->args['limit']['cnt']) {
            return '';
        }

        return ' and "__dsql_rownum" <= ' .
            max((int) ($this->args['limit']['cnt'] + $this->args['limit']['shift']), (int) $this->args['limit']['cnt']);
    }

    public function getIterator(): iterable
    {
        foreach (parent::getIterator() as $row) {
            unset($row['__dsql_rownum']);

            yield $row;
        }
    }

    public function get(): array
    {
        return array_map(function ($row) {
            unset($row['__dsql_rownum']);

            return $row;
        }, parent::get());
    }

    public function getRow(): ?array
    {
        $row = parent::getRow();

        if ($row !== null) {
            unset($row['__dsql_rownum']);
        }

        return $row;
    }

    /// }}}
}
