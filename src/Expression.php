<?php

namespace atk4\dsql;

/**
 * Implement this interface in your class if it can be used as a part of a
 * query. Implement getDSQLExpression method that would return a valid
 * Query object (or string);
 */
interface Expression {
    function getDSQLExpression();
}
