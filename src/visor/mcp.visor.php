<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use buzzingpixel\visor\interfaces\RequestInterface;
use buzzingpixel\visor\controllers\EntryListController;
use buzzingpixel\visor\controllers\EntryRemoveController;

/**
 * Class Visor_mcp
 */
class Visor_mcp
{
    /** @var RequestInterface $requestService */
    private $requestService;

    /** @var EntryListController $entryListController */
    private $entryListController;

    /** @var EntryRemoveController $entryRemoveController */
    private $entryRemoveController;

    /**
     * Visor_mcp constructor
     */
    public function __construct()
    {
        $this->requestService = ee('visor:RequestService');
        $this->entryListController = ee('visor:EntryListController');
        $this->entryRemoveController = ee('visor:EntryRemoveController');
    }

    /**
     * Displays the index page
     * @return array
     */
    public function index()
    {
        if ($this->requestService->server('REQUEST_METHOD') === 'post') {
            $this->entryRemoveController->run();
            exit();
        }

        return $this->entryListController->run();
    }
}
