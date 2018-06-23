<?php

namespace buzzingpixel\visor\facades;

use \EllisLab\ExpressionEngine\Library\CP\Table;
use buzzingpixel\visor\interfaces\TableInterface;

/**
 * Class TableFacade
 */
class TableFacade implements TableInterface
{
    /** @var string $noResultsText */
    private $noResultsText;

    /** @var array */
    private $columns = [];

    /** @var array $data */
    private $data = [];

    /** @var array $actionButtons */
    private $actionButtons = [];

    /** @var string $actionContent */
    private $actionContent = '';

    /**
     * Sets the no results text
     * @param string $text
     * @return self
     */
    public function noResultsText($text)
    {
        $this->noResultsText = $text;
        return $this;
    }

    /**
     * Sets the columns
     * @param array $columns
     * @return self
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Sets the table data
     * @param array $data
     * @return self
     */
    public function data(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Some tables need a "tbl-action" row with <a> "buttons". This method will
     * allow for them to be added, and will render the row with the correct
     * colspan.
     * @param string $url The url to use for the href="" attribute
     * @param string $text The text to use for the button
     * @param string $class An additional class string to add to the class
     *   attribute of the <a> tag.
     * @return self
     */
    public function addActionButton($url, $text, $class = 'submit')
    {
        $this->actionButtons[] = compact('url', 'text', 'class');
        return $this;
    }

    /**
     * Some tables need a "tbl-action" row non-button content. This method will
     * allow for them to be added, and will render the row with the correct
     * colspan.
     * @param string $content The content to append
     * @return void
     */
    public function addActionContent($content)
    {
        $this->actionContent .= $content;
    }

    /**
     * Gets the view data
     * @param string $baseUrl
     * @return array
     */
    public function getViewData($baseUrl = null)
    {
        /** @var Table $table */
        $table = ee('CP/Table');

        if ($this->noResultsText) {
            $table->setNoResultsText($this->noResultsText);
        }

        $table->setColumns($this->columns);

        $table->setData($this->data);

        foreach ($this->actionButtons as $actionButton) {
            $this->addActionButton(
                $actionButton['url'],
                $actionButton['text'],
                $actionButton['class']
            );
        }

        if ($this->actionContent) {
            $this->addActionContent($this->actionContent);
        }

        return $table->viewData($baseUrl);
    }
}
