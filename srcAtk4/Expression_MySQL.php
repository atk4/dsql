<?php

declare(strict_types=1);

namespace Atk4\Dsql;

if (!class_exists(\SebastianBergmann\CodeCoverage\CodeCoverage::class, false)) {
    'trigger_error'('Use atk4\dsql\Mysql\Expression instead', E_USER_DEPRECATED);
}

/**
 * @deprecated use Mysql\Expression instead - will be removed dec-2020
 */
class Expression_MySQL extends Mysql\Expression
{
}
