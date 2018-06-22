<?php

namespace buzzingpixel\visor\facades;

use buzzingpixel\visor\interfaces\ViewInterface;
use EllisLab\ExpressionEngine\Service\View\ViewFactory as EEViewFactory;

/**
 * Class ViewFacade
 */
class ViewFacade implements ViewInterface
{
    /** @var EEViewFactory $eeViewFactory */
    private $eeViewFactory;

    /**
     * ViewFacade constructor
     * @param EEViewFactory $eeViewFactory
     */
    public function __construct(EEViewFactory $eeViewFactory)
    {
        $this->eeViewFactory = $eeViewFactory;
    }

    /**
     * Renders a view
     * @param string $viewFile
     * @param array $vars
     * @return string
     */
    public function renderView($viewFile, array $vars = [])
    {
        return $this->eeViewFactory->make($viewFile)->render($vars);
    }
}
