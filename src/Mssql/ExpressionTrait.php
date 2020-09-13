<?php

declare(strict_types=1);

namespace atk4\dsql\Mssql;

trait ExpressionTrait
{
    private function fixOpenEscapeChar(string $v)
    {
        return preg_replace('~(?:\'(?:\'\'|\\\\\'|[^\'])*\')?+\K\]([^\[\]\'"(){}]*?)\]~s', '[$1]', $v);
    }

    protected function _escapeSoft(string $value): string
    {
        return $this->fixOpenEscapeChar(parent::_escapeSoft($value));
    }

    protected function _escape(string $value): string
    {
        return $this->fixOpenEscapeChar(parent::_escape($value));
    }

    // {{{ MSSQL does not support named parameters, so convert them to numerical inside execute

    private $paramsBackup = [];
    private $fixedRender;

    public function execute(object $connection = null)
    {
        $this->paramsBackup = $this->params;
        try {
            $numParams = [];
            $i = 0;
            $j = 0;
            $this->fixedRender = preg_replace_callback(
                '~(?:\'(?:\'\'|\\\\\'|[^\'])*\')?+\K(?:\?|:\w+)~s',
                function ($matches) use (&$numParams, &$i, &$j) {
                    $numParams[++$i] = $this->params[$matches[0] === '?' ? ++$j : $matches[0]];

                    return '?';
                },
                parent::render()
            );
            $this->params = $numParams;

            return parent::execute($connection);
        } finally {
            $this->params = $this->paramsBackup;
            $this->fixedRender = null;
        }
    }

    public function render()
    {
        if ($this->fixedRender !== null) {
            return $this->fixedRender;
        }

        return parent::render();
    }

    public function getDebugQuery(): string
    {
        $this->params = $this->paramsBackup;
        $this->fixedRender = null;

        return parent::getDebugQuery();
    }

    /// }}}
}
