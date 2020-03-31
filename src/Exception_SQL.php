<?php

namespace atk4\dsql;

class Exception_SQL extends Exception
{
    public function getErrorMessage(): string
    {
        return $this->getParams('error');
    }

    public function getDebugQuery(): string
    {
        return $this->getParams('query');
    }
}
