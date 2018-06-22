<?php

namespace buzzingpixel\visor\services;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Class ColumnConfigService
 */
class ColumnConfigService
{
    /** @var FiltersFromInputService $filtersFromInputService */
    private $filtersFromInputService;

    /** @var \EE_Config $eeConfigService */
    private $eeConfigService;

    /** @var array $defaultColumns */
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
     * ColumnConfigService constructor
     * @param FiltersFromInputService $filtersFromInputService
     * @param \EE_Config $eeConfigService
     */
    public function __construct(
        FiltersFromInputService $filtersFromInputService,
        \EE_Config $eeConfigService
    ) {
        $this->filtersFromInputService = $filtersFromInputService;
        $this->eeConfigService = $eeConfigService;
    }

    /**
     * Get column config
     * @return array
     */
    public function __invoke()
    {
        return $this->get();
    }

    /**
     * Get column config
     * @return array
     */
    public function get()
    {
        $filters = $this->filtersFromInputService->get();

        $channel = null;

        if (count($filters['channels']) === 1) {
            $channel = $filters['channels'][0];
        }

        $columnConfig = $this->eeConfigService->item('channelConfig', 'visor') ?: [];
        return isset($columnConfig[$channel]) ?
            $columnConfig[$channel] :
            self::$defaultColumns;
    }
}
