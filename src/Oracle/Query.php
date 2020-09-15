<?php

declare(strict_types=1);

namespace atk4\dsql\Oracle;

/**
 * Perform query operation on Oracle server.
 */
class Query extends AbstractQuery
{
    /**
     * SELECT template.
     *
     * Default [limit] syntax not supported. Add rownum implementation instead.
     *
     * @var string
     */
    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order]';
    protected $template_select_limit = 'select * from (select rownum "__dsql_rownum","__t".* [from] (select[option] [field] [from] [table][join][where][group][having][order]) "__t") where "__dsql_rownum">[limit_start][and_limit_end]';

    /**
     * Limit how many rows will be returned.
     *
     * @param int $cnt   Number of rows to return
     * @param int $shift Offset, how many rows to skip
     *
     * @return $this
     */
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

        return ' and "__dsql_rownum"<=' .
            ((int) ($this->args['limit']['cnt'] + $this->args['limit']['shift']));
    }

    public function exists()
    {
        return $this->dsql()->mode('select')->field(
            $this->dsql()->expr('case when exists[] then 1 else 0 end', [$this])
        );
    }
}
