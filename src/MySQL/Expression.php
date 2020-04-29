<?php

namespace atk4\dsql\MySQL;

use atk4\dsql\Expression as BaseExpression;

/**
 * Perform query operation on MySQL server.
 */
class Expression extends BaseExpression
{
    /**
     * Field, table and alias name quoted identifier.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $quotedIdentifier = '`';
}
