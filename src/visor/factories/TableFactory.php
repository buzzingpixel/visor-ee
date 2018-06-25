<?php

namespace buzzingpixel\visor\factories;

use buzzingpixel\visor\facades\TableFacade;
use buzzingpixel\visor\interfaces\TableInterface;

/**
 * Class TableFacadeFactory
 */
class TableFactory
{
    /**
     * Gets an instance of TableInterface
     * @return TableInterface
     */
    public function __invoke()
    {
        return $this->get();
    }

    /**
     * Gets an instance of TableInterface
     * @return TableInterface
     */
    public function get()
    {
        return new TableFacade();
    }
}
