<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Model\Channel\Channel as ChannelModel;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntryModel;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;

/**
 * Class Visor_mcp
 */
class Visor_mcp
{
    const PAGE_LIMIT = 25;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var EE_Input $inputService */
    private $inputService;

    /** @var ViewFactory $viewFactory */
    private $viewFactory;

    /** @var URLFactory $cpUrlService */
    private $cpUrlFactory;

    private static $defaultColumns = [
        [
            'label' => 'id',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_ID,
            'modelProperty' => 'entry_id',
        ],
        [
            'label' => 'title',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_TEXT,
            'modelProperty' => 'title',
            'propertyFormatting' => 'title',
        ],
        [
            'label' => 'channel',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_TEXT,
            'modelProperty' => 'Channel.channel_title',
        ],
        [
            'label' => 'date',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_TEXT,
            'modelProperty' => 'entry_date',
            'propertyFormatting' => 'date',
        ],
        [
            'label' => 'status',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_STATUS,
            'modelProperty' => 'status',
        ],
    ];

    /**
     * Visor_mcp constructor
     */
    public function __construct()
    {
        $this->modelFacade = ee('Model');
        $this->inputService = ee()->input;
        $this->viewFactory = ee('View');
        $this->cpUrlFactory = ee('CP/URL');
    }

