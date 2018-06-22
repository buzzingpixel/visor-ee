<?php

namespace buzzingpixel\visor\interfaces;

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
}
