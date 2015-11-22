<?php

namespace ATP\View\Helper;

class Partial extends \ATP\View\Helper
{
    protected $template = null;

    public function __invoke($vars = array())
    {
        return $this->getView()->partial($this->template, $vars);
    }
}