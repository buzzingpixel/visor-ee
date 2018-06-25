<?php

namespace buzzingpixel\visor\facades;

use buzzingpixel\visor\interfaces\CpUrlInterface;
use EllisLab\ExpressionEngine\Library\CP\URL as UrlObject;
use EllisLab\ExpressionEngine\Service\URL\URLFactory as EECpUrlFactory;

/**
 * Class CpUrlFacade
 */
class CpUrlFacade implements CpUrlInterface
{
    /** @var EECpUrlFactory $eeCpUrlFactory */
    private $eeCpUrlFactory;

    /**
     * CpUrlFacade constructor
     * @param EECpUrlFactory $eeCpUrlFactory
     */
    public function __construct(EECpUrlFactory $eeCpUrlFactory)
    {
        $this->eeCpUrlFactory = $eeCpUrlFactory;
    }

    /**
     * Creates a CP URL string
     * @param $path
     * @param array $query
     * @return string
     */
    public function renderUrl($path, array $query = [])
    {
        return $this->getUrlObject($path, $query)->compile();
    }

    /**
     * Gets a URL object
     * @param $path
     * @param array $query
     * @return UrlObject
     */
    public function getUrlObject($path, array $query = [])
    {
        return $this->eeCpUrlFactory->make($path, $query);
    }
}
