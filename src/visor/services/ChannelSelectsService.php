<?php

namespace buzzingpixel\visor\services;

use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Model\Channel\Channel as ChannelModel;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;

/**
 * Class ChannelSelectsService
 */
class ChannelSelectsService
{
    /** @var array $selects */
    private $selects;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var \EE_Session */
    private $eeSession;

    /**
     * ChannelSelectsService constructor
     * @param ModelFacade $modelFacade
     * @param \EE_Session $eeSession
     */
    public function __construct(
        ModelFacade $modelFacade,
        \EE_Session $eeSession
    ) {
        $this->modelFacade = $modelFacade;
        $this->eeSession = $eeSession;
    }

    /**
     * Channel Selects Invocation
     * @return array
     */
    public function __invoke()
    {
        return $this->get();
    }

    /**
     * Gets channel selects
     * @return null
     */
    public function get()
    {
        if ($this->selects === null) {
            $this->selects = $this->getSelects();
        }

        return $this->selects;
    }

    /**
     * Populates selects
     * @return array
     */
    private function getSelects()
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
            $channelName = $model->getProperty('channel_name');
            $channelTitle = $model->getProperty('channel_title');
            $channelSelects[$channelName] = $channelTitle;
        }

        return $channelSelects;
    }
}
