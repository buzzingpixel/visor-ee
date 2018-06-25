<?php

namespace buzzingpixel\visor\controllers;

use buzzingpixel\visor\interfaces\ViewInterface;
use buzzingpixel\visor\interfaces\CpUrlInterface;
use buzzingpixel\visor\services\VisorTableService;
use buzzingpixel\visor\interfaces\RequestInterface;
use buzzingpixel\visor\services\FilterTypesService;
use buzzingpixel\visor\services\EntrySelectionService;
use buzzingpixel\visor\services\ChannelSelectsService;
use EllisLab\ExpressionEngine\Library\CP\URL as UrlObject;
use buzzingpixel\visor\services\FilteredChannelLinksService;

/**
 * Class EntryListController
 */
class EntryListController
{
    /** @var RequestInterface $requestService */
    private $requestService;

    /** @var ViewInterface $viewService */
    private $viewService;

    /** @var CpUrlInterface $cpUrlService */
    private $cpUrlService;

    /** @var ChannelSelectsService $channelSelectsService */
    private $channelSelectsService;

    /** @var FilteredChannelLinksService $filteredChannelLinksService */
    private $filteredChannelLinksService;

    /** @var FilterTypesService $filterTypesService */
    private $filterTypesService;

    /** @var VisorTableService $visorTableService */
    private $visorTableService;

    /** @var EntrySelectionService $entrySelectionService */
    private $entrySelectionService;

    /** @var array $filters */
    private $filters = [];

    /**
     * EntryListController constructor
     * @param RequestInterface $requestService
     * @param ViewInterface $viewService
     * @param CpUrlInterface $cpUrlService
     * @param ChannelSelectsService $channelSelectsService
     * @param FilteredChannelLinksService $filteredChannelLinksService
     * @param FilterTypesService $filterTypesService
     * @param VisorTableService $visorTableService
     * @param EntrySelectionService $entrySelectionService
     */
    public function __construct(
        RequestInterface $requestService,
        ViewInterface $viewService,
        CpUrlInterface $cpUrlService,
        ChannelSelectsService $channelSelectsService,
        FilteredChannelLinksService $filteredChannelLinksService,
        FilterTypesService $filterTypesService,
        VisorTableService $visorTableService,
        EntrySelectionService $entrySelectionService
    ) {
        $this->requestService = $requestService;
        $this->viewService = $viewService;
        $this->cpUrlService = $cpUrlService;
        $this->channelSelectsService = $channelSelectsService;
        $this->filteredChannelLinksService = $filteredChannelLinksService;
        $this->filterTypesService = $filterTypesService;
        $this->visorTableService = $visorTableService;
        $this->entrySelectionService = $entrySelectionService;

        $filters = $this->requestService->get('filter', []);

        if (! is_array($filters)) {
            return;
        }

        $this->filters = $filters;
    }

    /**
     * Displays the listing page
     * @return array
     */
    public function __invoke()
    {
        return $this->run();
    }

    /**
     * Displays the listing page
     * @return array
     */
    public function run()
    {
        $viewBody = '<style type="text/css">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/visor.css');
        $viewBody .= '</style>';

        if (version_compare(APP_VER, '4.0.0', '<')) {
            $viewBody .= '<style type="text/css">';
            $viewBody .= file_get_contents(VISOR_PATH . '/resources/visoree3.css');
            $viewBody .= '</style>';
        }

        $viewBody .= '<script type="text/javascript">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/FAB.controller.js');
        $viewBody .= '</script>';
        $viewBody .= '<script type="text/javascript">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/FAB.model.js');
        $viewBody .= '</script>';

        $viewBody .= $this->viewService->renderView('visor:Visor', [
            'baseUrl' => $this->cpUrlService->renderUrl('addons/settings/visor'),
            'fullUrl' => $this->getFullUrlToPage(),
            'filters' => $this->filters,
            'channelSelects' => $this->channelSelectsService->get(),
            'filteredChannelLinks' => $this->filteredChannelLinksService->get(),
            'pagination' => $this->entrySelectionService->getPagination(
                $this->getFullUrlToPageObject()
            ),
            'filterTypes' => $this->filterTypesService->get(),
            'tableViewData' => $this->visorTableService->getViewData(),
        ]);

        $jsControllersDirectory = new \DirectoryIterator(
            VISOR_PATH . '/resources/controllers'
        );

        foreach ($jsControllersDirectory as $fileInfo) {
            if ($fileInfo->isDot() ||
                $fileInfo->isDir() ||
                $fileInfo->getExtension() !== 'js'
            ) {
                continue;
            }

            $viewBody .= '<script type="text/javascript">';

            $viewBody .= file_get_contents(
                $fileInfo->getPath() . '/' . $fileInfo->getFilename()
            );

            $viewBody .= '</script>';
        }

        $viewBody .= '<script type="text/javascript">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/visor.js');
        $viewBody .= '</script>';

        return ['heading' => lang('Visor'), 'body' => $viewBody,];
    }

    /**
     * Gets the full URL to this page
     * @return string
     */
    private function getFullUrlToPage()
    {
        return $this->getFullUrlToPageObject()->compile();
    }

    /**
     * Gets the full page URL object
     * @return UrlObject
     */
    private function getFullUrlToPageObject()
    {
        return $this->cpUrlService->getUrlObject(
            'addons/settings/visor',
            ['filter' => array_values($this->filters)]
        );
    }
}
