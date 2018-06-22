<?php

namespace buzzingpixel\visor\services;

use buzzingpixel\visor\interfaces\RequestInterface;

/**
 * Class FiltersFromInputService
 */
class FiltersFromInputService
{
    /** @var RequestInterface $requestService */
    private $requestService;

    /**
     * FiltersFromInputService constructor
     * @param RequestInterface $requestService
     */
    public function __construct(RequestInterface $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * Gets the filters
     * @return array(
     *     'channels' => array(),
     *     'standard' => array(),
     * )
     */
    public function __invoke()
    {
        return $this->get();
    }

    /**
     * Gets the filters
     * @return array(
     *     'channels' => array(),
     *     'standard' => array(),
     * )
     */
    public function get()
    {
        $filters = $this->requestService->get('filter', []);

        if (! is_array($filters)) {
            $filters = [];
        }

        $channels = [];
        $standard = [];

        foreach ($filters as $key => $filter) {
            if (! isset($filter['type'], $filter['value'])) {
                continue;
            }

            if ($filter['type'] !== 'channel') {
                $standard[] = $filter;
                continue;
            }

            $channels[$filter['value']] = $filter['value'];

            unset($filters[$key]);
        }

        $channels = array_values($channels);

        return compact('channels', 'standard');
    }
}
