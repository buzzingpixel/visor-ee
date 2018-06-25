<?php

namespace buzzingpixel\visor\interfaces;

use EllisLab\ExpressionEngine\Library\CP\URL as UrlObject;

/**
 * Interface CpUrlInterface
 */
interface CpUrlInterface
{
    /**
     * Creates a CP URL string
     * @param $path
     * @param array $query
     * @return string
     */
    public function renderUrl($path, array $query = []);

    /**
     * Gets a URL object
     * @param $path
     * @param array $query
     * @return UrlObject
     */
    public function getUrlObject($path, array $query = []);
}
