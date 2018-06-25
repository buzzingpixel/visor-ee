<?php

namespace buzzingpixel\visor\controllers;

use buzzingpixel\visor\interfaces\CpUrlInterface;
use EllisLab\ExpressionEngine\Service\Alert\Alert;
use buzzingpixel\visor\interfaces\RequestInterface;
use EllisLab\ExpressionEngine\Service\Alert\AlertCollection;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;
use EllisLab\ExpressionEngine\Service\Permission\Permission as PermissionService;

/**
 * Class EntryRemoveController
 */
class EntryRemoveController
{
    /** @var AlertCollection $alertCollection */
    private $alertCollection;

    /** @var RequestInterface $requestService */
    private $requestService;

    /** @var \EE_Functions $eeFunctions */
    private $eeFunctions;

    /** @var CpUrlInterface $cpUrlService */
    private $cpUrlService;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var PermissionService $permissionService */
    private $permissionService;

    /** @var \EE_Session $eeSession */
    private $eeSession;

    /**
     * EntryRemoveController constructor
     * @param AlertCollection $alertCollection
     * @param RequestInterface $requestService
     * @param \EE_Functions $eeFunctions
     * @param CpUrlInterface $cpUrlService
     * @param ModelFacade $modelFacade
     * @param PermissionService $permissionService
     * @param \EE_Session $eeSession
     */
    public function __construct(
        AlertCollection $alertCollection,
        RequestInterface $requestService,
        \EE_Functions $eeFunctions,
        CpUrlInterface $cpUrlService,
        ModelFacade $modelFacade,
        PermissionService $permissionService,
        \EE_Session $eeSession
    ) {
        $this->alertCollection = $alertCollection;
        $this->requestService = $requestService;
        $this->eeFunctions = $eeFunctions;
        $this->cpUrlService = $cpUrlService;
        $this->modelFacade = $modelFacade;
        $this->permissionService = $permissionService;
        $this->eeSession = $eeSession;
    }

    /**
     * Deletes entries specified by request input
     */
    public function __invoke()
    {
        $this->run();
    }

    /**
     * Deletes entries specified by request input
     */
    public function run()
    {
        /** @var Alert $alert */
        $alert = $this->alertCollection->make('visor');

        $entryIds = (array) $this->requestService->post('entry');

        if (! $entryIds) {
            $alert->asIssue();
            $alert->withTitle(lang('error'));
            $alert->addToBody(lang('noEntriesSelected'));
            $alert->defer();
            $this->eeFunctions->redirect($this->requestService->post(
                'redirect',
                $this->getFullUrlToPage(),
                false
            ));
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
        $this->eeFunctions->redirect($this->requestService->post(
            'redirect',
            $this->getFullUrlToPage(),
            false
        ));
        exit();
    }

    /**
     * Gets the full URL to this page
     * @return string
     */
    private function getFullUrlToPage()
    {
        $filters = $this->requestService->get('filter', []);

        if (! is_array($filters)) {
            $filters = [];
        }

        return $this->cpUrlService->renderUrl(
            'addons/settings/visor',
            ['filter' => array_values($filters)]
        );
    }
}
