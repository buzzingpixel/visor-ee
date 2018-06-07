<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\Alert\Alert;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use EllisLab\ExpressionEngine\Model\Channel\ChannelField;
use EllisLab\ExpressionEngine\Model\File\File as FileModel;
use EllisLab\ExpressionEngine\Service\Alert\AlertCollection;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Model\Channel\Channel as ChannelModel;
use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;
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

    /** @var AlertCollection $alertCollection */
    private $alertCollection;

    /** @var EE_Functions $eeFunctions */
    private $eeFunctions;

    /** @var \EE_Config $eeConfigService */
    private $eeConfigService;

    /** @var QueryBuilder $queryBuilder */
    private $queryBuilder;

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
        $this->alertCollection = ee('CP/Alert');
        $this->eeFunctions = ee()->functions;
        $this->eeConfigService = ee()->config;
        $this->queryBuilder = ee('db');
    }

    /**
     * Displays the index page
     *
     * @return array
     */
    public function index()
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $this->deletePostedEntries();
            exit();
        }

        $filters = $this->inputService->get('filter', true);

        if (!is_array($filters)) {
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

        $viewBody .= $this->viewFactory->make('visor:Visor')->render(['viewFactory' => $this->viewFactory, 'baseUrl' => $this->cpUrlFactory->make('addons/settings/visor')->compile(), 'fullUrl' => $this->getFullUrlToPage(), 'filters' => $filters, 'channelSelects' => $this->getChannelSelects(), 'filteredChannelLinks' => $this->getFilteredChannelLinks(), 'pagination' => $this->getPagination(), 'tableViewData' => $this->populateTableData($this->createTable(), $this->getEntryModelCollection())->viewData(),]);

        $controllersDirectory = new \DirectoryIterator(VISOR_PATH . '/resources/controllers');

        foreach ($controllersDirectory as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir() || $fileInfo->getExtension() !== 'js') {
                continue;
            }

            $viewBody .= '<script type="text/javascript">';
            $viewBody .= file_get_contents($fileInfo->getPath() . '/' . $fileInfo->getFilename());
            $viewBody .= '</script>';
        }

        $viewBody .= '<script type="text/javascript">';
        $viewBody .= file_get_contents(VISOR_PATH . '/resources/visor.js');
        $viewBody .= '</script>';

        return ['heading' => lang('Visor'), 'body' => $viewBody,];
    }

    /**
     * Gets the filters
     *
     * @return array(
     *     'channels' => array(),
     *     'standard' => array(),
     * )
     */
    private function getFiltersFromInput()
    {
        $filters = $this->inputService->get('filter', true);

        if (!is_array($filters)) {
            $filters = [];
        }

        $channels = [];
        $standard = [];

        foreach ($filters as $key => $filter) {
            if (!isset($filter['type'], $filter['value'])) {
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
     *
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

        $visorFilters = $this->inputService->get('filter') ?: [];

        foreach ($channels as $channel) {
            /** @var ChannelModel $channel */
            $links[] = ['title' => $channel->getProperty('channel_title'), 'link' => $this->cpUrlFactory->make("publish/create/{$channel->getProperty('channel_id')}", ['visorReturn' => 'true', 'visorFilters' => $visorFilters,])->compile()];
        }

        return $links;
    }

    /**
     * Gets channel collection
     *
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

    /**
     * Gets URL
     *
     * @return \EllisLab\ExpressionEngine\Library\CP\URL
     */
    private function getFullUrlToPage()
    {
        $url = $this->cpUrlFactory->make('addons/settings/visor');

        if ($this->inputService->get('filter')) {
            $url->setQueryStringVariable('filter', $this->inputService->get('filter'));
        }

        return $url;
    }

    /**
     * Gets pagination
     *
     * @return string
     */
    private function getPagination()
    {
        $limit = $this->inputService->get('limit', true) ?: self::PAGE_LIMIT;

        $channelModelBuilder = $this->getEntryModelBuilder();

        /** @var Pagination $pagination */
        $pagination = ee('CP/Pagination', $channelModelBuilder->count());
        $pagination->perPage($limit);
        $pagination->currentPage($this->inputService->get('page', true) ?: 1);

        return $pagination->render($this->getFullUrlToPage());
    }

    /**
     * Gets the entry model builder
     *
     * @return ModelQueryBuilder
     */
    private function getEntryModelBuilder()
    {
        /** @var ModelQueryBuilder $channelModelBuilder */
        $channelModelBuilder = $this->modelFacade->get('ChannelEntry');

        $filters = $this->getFiltersFromInput();

        if ($filters['channels']) {
            $channelModelBuilder->with('Channel');
            $channelModelBuilder->filter('Channel.channel_name', 'IN', $filters['channels']);
        }

        foreach ($filters['standard'] as $filter) {
            if ($filter['operator'] === 'contains') {
                $channelModelBuilder->filter($filter['type'], 'LIKE', '%' . $filter['value'] . '%');
                continue;
            }

            $channelModelBuilder->filter($filter['type'], $filter['value']);
        }

        return $channelModelBuilder;
    }

    /**
     * Creates the table
     *
     * @return Table
     */
    private function createTable()
    {
        /** @var Table $table */
        $table = ee('CP/Table');

        $table->setNoResultsText('noEntries');

        $filters = $this->getFiltersFromInput();

        $channel = null;

        if (count($filters['channels']) === 1) {
            $channel = $filters['channels'][0];
        }

        $columnConfig = $this->eeConfigService->item('channelConfig', 'visor') ?: [];
        $columnConfig = isset($columnConfig[$channel]) ? $columnConfig[$channel] : self::$defaultColumns;

        $table->setColumns(array_merge($columnConfig, [['type' => Table::COL_CHECKBOX,],]));

        return $table;
    }

    /**
     * Populates the table data
     *
     * @param Table $table
     * @param ModelCollection $entryModelCollection
     * @return Table
     */
    private function populateTableData(Table $table, ModelCollection $entryModelCollection)
    {
        $tableData = [];

        $visorFilters = $this->inputService->get('filter') ?: [];

        foreach ($entryModelCollection as $entryModel) {
            /** @var ChannelEntryModel $entryModel */

            $url = $this->cpUrlFactory
                ->make(
                    "publish/edit/entry/{$entryModel->getProperty('entry_id')}",
                    [
                        'visorReturn' => 'true',
                        'visorFilters' => $visorFilters,
                    ]
                )
                ->compile();

            $data = [];

            $filters = $this->getFiltersFromInput();

            $channel = null;

            if (count($filters['channels']) === 1) {
                $channel = $filters['channels'][0];
            }

            $columnConfig = $this->eeConfigService->item('channelConfig', 'visor') ?: [];
            $columnConfig = isset($columnConfig[$channel]) ? $columnConfig[$channel] : self::$defaultColumns;

            foreach ($columnConfig as $column) {
                $property = isset($column['modelProperty']) ? $column['modelProperty'] : null;

                $parentCheck = explode('.', $property);
                $parentProperty = null;

                if (isset($parentCheck[1])) {
                    $parentProperty = $parentCheck[0];
                    $property = $parentCheck[1];
                }

                if ((
                        $parentProperty &&
                        ! $entryModel->{$parentProperty}->hasProperty($property)
                    ) ||
                    (
                        ! $parentProperty &&
                        ! $entryModel->hasProperty($property)
                    )
                ) {
                    $data[] = '';
                    continue;
                }

                $formatting = isset($column['propertyFormatting']) ? $column['propertyFormatting'] : null;

                $propertyValue = null;

                if ($parentProperty) {
                    $propertyValue = $entryModel->{$parentProperty}->getProperty($property);
                }

                if (!$parentProperty) {
                    if (isset($column['isCustomField']) && $column['isCustomField']) {
                        $fieldId = $this->getFieldId($property);
                        $propertyValue = $entryModel->getProperty("field_id_{$fieldId}");
                    } else {
                        $propertyValue = $entryModel->getProperty($property);
                    }
                }

                switch ($formatting) {
                    case 'date':
                        $data[] = $this->parseDateFieldValueForDisplay($propertyValue, $column);
                        break;
                    case 'title':
                        $data[] = '<strong style="font-style: normal;">' . "<a href=\"{$url}\">{$propertyValue}</a>" . '</strong>';
                        break;
                    case 'file':
                        $data[] = $this->parseImageFieldValueForDisplay($propertyValue);
                        break;
                    case 'grid':
                        $data[] = $this->parseGridField(
                            $column,
                            (int) $entryModel->getProperty('entry_id'),
                            $property
                        );
                        break;
                    default:
                        $data[] = $this->parseDefaultFieldValueForDisplay($propertyValue);
                }
            }

            $tableData[] = array_merge($data, [['name' => "entry[{$entryModel->getProperty('entry_id')}]", 'value' => 'selected',]]);
        }

        $table->setData($tableData);

        return $table;
    }

    /**
     * Gets channels selects array
     *
     * @return array
     */
    private function getChannelSelects()
    {
        /** @var ModelQueryBuilder $channelQuery */
        $channelQuery = $this->modelFacade->get('Channel');

        $channelQuery->order('channel_title', 'asc');

        $channelSelects = ['' => '--',];

        foreach ($channelQuery->all() as $model) {
            /** @var ChannelModel $model */
            $channelSelects[$model->getProperty('channel_name')] = $model->getProperty('channel_title');
        }

        return $channelSelects;
    }

    /**
     * Deletes posted entries
     */
    private function deletePostedEntries()
    {
        /** @var Alert $alert */
        $alert = $this->alertCollection->make('visor');

        $entryIds = (array)$this->inputService->post('entry');

        if (!$entryIds) {
            $alert->asIssue();
            $alert->withTitle(lang('error'));
            $alert->addToBody(lang('noEntriesSelected'));
            $alert->defer();
            $this->eeFunctions->redirect($this->inputService->post('redirect') ?: $this->getFullUrlToPage());
            exit();
        }

        /** @var ModelQueryBuilder $channelModelBuilder */
        $channelModelBuilder = $this->modelFacade->get('ChannelEntry');
        $channelModelBuilder->filter('entry_id', 'IN', array_keys($entryIds));
        $channelModelBuilder->delete();

        $alert->asSuccess();
        $alert->withTitle(lang('success'));
        $alert->addToBody(lang('selectedEntriesDeleted'));
        $alert->defer();
        $this->eeFunctions->redirect($this->inputService->post('redirect') ?: $this->getFullUrlToPage());
        exit();
    }

    /** @var array $fieldNameToIdMap */
    private $fieldNameToIdMap;

    /**
     * Gets field ID from field property name
     *
     * @param $property
     * @return mixed|null
     */
    private function getFieldId($property)
    {
        if ($this->fieldNameToIdMap === null) {
            /** @var ModelQueryBuilder $fieldsQueryBuilder */
            $fieldsQueryBuilder = $this->modelFacade->get('ChannelField');

            $this->fieldNameToIdMap = [];

            foreach ($fieldsQueryBuilder->all() as $channelField) {
                /** @var ChannelField $channelField */
                $id = (int)$channelField->getProperty('field_id');
                $name = $channelField->getProperty('field_name');
                $this->fieldNameToIdMap[$name] = $id;
            }
        }

        return isset($this->fieldNameToIdMap[$property]) ? $this->fieldNameToIdMap[$property] : null;
    }

    /**
     * @param $propertyValue
     * @param $column
     * @return string
     */
    private function parseDateFieldValueForDisplay($propertyValue, $column)
    {
        $format = isset($column['dateFormat']) ? $column['dateFormat'] : 'n/j/Y g:i A';

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($propertyValue);

        return $dateTime->format($format);
    }

    /**
     * Parses image field value for display
     *
     * @param string $propertyValue
     * @return string
     */
    private function parseImageFieldValueForDisplay($propertyValue)
    {
        if (!$propertyValue) {
            return '';
        }

        preg_match('/{filedir_(\d+)}(.*)/', $propertyValue, $matches);

        $fileDirId = isset($matches[1]) ? ((int)$matches[1]) : null;
        $fileName = isset($matches[2]) ? $matches[2] : null;

        if (!$fileDirId || !$fileName) {
            return '';
        }

        /** @var ModelQueryBuilder $fileQuery */
        $fileQuery = $this->modelFacade->get('File');
        $fileQuery->filter('file_name', $fileName);
        $fileQuery->filter('upload_location_id', $fileDirId);

        /** @var FileModel $fileModel */
        $fileModel = $fileQuery->first();

        if (!$fileModel) {
            return '';
        }

        $str = '<div class="visor-file-wrapper">';

        if ($fileModel->isImage()) {
            $str .= "<img src=\"{$fileModel->getThumbnailUrl()}\" alt=\"{$fileModel->getProperty('title')}\">";
        } else {
            $str .= $fileName;
        }

        $str .= '</div>';

        return $str;
    }

    /**
     * @param $propertyValue
     * @return string
     */
    private function parseDefaultFieldValueForDisplay($propertyValue)
    {
        $charCount = strlen($propertyValue);

        if ($charCount > 100) {
            $propertyValue = substr($propertyValue, 0, 97) . '...';
        }

        return $propertyValue;
    }

    /**
     * Parses a grid field
     * @param array $column
     * @param int $entryId
     * @param string $fieldName
     * @return string
     */
    private function parseGridField($column, $entryId, $fieldName)
    {
        if (! isset($column['gridItems'])) {
            return '';
        }

        $gridItems = $column['gridItems'];

        /** @var Table $table */
        $table = ee('CP/Table');

        $table->setNoResultsText('noRows');

        $table->setColumns($gridItems);

        $fieldId = $this->getFieldId($fieldName);

        $gridFieldQuery = $this->queryBuilder->where('entry_id', $entryId)
            ->order_by('row_order', 'asc')
            ->get("channel_grid_field_{$fieldId}")
            ->result();

        $tableData = [];

        // We need to go through each grid row
        foreach ($gridFieldQuery as $gridRow) {
            $data = [];

            // And we need to go through each grid column
            foreach ($gridItems as $gridColumn) {
                $property = isset($gridColumn['modelProperty']) ?
                    $gridColumn['modelProperty'] :
                    null;

                if (! $property) {
                    $data[] = '';
                    continue;
                }

                $formatting = isset($gridColumn['propertyFormatting']) ?
                    $gridColumn['propertyFormatting'] :
                    null;

                $colId = $this->getGridColId($fieldId, $property);

                if (! $colId) {
                    $data[] = '';
                    continue;
                }

                $propertyValue = isset($gridRow->{"col_id_{$colId}"}) ?
                    $gridRow->{"col_id_{$colId}"} :
                    null;

                if (! $propertyValue) {
                    $data[] = '';
                    continue;
                }

                switch ($formatting) {
                    case 'date':
                        $data[] = $this->parseDateFieldValueForDisplay($propertyValue, $gridColumn);
                        break;
                    case 'file':
                        $data[] = $this->parseImageFieldValueForDisplay($propertyValue);
                        break;
                    default:
                        $data[] = $this->parseDefaultFieldValueForDisplay($propertyValue);
                }
            }

            $tableData[] = $data;
        }

        $table->setData($tableData);

        $returnString = '<div class="visor-grid-wrapper">';

        $returnString .= $this->viewFactory->make('ee:_shared/table')
            ->render($table->viewData());

        $returnString .= '</div>';

        return $returnString;
    }

    /**
     * @param $fieldId
     * @param $colName
     * @return int
     */
    private function getGridColId($fieldId, $colName)
    {
        $query = $this->queryBuilder->select('col_id')
            ->where('field_id', $fieldId)
            ->where('col_name', $colName)
            ->get('grid_columns')
            ->row();

        if (! isset($query->col_id)) {
            return 0;
        }

        return (int) $query->col_id;
    }
}
