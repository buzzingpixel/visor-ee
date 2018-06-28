<?php

namespace buzzingpixel\visor\services;

use buzzingpixel\visor\services\FieldService;
use buzzingpixel\visor\interfaces\RequestInterface;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use buzzingpixel\visor\services\FiltersFromInputService;
use EllisLab\ExpressionEngine\Library\CP\URL as UrlObject;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;
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

    /** @var QueryBuilder $queryBuilder */
    private $queryBuilder;

    /** @var FieldService $fieldService */
    private $fieldService;

    /**
     * EntrySelectionService constructor
     * @param RequestInterface $requestService
     * @param ModelFacade $modelFacade
     * @param \EE_Session $eeSession
     * @param PermissionService $permissionService
     * @param FiltersFromInputService $filtersFromInputService
     * @param QueryBuilder $queryBuilder
     * @param FieldService $fieldService
     */
    public function __construct(
        RequestInterface $requestService,
        ModelFacade $modelFacade,
        \EE_Session $eeSession,
        PermissionService $permissionService,
        FiltersFromInputService $filtersFromInputService,
        QueryBuilder $queryBuilder,
        FieldService $fieldService
    ) {
        $this->requestService = $requestService;
        $this->modelFacade = $modelFacade;
        $this->eeSession = $eeSession;
        $this->permissionService = $permissionService;
        $this->filtersFromInputService = $filtersFromInputService;
        $this->queryBuilder = $queryBuilder;
        $this->fieldService = $fieldService;
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
        $applicableEntryIds = $this->getApplicableEntryIds();

        /** @var ModelQueryBuilder $channelModelBuilder */
        $channelModelBuilder = $this->modelFacade->get('ChannelEntry');

        if (! $applicableEntryIds) {
            $channelModelBuilder->filter('entry_id', 'asdf');

            return $channelModelBuilder;
        }

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
            $fieldName = $filter['type'];

            if (strpos($fieldName, '.') !== false) {
                $fieldArr = explode('.', $fieldName);
                $fieldName = $fieldArr[0];
            }

            $fieldType = $this->fieldService->getFieldTypeByName($fieldName);

            if ($fieldType === 'grid' || $fieldType === 'matrix') {
                continue;
            }

            $fieldId = $this->fieldService->getFieldIdByName($fieldName);

            if ($fieldId) {
                $fieldName = "field_id_{$fieldId}";
            }

            if ($filter['operator'] === 'contains') {
                $channelModelBuilder->filter(
                    $fieldName,
                    'LIKE',
                    '%' . $filter['value'] . '%'
                );

                continue;
            }

            $channelModelBuilder->filter($fieldName, $filter['value']);
        }

        $channelModelBuilder->filter('entry_id', 'IN', $applicableEntryIds);

        return $channelModelBuilder;
    }

    /**
     * Gets the applicable entry IDs
     * @return array
     */
    private function getApplicableEntryIds()
    {
        $filters = $this->filtersFromInputService->get()['standard'];

        $query = $this->queryBuilder->select('CT.entry_id')
            ->from('channel_titles as CT');

        foreach ($filters as $filter) {
            $fieldName = $filter['type'];
            $subFieldName = null;

            if (strpos($fieldName, '.') !== false) {
                $fieldArr = explode('.', $fieldName);
                $fieldName = $fieldArr[0];
                $subFieldName = $fieldArr[1];
            }


            $fieldType = $this->fieldService->getFieldTypeByName($fieldName);
            $fieldId = $this->fieldService->getFieldIdByName($fieldName);
            $op = $filter['operator'];

            if (! $fieldType) {
                continue;
            }

            if ($fieldType === 'grid') {
                if (! $subFieldName) {
                    continue;
                }

                $query->join(
                    "channel_grid_field_{$fieldId} as {$fieldName}_{$subFieldName}",
                    "CT.entry_id = {$fieldName}_{$subFieldName}.entry_id",
                    'LEFT'
                );

                $gridColId = $this->fieldService->getGridColId(
                    $fieldId,
                    $subFieldName
                );

                if (empty($filter['value'])) {
                    $query->start_group();

                    $query->where(
                        "{$fieldName}_{$subFieldName}.col_id_{$gridColId}",
                        ''
                    );

                    $query->or_where(
                        "{$fieldName}_{$subFieldName}.col_id_{$gridColId}",
                        null
                    );

                    $query->end_group();

                    continue;
                }

                if ($op === 'contains') {
                    $query->like(
                        "{$fieldName}_{$subFieldName}.col_id_{$gridColId}",
                        $filter['value']
                    );

                    continue;
                }

                $query->where(
                    "{$fieldName}_{$subFieldName}.col_id_{$gridColId}",
                    $filter['value']
                );

                continue;
            }

            if ($fieldType === 'matrix') {
                if (! $subFieldName) {
                    continue;
                }

                $query->join(
                    "matrix_data as {$fieldName}_{$subFieldName}",
                    "CT.entry_id = {$fieldName}_{$subFieldName}.entry_id"
                );

                $matrixColId = $this->fieldService->getMatrixColId(
                    $fieldId,
                    $subFieldName
                );

                if (empty($filter['value'])) {
                    $query->start_group();

                    $query->where(
                        "{$fieldName}_{$subFieldName}.col_id_{$matrixColId}",
                        ''
                    );

                    $query->or_where(
                        "{$fieldName}_{$subFieldName}.col_id_{$matrixColId}",
                        null
                    );

                    $query->end_group();

                    continue;
                }

                if ($op === 'contains') {
                    $query->like(
                        "{$fieldName}_{$subFieldName}.col_id_{$matrixColId}",
                        $filter['value']
                    );

                    continue;
                }

                $query->where(
                    "{$fieldName}_{$subFieldName}.col_id_{$matrixColId}",
                    $filter['value']
                );

                continue;
            }

            // if ($op === 'contains') {
            //     $query->like("CD.field_id_{$fieldId}", $filter['value']);
            //
            //     continue;
            // }
            //
            // $query->where("CD.field_id_{$fieldId}", $filter['value']);
        }

        $entryIds = [];

        foreach ($query->get()->result() as $item) {
            $entryIds[] = $item->entry_id;
        }

        return $entryIds;
    }
}
