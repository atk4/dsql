<?php

namespace atk4\dsql;

class Query
{
    public function table($table){
        return (boolean)$table;
    }

    public function field($field){
        return (boolean)$field;
    }
}
