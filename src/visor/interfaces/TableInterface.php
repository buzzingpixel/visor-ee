<?php

namespace buzzingpixel\visor\interfaces;

/**
 * Interface TableInterface
 */
interface TableInterface
{
    /**
     * Sets the no results text
     * @param string $text
     * @return self
     */
    public function noResultsText($text);

    /**
     * Sets the columns
     * @param array $columns
     * @return self
     */
    public function columns(array $columns);

    /**
     * Sets the table data
     * @param array $data
     * @return self
     */
    public function data(array $data);

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
    public function addActionButton($url, $text, $class = 'submit');

    /**
     * Some tables need a "tbl-action" row non-button content. This method will
     * allow for them to be added, and will render the row with the correct
     * colspan.
     * @param string $content The content to append
     * @return void
     */
    public function addActionContent($content);

    /**
     * Gets the view data
     * @param string $baseUrl
     * @return array
     */
    public function getViewData($baseUrl = null);
}
