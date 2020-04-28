<?php

namespace atk4\dsql;

interface Expressionable
{
    public function getDSQLExpression($expression);
}
