<?php

namespace buzzingpixel\visor\facades;

use buzzingpixel\visor\interfaces\RequestInterface;

/**
 * Class RequestFacade
 */
class RequestFacade implements RequestInterface
{
    /** @var \EE_Input $eeInput */
    private $eeInput;

    /**
     * RequestFacade constructor
     * @param \EE_Input $eeInput
     */
    public function __construct(\EE_Input $eeInput)
    {
        $this->eeInput = $eeInput;
    }

    /**
     * Retrieves a key from the $_GET array
     * @param string $key
     * @param mixed $defaultVal
     * @param bool $xssClean
     * @return mixed
     */
    public function get($key, $defaultVal = null, $xssClean = true)
    {
        return $this->eeInput->get($key, $xssClean) ?: $defaultVal;
    }

    /**
     * Retrieves a key from the $_POST array
     * @param $key
     * @param null $defaultVal
     * @param bool $xssClean
     * @return mixed
     */
    public function post($key, $defaultVal = null, $xssClean = true)
    {
        return $this->eeInput->post($key, $xssClean) ?: $defaultVal;
    }

    /**
     * Retrieves a key from the $_GET or $_POST array
     * @param $key
     * @param null $defaultVal
     * @param bool $xssClean
     * @return mixed
     */
    public function getPost($key, $defaultVal = null, $xssClean = true)
    {
        return $this->eeInput->get_post($key, $xssClean) ?: $defaultVal;
    }

    /**
     * Retrieves a key from the $_SERVER
     * @param $key
     * @param null $defaultVal
     * @param bool $lowerCase
     * @param bool $xssClean
     * @return mixed
     */
    public function server($key, $defaultVal = null, $lowerCase = true, $xssClean = true)
    {
        $val = $this->eeInput->server($key, $xssClean);

        if ($val && $lowerCase) {
            $val = strtolower($val);
        }

        return $val ?: $defaultVal;
    }

    /**
     * Checks if request is ajax
     * @return mixed
     */
    public function isAjaxRequest()
    {
        return $this->eeInput->is_ajax_request();
    }
}
