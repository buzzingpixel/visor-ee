<?php

namespace buzzingpixel\visor\services;

use EllisLab\ExpressionEngine\Library\CP\Table;
use buzzingpixel\visor\services\ColumnConfigService;
use buzzingpixel\visor\services\FiltersFromInputService;

/**
 * Class FilterTypesService
 */
class FilterTypesService
{
    /** @var ColumnConfigService $columnConfigService */
    private $columnConfigService;

    /** @var FiltersFromInputService $filtersFromInputService */
    private $filtersFromInputService;

    /**
     * FilterTypesService constructor
     * @param ColumnConfigService $columnConfigService
     * @param FiltersFromInputService $filtersFromInputService
     */
    public function __construct(
        ColumnConfigService $columnConfigService,
        FiltersFromInputService $filtersFromInputService
    ) {
        $this->columnConfigService = $columnConfigService;
        $this->filtersFromInputService = $filtersFromInputService;
    }

    /**
     * Gets filter types
     * @return array
     */
    public function __invoke()
    {
        return $this->get();
    }

    /**
     * Gets filter types
     * @return array
     */
    public function get()
    {
        $config = $this->columnConfigService->get();

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
            $subItems = isset($configItem['gridItems']) ?
                $configItem['gridItems'] :
                null;

            if (! $subItems) {
                $subItems = isset($configItem['matrixItems']) ?
                    $configItem['matrixItems'] :
                    null;
            }

            if ($subItems !== null) {
                $appropriateTopLevelItemsSet = is_array($subItems) && isset(
                    $configItem['label'],
                    $configItem['modelProperty']
                );

                if (! $appropriateTopLevelItemsSet) {
                    continue;
                }

                $subSet = [];

                foreach ($subItems as $item) {
                    $appropriateItemsSet = isset(
                        $item['label'],
                        $item['modelProperty'],
                        $item['filter']
                    );

                    if (! $appropriateItemsSet || ! $item['filter']) {
                        continue;
                    }

                    $val = "{$configItem['modelProperty']}.{$item['modelProperty']}";

                    $subSet[] = [
                        'label' => $item['label'],
                        'value' => $val
                    ];

                    $filtersSet[$val] = $val;
                }

                if (! $subSet) {
                    continue;
                }

                $filterTypes[] = [
                    'label' => lang($configItem['label']),
                    'subSet' => $subSet,
                ];

                continue;
            }

            $appropriateItemsSet = isset(
                $configItem['filter'],
                $configItem['label'],
                $configItem['modelProperty']
            );

            if (! $appropriateItemsSet || ! $configItem['filter']) {
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

        /** @var array $inputFilters */
        $inputFilters = $this->filtersFromInputService->get()['standard'];

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
