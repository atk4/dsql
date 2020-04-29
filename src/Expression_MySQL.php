<?php

declare(strict_types=1);

namespace atk4\dsql;

/**
 * Perform query operation on MySQL server.
 */
class Expression_MySQL extends Expression
{
    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '`';
}
