<?php

declare(strict_types=1);

namespace Atk4\Dsql;

interface Expressionable
{
    public function getDsqlExpression($expression);
}
