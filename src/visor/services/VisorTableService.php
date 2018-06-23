<?php

namespace buzzingpixel\visor\services;

/**
 * Class VisorTableService
 */
class VisorTableService
{
    /**
     * Gets view data
     * @return array
     */
    public function __invoke()
    {
        return $this->getViewData();
    }

    /**
     * Gets view data
     * @return array
     */
    public function getViewData()
    {
        return $this->populateTableData(
            $this->createTable(),
            $this->getEntryModelCollection()
        );
    }
}
