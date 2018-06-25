<?php

namespace buzzingpixel\visor\services;

use buzzingpixel\visor\interfaces\RequestInterface;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use buzzingpixel\visor\services\FiltersFromInputService;
use EllisLab\ExpressionEngine\Library\CP\URL as UrlObject;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;
use EllisLab\ExpressionEngine\Service\Permission\Permission as PermissionService;

/**
 * Class EntrySelectionService
 */
class EntrySelectionService
{
    const PAGE_LIMIT = 25;

    /** @var RequestInterface $requestService */
    private $requestService;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var \EE_Session */
    private $eeSession;

    /** @var PermissionService $permissionService */
    private $permissionService;

    /** @var FiltersFromInputService $filtersFromInputService */
    private $filtersFromInputService;

    /**
     * EntrySelectionService constructor
     * @param RequestInterface $requestService
     * @param ModelFacade $modelFacade
     * @param \EE_Session $eeSession
     * @param PermissionService $permissionService
     * @param FiltersFromInputService $filtersFromInputService
     */
    public function __construct(
        RequestInterface $requestService,
        ModelFacade $modelFacade,
        \EE_Session $eeSession,
        PermissionService $permissionService,
        FiltersFromInputService $filtersFromInputService
    ) {
        $this->requestService = $requestService;
        $this->modelFacade = $modelFacade;
        $this->eeSession = $eeSession;
        $this->permissionService = $permissionService;
        $this->filtersFromInputService = $filtersFromInputService;
    }

    /**
     * Gets the entry model collection from request input
     * @return ModelCollection
     */
    public function getEntryModelCollectionFromRequest()
    {
        $limit = $this->requestService->get('limit', self::PAGE_LIMIT);
        $page  = $this->requestService->get('page', 1);

        $channelModelBuilder = $this->getEntryModelBuilder();
        $channelModelBuilder->order('entry_date', 'desc');
        $channelModelBuilder->limit($limit);
        $channelModelBuilder->offset(($page * $limit) - $limit);

        return $channelModelBuilder->all();
    }

    /**
     * Gets pagination
     * @param UrlObject $fullPageUrl
     * @return string
     */
    public function getPagination($fullPageUrl)
    {
        $limit = $this->requestService->get('limit', self::PAGE_LIMIT);

        $channelModelBuilder = $this->getEntryModelBuilder();

        /** @var Pagination $pagination */
        $pagination = ee('CP/Pagination', $channelModelBuilder->count());
        $pagination->perPage($limit);
        $pagination->currentPage($this->requestService->get('page', 1));

        return $pagination->render($fullPageUrl);
    }

    /**
     * Gets the Entry Model Query Builder
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

        $filters = $this->filtersFromInputService->get();

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
}
