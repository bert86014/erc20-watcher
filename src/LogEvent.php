<?php

namespace Leonis\ERC20Watcher;

class LogEvent
{
    public $log;

    public function __construct($log)
    {
        $this->log = new Log($log);
    }
}