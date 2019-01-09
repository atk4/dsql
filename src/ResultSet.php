<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * This interface describes a class, which can represents a result set. Some operations can be performed with this, such as
 * counting or adding conditions. This is implemented by Expression but more importantly ATK4 Data adds more implementations
 * for the ResultSet which implement NoSQL actions (such as on arrays etc).
 */
interface ResultSet
{
    //public function where($field, $value);
    //public function count();
    public function get();

    public function getRow();

    public function getOne();
}
