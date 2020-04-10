<?php

namespace atk4\dsql;

class ExecuteException extends Exception
{
    public function getErrorMessage(): string
    {
        return $this->getParams()['error'];
    }

    public function getDebugQuery(): string
    {
        return $this->getParams()['query'];
    }
}
