<?php

namespace atk4\dsql\Oracle;

/**
 * Perform query operation on Oracle server.
 */
class Query extends AbstractOracleQuery
{
    /**
     * SELECT template.
     *
     * Default [limit] syntax not supported. Add rownum implementation instead.
     *
     * @var string
     */
    protected $templateSelect = 'select[option] [field] [from] [table][join][where][group][having][order]';
    protected $templateSelectLimit = 'select * from (select rownum "__dsql_rownum","__t".* [from] (select[option] [field] [from] [table][join][where][group][having][order]) "__t") where "__dsql_rownum">[limit_start][and_limit_end]';

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
        $this->templateSelect = $this->templateSelectLimit;

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
}
