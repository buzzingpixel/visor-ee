<?php

namespace buzzingpixel\visor\interfaces;

/**
 * Interface ViewInterface
 */
interface ViewInterface
{
    /**
     * Renders a view
     * @param string $viewFile
     * @param array $vars
     * @return string
     */
    public function renderView($viewFile, array $vars = []);
}
