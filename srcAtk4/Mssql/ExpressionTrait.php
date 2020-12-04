<?php

declare(strict_types=1);

namespace Atk4\Dsql\Mssql;

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

    private $numQueryParamsBackup;
    private $numQueryRender;

    public function execute(object $connection = null)
    {
        if ($this->numQueryParamsBackup !== null) {
            return parent::execute($connection);
        }

        $this->numQueryParamsBackup = $this->params;
        try {
            $numParams = [];
            $i = 0;
            $j = 0;
            $this->numQueryRender = preg_replace_callback(
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
            $this->params = $this->numQueryParamsBackup;
            $this->numQueryParamsBackup = null;
            $this->numQueryRender = null;
        }
    }

    public function render()
    {
        if ($this->numQueryParamsBackup !== null) {
            return $this->numQueryRender;
        }

        return parent::render();
    }

    public function getDebugQuery(): string
    {
        if ($this->numQueryParamsBackup === null) {
            return parent::getDebugQuery();
        }

        $paramsBackup = $this->params;
        $numQueryRenderBackupBackup = $this->numQueryParamsBackup;
        $numQueryRenderBackup = $this->numQueryRender;
        try {
            $this->params = $this->numQueryParamsBackup;
            $this->numQueryParamsBackup = null;
            $this->numQueryRender = null;

            return parent::getDebugQuery();
        } finally {
            $this->params = $paramsBackup;
            $this->numQueryParamsBackup = $numQueryRenderBackupBackup;
            $this->numQueryRender = $numQueryRenderBackup;
        }
    }

    /// }}}
}
