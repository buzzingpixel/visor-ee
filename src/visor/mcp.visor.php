<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntryModel;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;

/**
 * Class Visor_mcp
 */
class Visor_mcp
{
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
        return [
            'heading' => lang('Visor'),
            'body' => $this->viewFactory->make('visor:Visor')
                ->render([
                    'viewFactory' => $this->viewFactory,
                    'tableViewData' => $this
                        ->populateTableData(
                            $this->createTable(),
                            $this->getEntryModelCollection()
                        )
                        ->viewData(),
                ])
        ];
    }

    /**
     * Gets channel collection
     * @return ModelCollection
     */
    private function getEntryModelCollection()
    {
        $limit = $this->inputService->get('limit', 25);

        /** @var ModelQueryBuilder $channelModelBuilder */
        $channelModelBuilder = $this->modelFacade->get('ChannelEntry');
        $channelModelBuilder->order('entry_date', 'desc');
        $channelModelBuilder->limit($limit);

        return $channelModelBuilder->all();
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
}