    /**
     * Displays the index page
     * @return array
     */
    public function index()
    {
        $filters = $this->inputService->get('filter', true);

        if (! is_array($filters)) {
            $filters = [];
        }

        $viewBody = '<style type="text/css">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/visor.css');
        $viewBody .= '</style>';
        $viewBody .= '<script type="text/javascript">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/FAB.controller.js');
        $viewBody .= '</script>';
        $viewBody .= '<script type="text/javascript">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/FAB.model.js');
        $viewBody .= '</script>';

        $viewBody .= $this->viewFactory->make('visor:Visor')
            ->render([
                'viewFactory' => $this->viewFactory,
                'baseUrl' => $this->cpUrlFactory->make('addons/settings/visor')
                    ->compile(),
                'filters' => $filters,
                'channelSelects' => $this->getChannelSelects(),
                'filteredChannelLinks' => $this->getFilteredChannelLinks(),
                'pagination' => $this->getPagination(),
                'tableViewData' => $this
                    ->populateTableData(
                        $this->createTable(),
                        $this->getEntryModelCollection()
                    )
                    ->viewData(),
            ]);

        $controllersDirectory = new \DirectoryIterator(
            VISOR_PATH . '/resources/controllers'
        );

        foreach ($controllersDirectory as $fileInfo) {
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

        return [
            'heading' => lang('Visor'),
            'body' => $viewBody,
        ];
    }

    /**
     * Gets the filters
     * @return array(
     *     'channels' => array(),
     *     'standard' => array(),
     * )
     */
    private function getFiltersFromInput()
    {
        $filters = $this->inputService->get('filter', true);

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

    /**
     * Gets filtered channel links
     * @return array
     */
    private function getFilteredChannelLinks()
    {
        $channelQuery = $this->modelFacade->get('Channel');

        $channelQuery->order('channel_title', 'asc');

        $filters = $this->getFiltersFromInput();

        if ($filters['channels']) {
            $channelQuery->filter('channel_name', 'IN', $filters['channels']);
        }

        $channels = $channelQuery->all();

        $links = [];

        foreach ($channels as $channel) {
            /** @var ChannelModel $channel */
            $links[] = [
                'title' => $channel->getProperty('channel_title'),
                'link' => $this->cpUrlFactory
                    ->make("publish/create/{$channel->getProperty('channel_id')}")
                    ->compile()
            ];
        }

        return $links;
    }

    /**
     * Gets channel collection
     * @return ModelCollection
     */
    private function getEntryModelCollection()
    {
        $limit = $this->inputService->get('limit', true) ?: self::PAGE_LIMIT;
        $page = $this->inputService->get('page', true) ?: 1;

        $channelModelBuilder = $this->getEntryModelBuilder();
        $channelModelBuilder->order('entry_date', 'desc');
        $channelModelBuilder->limit($limit);
        $channelModelBuilder->offset(($page * $limit) - $limit);

        return $channelModelBuilder->all();
    }

    private function getPagination()
    {
        $limit = $this->inputService->get('limit', true) ?: self::PAGE_LIMIT;

        $channelModelBuilder = $this->getEntryModelBuilder();

        $baseUrl = $this->cpUrlFactory->make('addons/settings/visor');

        if ($this->inputService->get('filter')) {
            $baseUrl->setQueryStringVariable(
                'filter',
                $this->inputService->get('filter')
            );
        }

        /** @var Pagination $pagination */
        $pagination = ee('CP/Pagination', $channelModelBuilder->count());
        $pagination->perPage($limit);
        $pagination->currentPage($this->inputService->get('page', true) ?: 1);

        return $pagination->render($baseUrl);
    }

    /**
     * Gets the entry model builder
     * @return ModelQueryBuilder
     */
    private function getEntryModelBuilder()
    {
        /** @var ModelQueryBuilder $channelModelBuilder */
        $channelModelBuilder = $this->modelFacade->get('ChannelEntry');

        $filters = $this->getFiltersFromInput();

        if ($filters['channels']) {
            $channelModelBuilder->with('Channel');
            $channelModelBuilder->filter(
                'Channel.channel_name',
                'IN',
                $filters['channels']
            );
        }

        foreach ($filters['standard'] as $filter) {
            if ($filter['operator'] === 'contains') {
                $channelModelBuilder->filter(
                    $filter['type'],
                    'LIKE',
                    '%' . $filter['value'] . '%'
                );
                continue;
            }

            $channelModelBuilder->filter(
                $filter['type'],
                $filter['value']
            );
        }

        return $channelModelBuilder;
    }

    /**
     * Creates the table
     * @return Table
     */
    private function createTable()
    {
        /** @var Table $table */
        $table = ee('CP/Table');

        $table->setNoResultsText('noEntries');

        $table->setColumns(array_merge(self::$defaultColumns, [
            [
                'type' => Table::COL_CHECKBOX,
            ],
        ]));

        return $table;
    }

    /**
     * Populates the table data
     * @param Table $table
     * @param ModelCollection $entryModelCollection
     * @return Table
     */
    private function populateTableData(
        Table $table,
        ModelCollection $entryModelCollection
    ) {
        $tableData = [];

        foreach ($entryModelCollection as $channelModel) {
            /** @var ChannelEntryModel $channelModel */

            $url = $this->cpUrlFactory
                ->make(
                    "publish/edit/entry/{$channelModel->getProperty('entry_id')}"
                )
                ->compile();

            $data = [];

            foreach (self::$defaultColumns as $column) {
                $property = isset($column['modelProperty']) ?
                    $column['modelProperty'] :
                    null;

                $parentCheck = explode('.', $property);
                $parentProperty = null;

                if (isset($parentCheck[1])) {
                    $parentProperty = $parentCheck[0];
                    $property = $parentCheck[1];
                }

                if ((
                        $parentProperty &&
                        ! $channelModel->{$parentProperty}->hasProperty($property)
                    ) ||
                    (
                        ! $parentProperty &&
                        ! $channelModel->hasProperty($property)
                    )
                ) {
                    $data[] = '';
                    continue;
                }

                $formatting = isset($column['propertyFormatting']) ?
                    $column['propertyFormatting'] :
                    null;

                $propertyValue = null;

                if ($parentProperty) {
                    $propertyValue = $channelModel->{$parentProperty}->getProperty($property);
                }

                if (! $parentProperty) {
                    $propertyValue = $channelModel->getProperty($property);
                }

                switch ($formatting) {
                    case 'date':
                        $format = isset($column['dateFormat']) ?
                            $column['dateFormat'] :
                            'n/j/Y g:i A';

                        $dateTime = new \DateTime();
                        $dateTime->setTimestamp($propertyValue);

                        $data[] = $dateTime->format($format);
                        break;
                    case 'title':
                        $data[] = '<strong style="font-style: normal;">' .
                            "<a href=\"{$url}\">{$propertyValue}</a>" .
                        '</strong>';
                        break;
                    default:
                        $data[] = $propertyValue;
                }
            }

            $tableData[] = array_merge($data, [[
                'name' => "entry[{$channelModel->getProperty('entry_id')}]",
                'value' => 'selected',
            ]]);
        }

        $table->setData($tableData);

        return $table;
    }

    /**
     * Gets channels selects array
     * @return array
     */
    private function getChannelSelects()
    {
        /** @var ModelQueryBuilder $channelQuery */
        $channelQuery = $this->modelFacade->get('Channel');

        $channelQuery->order('channel_title', 'asc');

        $channelSelects = [
            '' => '--',
        ];

        foreach ($channelQuery->all() as $model) {
            /** @var ChannelModel $model */
            $channelSelects[$model->getProperty('channel_name')] = $model->getProperty('channel_title');
        }

        return $channelSelects;
    }
}
