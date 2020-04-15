<?php

namespace atk4\dsql;

/**
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
interface Expressionable
{
    public function getDSQLExpression($expression);
}
