<?php

declare(strict_types=1);

namespace atk4\dsql\Oracle;

use atk4\dsql\Query as BaseQuery;

abstract class AbstractQuery extends BaseQuery
{
    protected $escape_char = '"';

    /** @var string */
    protected $template_seq_currval = 'select [sequence].CURRVAL from dual';
    /** @var string */
    protected $template_seq_nextval = '[sequence].NEXTVAL';

    public function render()
    {
        if ($this->mode === 'select' && $this->main_table === null) {
            $this->table('DUAL');
        }

        return parent::render();
    }

    /**
     * Set sequence.
     *
     * @param string $sequence
     *
     * @return $this
     */
    public function sequence($sequence)
    {
        $this->args['sequence'] = $sequence;

        return $this;
    }

    public function _render_sequence()
    {
        return $this->args['sequence'];
    }

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('listagg({field}, []) within group (order by {field})', ['field' => $field, $delimeter]);
    }
}
