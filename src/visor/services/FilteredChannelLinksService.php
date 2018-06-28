<?php

namespace buzzingpixel\visor\services;

use buzzingpixel\visor\interfaces\CpUrlInterface;
use buzzingpixel\visor\interfaces\RequestInterface;
use buzzingpixel\visor\services\FiltersFromInputService;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Model\Channel\Channel as ChannelModel;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;
use EllisLab\ExpressionEngine\Service\Permission\Permission as PermissionService;

/**
 * Class FilteredChannelLinksService
 */
class FilteredChannelLinksService
{
    /** @var array $links */
    private $links;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var \EE_Session */
    private $eeSession;

    /** @var PermissionService $permissionService */
    private $permissionService;

    /** @var FiltersFromInputService $filtersFromInputService  */
    private $filtersFromInputService;

    /** @var RequestInterface $requestService */
    private $requestService;

    /** @var CpUrlInterface $cpUrlInterface */
    private $cpUrlInterface;

    /**
     * ChannelSelectsService constructor
     * @param ModelFacade $modelFacade
     * @param \EE_Session $eeSession
     * @param PermissionService $permissionService
     * @param FiltersFromInputService $filtersFromInputService
     * @param RequestInterface $requestService
     * @param CpUrlInterface $cpUrlInterface
     */
    public function __construct(
        ModelFacade $modelFacade,
        \EE_Session $eeSession,
        PermissionService $permissionService,
        FiltersFromInputService $filtersFromInputService,
        RequestInterface $requestService,
        CpUrlInterface $cpUrlInterface
    ) {
        $this->modelFacade = $modelFacade;
        $this->eeSession = $eeSession;
        $this->permissionService = $permissionService;
        $this->filtersFromInputService = $filtersFromInputService;
        $this->requestService = $requestService;
        $this->cpUrlInterface = $cpUrlInterface;
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
        if ($this->links === null) {
            $this->links = $this->getLinks();
        }

        return $this->links;
    }

    /**
     * Populates selects
     * @return array
     */
    private function getLinks()
    {
        $assignedChannels = $this->eeSession->userdata('assigned_channels');


        if (! $assignedChannels ||
            ! $this->permissionService->has('can_create_entries')
        ) {
            return [];
        }

        /** @var ModelQueryBuilder $channelQuery */
        $channelQuery = $this->modelFacade->get('Channel');

        $channelQuery->order('channel_title', 'asc');

        $filters = $this->filtersFromInputService->get();

        $channelQuery->filter(
            'channel_id',
            'IN',
            array_keys($assignedChannels)
        );

        if ($filters['channels']) {
            $channelQuery->filter('channel_name', 'IN', $filters['channels']);
        }

        $channels = $channelQuery->all();

        $links = [];

        $visorFilters = $this->requestService->get('filter', []);

        foreach ($channels as $channel) {
            /** @var ChannelModel $channel */
            $links[] = [
                'title' => $channel->getProperty('channel_title'),
                'link' => $this->cpUrlInterface->renderUrl(
                    "publish/create/{$channel->getProperty('channel_id')}",
                    [
                        'visorReturn' => 'true',
                        'visorFilters' => $visorFilters,
                    ]
                ),
            ];
        }

        return $links;
    }
}
