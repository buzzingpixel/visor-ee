<?php

namespace buzzingpixel\visor\interfaces;

/**
 * Interface RequestInterface
 */
interface RequestInterface
{
    /**
     * Retrieves a key from the $_GET array
     * @param string $key
     * @param mixed $defaultVal
     * @param bool $xssClean
     * @return mixed
     */
    public function get($key, $defaultVal = null, $xssClean = true);

    /**
     * Retrieves a key from the $_POST array
     * @param $key
     * @param null $defaultVal
     * @param bool $xssClean
     * @return mixed
     */
    public function post($key, $defaultVal = null, $xssClean = true);

    /**
     * Retrieves a key from the $_GET or $_POST array
     * @param $key
     * @param null $defaultVal
     * @param bool $xssClean
     * @return mixed
     */
    public function getPost($key, $defaultVal = null, $xssClean = true);

    /**
     * Retrieves a key from the $_SERVER
     * @param $key
     * @param null $defaultVal
     * @param bool $lowerCase
     * @param bool $xssClean
     * @return mixed
     */
    public function server($key, $defaultVal = null, $lowerCase = true, $xssClean = true);

    /**
     * Checks if request is ajax
     * @return mixed
     */
    public function isAjaxRequest();
}
