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

        $table->setColumns([
            [
                'label' => 'id',
                'encode' => false,
                'sort' => false,
                'type' => Table::COL_ID,
            ],
            [
                'label' => 'title',
                'encode' => false,
                'sort' => false,
                'type' => Table::COL_TEXT,
            ],
            [
                'label' => 'channel',
                'encode' => false,
                'sort' => false,
                'type' => Table::COL_TEXT,
            ],
            [
                'label' => 'date',
                'encode' => false,
                'sort' => false,
                'type' => Table::COL_TEXT,
            ],
            [
                'label' => 'status',
                'encode' => false,
                'sort' => false,
                'type' => Table::COL_STATUS,
            ],
            [
                'type' => Table::COL_CHECKBOX,
            ],
        ]);

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

            $entryDateTime = new \DateTime();
            $entryDateTime->setTimestamp($channelModel->getProperty('entry_date'));

            $tableData[] = [
                $channelModel->getProperty('entry_id'),
                '<strong style="font-style: normal;">' .
                    "<a href=\"{$url}\">{$channelModel->getProperty('title')}</a>" .
                '</strong>',
                $channelModel->Channel->getProperty('channel_title'),
                $entryDateTime->format('n/j/Y g:i A'),
                $channelModel->getProperty('status'),
                [
                    'name' => "entry[{$channelModel->getProperty('entry_id')}]",
                    'value' => 'selected',
                ]
            ];
        }

        $table->setData($tableData);

        return $table;
    }
}
