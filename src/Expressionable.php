<?php

declare(strict_types=1);

namespace atk4\dsql;

interface Expressionable
{
    public function getDsqlExpression($expression);
}
