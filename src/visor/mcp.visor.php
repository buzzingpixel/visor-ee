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
use EllisLab\ExpressionEngine\Model\Category\Category as CategoryModel;
use EllisLab\ExpressionEngine\Library\Data\Collection as DataCollection;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntryModel;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;
use EllisLab\ExpressionEngine\Service\Permission\Permission as PermissionService;

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

    /** @var \EE_Session */
    private $eeSession;

    /** @var PermissionService $permissionService */
    private $permissionService;

    private static $defaultColumns = [
        [
            'label' => 'id',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_ID,
            'modelProperty' => 'entry_id',
            'filter' => true,
        ],
        [
            'label' => 'title',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_TEXT,
            'modelProperty' => 'title',
            'propertyFormatting' => 'title',
            'filter' => true,
        ],
        [
            'label' => 'url_title',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_TEXT,
            'modelProperty' => 'url_title',
            'filter' => true,
        ],
        [
            'label' => 'channel',
            'encode' => false,
            'sort' => false,
            'type' => Table::COL_TEXT,
            'modelProperty' => 'Channel.channel_title',
            'filter' => true,
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
            'filter' => true,
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
        $this->eeSession = ee()->session;
        $this->permissionService = ee('Permission');
    }

    /**
     * Displays the index page
     * @return array
     */
    public function index()
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $this->deletePostedEntries();
            exit();
        }

        $filters = $this->inputService->get('filter', true);

        if (! is_array($filters)) {
            $filters = [];
        }

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

        $viewBody .= $this->viewFactory->make('visor:Visor')
            ->render([
                'viewFactory' => $this->viewFactory,
                'baseUrl' => $this->cpUrlFactory->make('addons/settings/visor')->compile(),
                'fullUrl' => $this->getFullUrlToPage(),
                'filters' => $filters,
                'channelSelects' => $this->getChannelSelects(),
                'filteredChannelLinks' => $this->getFilteredChannelLinks(),
                'pagination' => $this->getPagination(),
                'filterTypes' => $this->getFilterTypes(),
                'tableViewData' => $this->populateTableData(
                    $this->createTable(),
                    $this->getEntryModelCollection()
                )
                ->viewData(),]);

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
        if (! $this->permissionService->has('can_create_entries')) {
            return [];
        }

        $channelQuery = $this->modelFacade->get('Channel');

        $channelQuery->order('channel_title', 'asc');

        $filters = $this->getFiltersFromInput();

        $channelQuery->filter(
            'channel_id',
            'IN',
            array_keys($this->eeSession->userdata('assigned_channels'))
        );

        if ($filters['channels']) {
            $channelQuery->filter('channel_name', 'IN', $filters['channels']);
        }

        $channels = $channelQuery->all();

        $links = [];

        $visorFilters = $this->inputService->get('filter') ?: [];

        foreach ($channels as $channel) {
            /** @var ChannelModel $channel */
            $links[] = [
                'title' => $channel->getProperty('channel_title'),
                'link' => $this->cpUrlFactory->make(
                    "publish/create/{$channel->getProperty('channel_id')}",
                    [
                        'visorReturn' => 'true',
                        'visorFilters' => $visorFilters,
                    ]
                )
                ->compile()];
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

    /**
     * Gets URL
     * @return \EllisLab\ExpressionEngine\Library\CP\URL
     */
    private function getFullUrlToPage()
    {
        $url = $this->cpUrlFactory->make('addons/settings/visor');

        if ($this->inputService->get('filter')) {
            $url->setQueryStringVariable(
                'filter',
                $this->inputService->get('filter')
            );
        }

        return $url;
    }

    /**
     * Gets pagination
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
     * Get column config
     * @return array
     */
    private function getColumnConfig()
    {
        $filters = $this->getFiltersFromInput();

        $channel = null;

        if (count($filters['channels']) === 1) {
            $channel = $filters['channels'][0];
        }

        $columnConfig = $this->eeConfigService->item('channelConfig', 'visor') ?: [];
        return isset($columnConfig[$channel]) ?
            $columnConfig[$channel] :
            self::$defaultColumns;
    }

    /**
     * Gets the entry model builder
     * @return ModelQueryBuilder
     */
    private function getEntryModelBuilder()
    {
        /** @var ModelQueryBuilder $channelModelBuilder */
        $channelModelBuilder = $this->modelFacade->get('ChannelEntry');

        $channelModelBuilder->filter(
            'channel_id',
            'IN',
            array_keys($this->eeSession->userdata('assigned_channels'))
        );

        if (! $this->permissionService->has('can_edit_self_entries')) {
            $channelModelBuilder->filter(
                'author_id',
                '!=',
                $this->eeSession->userdata('member_id')
            );
        }

        if (! $this->permissionService->has('can_edit_other_entries')) {
            $channelModelBuilder->filter(
                'author_id',
                $this->eeSession->userdata('member_id')
            );
        }

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

            $channelModelBuilder->filter($filter['type'], $filter['value']);
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

        $table->setColumns(array_merge(
            $this->getColumnConfig(),
            [
                [
                    'type' => Table::COL_CHECKBOX,
                ],
            ]
        ));

        return $table;
    }

    /**
     * Populates the table data
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

            foreach ($this->getColumnConfig() as $column) {
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

                $formatting = isset($column['propertyFormatting']) ?
                    $column['propertyFormatting'] :
                    null;

                $propertyValue = null;

                if ($parentProperty) {
                    $propertyValue = $entryModel->{$parentProperty}->getProperty($property);
                }

                if (! $parentProperty) {
                    if (isset($column['isCustomField']) && $column['isCustomField']) {
                        $fieldId = $this->getFieldId($property);
                        $propertyValue = $entryModel->getProperty("field_id_{$fieldId}");
                    } else {
                        $propertyValue = $entryModel->getProperty($property);
                    }
                }

                switch ($formatting) {
                    case 'date':
                        $data[] = $this->parseDateFieldValueForDisplay(
                            $propertyValue,
                            $column
                        );
                        break;
                    case 'title':
                        $data[] = '<strong style="font-style: normal; white-space: nowrap;">' . "<a href=\"{$url}\">{$propertyValue}</a>" . '</strong>';
                        break;
                    case 'file':
                        $data[] = $this->parseImageFieldValueForDisplay(
                            $propertyValue
                        );
                        break;
                    case 'grid':
                        $data[] = $this->parseGridField(
                            $column,
                            (int) $entryModel->getProperty('entry_id'),
                            $property
                        );
                        break;
                    case 'matrix':
                        $data[] = $this->parseMatrixField(
                            $column,
                            (int) $entryModel->getProperty('entry_id'),
                            $property
                        );
                        break;
                    case 'categories':
                        $data[] = $this->parseCategoryField($entryModel);
                        break;
                    case 'relationship':
                        $data[] = $this->parseRelationshipField(
                            $entryModel,
                            $column
                        );
                        break;
                    default:
                        $data[] = $this->parseDefaultFieldValueForDisplay(
                            $propertyValue,
                            $column
                        );
                }
            }

            $tableData[] = array_merge($data, [[
                'name' => "entry[{$entryModel->getProperty('entry_id')}]",
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

        $channelQuery->filter(
            'channel_id',
            'IN',
            array_keys($this->eeSession->userdata('assigned_channels'))
        );

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

        $entryIds = (array) $this->inputService->post('entry');

        if (! $entryIds) {
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

        if (! $this->permissionService->has('can_delete_self_entries')) {
            $channelModelBuilder->filter(
                'author_id',
                '!=',
                $this->eeSession->userdata('member_id')
            );
        }

        if (! $this->permissionService->has('can_delete_all_entries')) {
            $channelModelBuilder->filter(
                'author_id',
                $this->eeSession->userdata('member_id')
            );
        }

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
     * @param string $propertyValue
     * @return string
     */
    private function parseImageFieldValueForDisplay($propertyValue)
    {
        if (! $propertyValue) {
            return '';
        }

        preg_match('/{filedir_(\d+)}(.*)/', $propertyValue, $matches);

        $fileDirId = isset($matches[1]) ? ((int)$matches[1]) : null;
        $fileName = isset($matches[2]) ? $matches[2] : null;

        if (! $fileDirId || ! $fileName) {
            return '';
        }

        /** @var ModelQueryBuilder $fileQuery */
        $fileQuery = $this->modelFacade->get('File');
        $fileQuery->filter('file_name', $fileName);
        $fileQuery->filter('upload_location_id', $fileDirId);

        /** @var FileModel $fileModel */
        $fileModel = $fileQuery->first();

        if (! $fileModel) {
            return '';
        }

        $str = '<div class="visor-file-wrapper">';

        $str .= "<a href=\"{$fileModel->getAbsoluteURL()}\" target=\"_blank\">";

        if ($fileModel->isImage()) {
            $str .= "<img src=\"{$fileModel->getThumbnailUrl()}\" alt=\"{$fileModel->getProperty('title')}\">";
        } else {
            $str .= $fileName;
        }

        $str .= '</a>';

        $str .= '</div>';

        return $str;
    }

    /**
     * @param $propertyValue
     * @param array $column
     * @return string
     */
    private function parseDefaultFieldValueForDisplay($propertyValue, $column)
    {
        $type = isset($column['type']) ? $column['type'] : Table::COL_TEXT;

        if ($type !== Table::COL_TEXT) {
            return $propertyValue;
        }

        $propertyValue = strip_tags($propertyValue);

        $charCount = strlen($propertyValue);

        if ($charCount > 100) {
            $propertyValue = substr($propertyValue, 0, 97) . '...';
        }

        $charCount = strlen($propertyValue);

        $classes = 'visor-text-field';

        if ($charCount > 40) {
            $classes .= ' visor-long-text-field';
        }

        return "<div class=\"{$classes}\">{$propertyValue}</div>";
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
                        $data[] = $this->parseDateFieldValueForDisplay(
                            $propertyValue,
                            $gridColumn
                        );
                        break;
                    case 'file':
                        $data[] = $this->parseImageFieldValueForDisplay(
                            $propertyValue
                        );
                        break;
                    default:
                        $data[] = $this->parseDefaultFieldValueForDisplay(
                            $propertyValue,
                            $gridColumn
                        );
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

    /**
     * Parses a matrix field
     * @param $column
     * @param $entryId
     * @param $fieldName
     * @return string
     */
    private function parseMatrixField($column, $entryId, $fieldName)
    {
        if (! isset($column['matrixItems'])) {
            return '';
        }

        $matrixItems = $column['matrixItems'];

        /** @var Table $table */
        $table = ee('CP/Table');

        $table->setNoResultsText('noRows');

        $table->setColumns($matrixItems);

        $fieldId = $this->getFieldId($fieldName);

        $matrixFieldQuery = $this->queryBuilder->where('entry_id', $entryId)
            ->where('field_id', $fieldId)
            ->order_by('row_order', 'asc')
            ->get('matrix_data')
            ->result();

        $tableData = [];

        // We need to go through each grid row
        foreach ($matrixFieldQuery as $matrixRow) {
            $data = [];

            // And we need to go through each grid column
            foreach ($matrixItems as $matrixColumn) {
                $property = isset($matrixColumn['modelProperty']) ?
                    $matrixColumn['modelProperty'] :
                    null;

                if (! $property) {
                    $data[] = '';
                    continue;
                }

                $formatting = isset($matrixColumn['propertyFormatting']) ?
                    $matrixColumn['propertyFormatting'] :
                    null;

                $colId = $this->getMatrixColId($fieldId, $property);

                if (! $colId) {
                    $data[] = '';
                    continue;
                }

                $propertyValue = isset($matrixRow->{"col_id_{$colId}"}) ?
                    $matrixRow->{"col_id_{$colId}"} :
                    null;

                if (! $propertyValue) {
                    $data[] = '';
                    continue;
                }

                switch ($formatting) {
                    case 'date':
                        $data[] = $this->parseDateFieldValueForDisplay(
                            $propertyValue,
                            $matrixColumn
                        );
                        break;
                    case 'file':
                        $data[] = $this->parseImageFieldValueForDisplay(
                            $propertyValue
                        );
                        break;
                    default:
                        $data[] = $this->parseDefaultFieldValueForDisplay(
                            $propertyValue,
                            $matrixColumn
                        );
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
    private function getMatrixColId($fieldId, $colName)
    {
        $query = $this->queryBuilder->select('col_id')
            ->where('field_id', $fieldId)
            ->where('col_name', $colName)
            ->get('matrix_cols')
            ->row();

        if (! isset($query->col_id)) {
            return 0;
        }

        return (int) $query->col_id;
    }

    /**
     * @param ChannelEntryModel $entryModel
     * @return string
     */
    private function parseCategoryField(ChannelEntryModel $entryModel)
    {
        $categoryCollection = $entryModel->Categories;

        $count = $categoryCollection->count();

        if (! $count) {
            return '';
        }

        $i = 1;

        $str = '';

        foreach ($categoryCollection as $categoryModel) {
            /** @var CategoryModel $categoryModel */

            $str .= $categoryModel->getProperty('cat_name');

            if ($i !== $count) {
                $str .= ' • ';
            }

            $i++;
        }

        return $str;
    }

    /**
     * @param ChannelEntryModel $entryModel
     * @param array $config
     * @return string
     */
    private function parseRelationshipField(
        ChannelEntryModel $entryModel,
        $config
    ) {
        $fieldRels = new DataCollection(
            $this->queryBuilder->select('child_id')
                ->where('field_id', $this->getFieldId($config['modelProperty']))
                ->where('parent_id', $entryModel->getProperty('entry_id'))
                ->get('relationships')
                ->result()
        );

        $children = $entryModel->Children->filter(
            'entry_id',
            'IN',
            $fieldRels->pluck('child_id')
        );

        $visorFilters = $this->inputService->get('filter') ?: [];

        $str = '';

        foreach ($children as $child) {
            /** @var ChannelEntryModel $child */
            $url = $this->cpUrlFactory
                ->make(
                    "publish/edit/entry/{$child->getProperty('entry_id')}",
                    [
                        'visorReturn' => 'true',
                        'visorFilters' => $visorFilters,
                    ]
                )
                ->compile();

            $thisStr = '<div class="visor-relationship-link">';
            $thisStr .= "<a href=\"{$url}\">";
            $thisStr .= $child->getProperty('title');
            $thisStr .= '</a></div>';

            $str .= $thisStr;
        }

        return $str;
    }

    private function getFilterTypes()
    {
        $config = $this->getColumnConfig();

        foreach ($config as $key => $val) {
            if (! isset($val['modelProperty']) ||
                $val['modelProperty'] !== 'Channel.channel_title'
            ) {
                continue;
            }

            unset($config[$key]);
        }

        $config = array_merge([
            [
                'label' => 'channel',
                'encode' => false,
                'sort' => false,
                'type' => Table::COL_TEXT,
                'modelProperty' => 'Channel.channel_title',
                'filter' => true,
            ],
        ], array_values($config));

        $filterTypes = [
            [
                'label' => '--',
                'value' => '',
            ],
        ];

        $filtersSet = [];

        foreach ($config as $configItem) {
            if (! isset(
                $configItem['filter'],
                $configItem['label'],
                $configItem['modelProperty']
            ) ||
                ! $configItem['filter']
            ) {
                continue;
            }

            $value = $configItem['modelProperty'];

            if ($value === 'Channel.channel_title') {
                $value = 'channel';
            }

            $filtersSet[$value] = $value;

            $filterTypes[] = [
                'label' => lang($configItem['label']),
                'value' => $value,
            ];
        }

        $inputFilters = $this->getFiltersFromInput()['standard'];

        foreach ($inputFilters as $inputFilter) {
            if (in_array($inputFilter['type'], $filtersSet, true)) {
                continue;
            }

            $filterTypes[] = [
                'label' => lang($inputFilter['type']),
                'value' => $inputFilter['type'],
            ];
        }

        return $filterTypes;
    }
}
