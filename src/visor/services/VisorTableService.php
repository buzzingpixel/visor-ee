<?php

namespace buzzingpixel\visor\services;

use buzzingpixel\visor\services\FieldService;
use buzzingpixel\visor\factories\TableFactory;
use EllisLab\ExpressionEngine\Library\CP\Table;
use buzzingpixel\visor\interfaces\ViewInterface;
use buzzingpixel\visor\interfaces\CpUrlInterface;
use buzzingpixel\visor\interfaces\TableInterface;
use buzzingpixel\visor\services\ColumnConfigService;
use buzzingpixel\visor\services\EntrySelectionService;
use buzzingpixel\visor\services\FiltersFromInputService;
use EllisLab\ExpressionEngine\Model\File\File as FileModel;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;
use EllisLab\ExpressionEngine\Model\Category\Category as CategoryModel;
use EllisLab\ExpressionEngine\Library\Data\Collection as DataCollection;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntryModel;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;

/**
 * Class VisorTableService
 */
class VisorTableService
{
    /** @var TableFactory $tableFactory */
    private $tableFactory;

    /** @var ColumnConfigService $columnConfigService */
    private $columnConfigService;

    /** @var EntrySelectionService $entrySelectionService */
    private $entrySelectionService;

    /** @var FiltersFromInputService $filtersFromInputService */
    private $filtersFromInputService;

    /** @var CpUrlInterface $cpUrlService */
    private $cpUrlService;

    /** @var FieldService $fieldService */
    private $fieldService;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var QueryBuilder $queryBuilder */
    private $queryBuilder;

    /** @var ViewInterface $viewService */
    private $viewService;

    /**
     * VisorTableService constructor
     * @param TableFactory $tableFactory
     * @param ColumnConfigService $columnConfigService
     * @param EntrySelectionService $entrySelectionService
     * @param FiltersFromInputService $filtersFromInputService
     * @param CpUrlInterface $cpUrlService
     * @param FieldService $fieldService
     * @param ModelFacade $modelFacade
     * @param QueryBuilder $queryBuilder
     * @param ViewInterface $viewService
     */
    public function __construct(
        TableFactory $tableFactory,
        ColumnConfigService $columnConfigService,
        EntrySelectionService $entrySelectionService,
        FiltersFromInputService $filtersFromInputService,
        CpUrlInterface $cpUrlService,
        FieldService $fieldService,
        ModelFacade $modelFacade,
        QueryBuilder $queryBuilder,
        ViewInterface $viewService
    ) {
        $this->tableFactory = $tableFactory;
        $this->columnConfigService = $columnConfigService;
        $this->entrySelectionService = $entrySelectionService;
        $this->filtersFromInputService = $filtersFromInputService;
        $this->cpUrlService = $cpUrlService;
        $this->fieldService = $fieldService;
        $this->modelFacade = $modelFacade;
        $this->queryBuilder = $queryBuilder;
        $this->viewService = $viewService;
    }

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
            $this->entrySelectionService->getEntryModelCollectionFromRequest()
        );
    }

    /**
     * Creates the table
     * @return TableInterface
     */
    private function createTable()
    {
        $table = $this->tableFactory->get();

        $table->noResultsText('noEntries');

        $table->columns(array_merge(
            $this->columnConfigService->get(),
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
     * @param TableInterface $table
     * @param ModelCollection $entryModelCollection
     * @return array
     */
    private function populateTableData(
        TableInterface $table,
        ModelCollection $entryModelCollection
    ) {
        $colConfig = $this->columnConfigService->get();

        $tableData = [];

        $visorFilters = $this->filtersFromInputService->get();

        foreach ($entryModelCollection as $entryModel) {
            /** @var ChannelEntryModel $entryModel */

            $url = $this->cpUrlService->renderUrl(
                "publish/edit/entry/{$entryModel->getProperty('entry_id')}",
                [
                    'visorReturn' => 'true',
                    'visorFilters' => $visorFilters,
                ]
            );

            $data = [];

            foreach ($colConfig as $column) {
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
                        $fieldId = $this->fieldService->getFieldIdByName($property);
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

        $table->data($tableData);

        return $table->getViewData();
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

        $table = $this->tableFactory->get();

        $table->noResultsText('noRows');

        $table->columns($gridItems);

        $fieldId = $this->fieldService->getFieldIdByName($fieldName);

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

                $colId = $this->fieldService->getGridColId($fieldId, $property);

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

        $table->data($tableData);

        $returnString = '<div class="visor-grid-wrapper">';

        $returnString .= $this->viewService->renderView(
            'ee:_shared/table',
            $table->getViewData()
        );

        $returnString .= '</div>';

        return $returnString;
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

        $table = $this->tableFactory->get();

        $table->noResultsText('noRows');

        $table->columns($matrixItems);

        $fieldId = $this->fieldService->getFieldIdByName($fieldName);

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

                $colId = $this->fieldService->getMatrixColId($fieldId, $property);

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

        $table->data($tableData);

        $returnString = '<div class="visor-grid-wrapper">';

        $returnString .= $this->viewService->renderView(
            'ee:_shared/table',
            $table->getViewData()
        );

        $returnString .= '</div>';

        return $returnString;
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
                ->where(
                    'field_id',
                    $this->fieldService->getFieldIdByName($config['modelProperty'])
                )
                ->where('parent_id', $entryModel->getProperty('entry_id'))
                ->get('relationships')
                ->result()
        );

        $children = $entryModel->Children->filter(
            'entry_id',
            'IN',
            $fieldRels->pluck('child_id')
        );

        $visorFilters = $this->filtersFromInputService->get();

        $str = '';

        foreach ($children as $child) {
            /** @var ChannelEntryModel $child */
            $url = $this->cpUrlService->renderUrl(
                "publish/edit/entry/{$child->getProperty('entry_id')}",
                [
                    'visorReturn' => 'true',
                    'visorFilters' => $visorFilters,
                ]
            );

            $thisStr = '<div class="visor-relationship-link">';
            $thisStr .= "<a href=\"{$url}\">";
            $thisStr .= $child->getProperty('title');
            $thisStr .= '</a></div>';

            $str .= $thisStr;
        }

        return $str;
    }
}
