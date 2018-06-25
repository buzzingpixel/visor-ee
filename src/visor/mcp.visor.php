<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use buzzingpixel\visor\controllers\EntryListController;

/**
 * Class Visor_mcp
 */
class Visor_mcp
{
    /** @var EntryListController $entryListController */
    private $entryListController;

    /**
     * Visor_mcp constructor
     */
    public function __construct()
    {
        $this->entryListController = ee('visor:EntryListController');
    }

    /**
     * Displays the index page
     * @return array
     */
    public function index()
    {
        return $this->entryListController->run();

        // if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
        //     $this->deletePostedEntries();
        //     exit();
        // }
    }
}
