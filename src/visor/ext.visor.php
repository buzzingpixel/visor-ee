<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Model\Channel\Channel as ChannelModel;
use EllisLab\ExpressionEngine\Service\CustomMenu\Menu as CustomMenuService;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as ModelQueryBuilder;

/**
 * Class Visor_mcp
 */
class Visor_ext
{
    /** @var string $version */
    public $version = VISOR_VER;

    /** @var EE_Input $inputService */
    private $inputService;

    /** @var EE_URI $uriService */
    private $uriService;

    /** @var URLFactory $cpUrlService */
    private $cpUrlFactory;

    /** @var EE_Functions $eeFunctions */
    private $eeFunctions;

    /** @var ModelFacade $modelFacade */
    private $modelFacade;

    /** @var \EE_Config $eeConfigService */
    private $eeConfigService;

    /** @var \EE_Lang $lang */
    private $lang;

    /** @var \EE_Session */
    private $eeSession;

    public function __construct()
    {
        $this->inputService = ee()->input;
        $this->uriService = ee()->uri;
        $this->cpUrlFactory = ee('CP/URL');
        $this->eeFunctions = ee()->functions;
        $this->modelFacade = ee('Model');
        $this->eeConfigService = ee()->config;
        $this->lang = ee()->lang;
        $this->eeSession = ee()->session;
    }

    /**
     * core_boot hook
     * Deals with cookies for returning to visor on entry submission
     */
    public function core_boot()
    {
        // If this is not the control panel, we know we can stop here
        if (REQ !== 'CP') {
            return;
        }

        // Get cookie
        $visorReturnCookie = $this->inputService->cookie('visorReturn');
        $visorFiltersCookie = json_decode(
            $this->inputService->cookie('visorFilters'),
            true
        );

        if ($visorReturnCookie &&
            $this->uriService->segment(2) === 'publish' &&
            $this->uriService->segment(3) === 'edit' &&
            ! $this->uriService->segment(4)
        ) {
            $this->inputService->delete_cookie('visorReturn');
            $this->inputService->delete_cookie('visorFilters');

            $url = $this->cpUrlFactory->make('addons/settings/visor');

            if ($visorFiltersCookie) {
                $url->setQueryStringVariable(
                    'filter',
                    $visorFiltersCookie
                );
            }

            // Redirect to the requested construct page
            $this->eeFunctions->redirect($url);

            // Stop here
            return;
        }

        // Get return data
        $visorReturn = $this->inputService->get('visorReturn') === 'true';
        $visorFilters = $this->inputService->get('visorFilters') ?: [];

        // If get param present and this is a publish entry page
        if ((
                $this->uriService->segment(2) === 'publish' &&
                $this->uriService->segment(3) === 'edit' &&
                $this->uriService->segment(4) === 'entry'
            ) ||
            (
                $this->uriService->segment(2) === 'publish' &&
                $this->uriService->segment(3) === 'create' &&
                (int) $this->uriService->segment(4)
            )
        ) {
            if ($visorReturn) {
                // Place Save and return to tree button?
                // Set the return data in a cookie
                $this->inputService->set_cookie(
                    'visorReturn',
                    'true',
                    31557600
                );

                $this->inputService->set_cookie(
                    'visorFilters',
                    json_encode($visorFilters),
                    31557600
                );
            }

            // Stop processing
            return;
        }

        // If we made it here, the cookie is no longer useful
        $this->inputService->delete_cookie('visorReturn');
        $this->inputService->delete_cookie('visorFilters');
    }

    /**
     * cp_custom_menu hook
     * @param CustomMenuService $menu
     */
    public function cp_custom_menu(CustomMenuService $menu)
    {
        $this->lang->loadfile('visor');

        /** @var ModelQueryBuilder $channelQuery */
        $channelQuery = $this->modelFacade->get('Channel');

        $channelQuery->filter(
            'channel_id',
            'IN',
            array_keys($this->eeSession->userdata('assigned_channels'))
        );

        $channelQuery->order('channel_title', 'asc');

        /** @var Collection $channels */
        $channels = $channelQuery->all();

        $channelsCount = $channels->count();

        if ($channelsCount < 1) {
            return;
        }

        $title = $this->eeConfigService->item('menuTitle', 'visor') ?:
            lang('visor');

        if ($channelsCount < 2) {
            foreach ($channels as $channel) {
                $menu->addItem(
                    $title,
                    $this->cpUrlFactory
                        ->make(
                            'addons/settings/visor',
                            [
                                'filter' => [
                                    uniqid('', false) => [
                                        'type' => 'channel',
                                        'operator' => 'is',
                                        'value' => $channel->getProperty(
                                            'channel_name'
                                        ),
                                    ],
                                ],
                            ]
                        )
                );
            }

            return;
        }

        $submenu = $menu->addSubmenu($title);

        if ($channelsCount > 5) {
            $submenu->withFilter(lang('filterChannels'));
        }

        $submenu->addItem(
            lang('viewAll'),
            $this->cpUrlFactory->make('addons/settings/visor')
        );

        foreach ($channels as $channel) {
            /** @var ChannelModel $channel */

            $submenu->addItem(
                $channel->getProperty('channel_title'),
                $this->cpUrlFactory
                    ->make(
                        'addons/settings/visor',
                        [
                            'filter' => [
                                uniqid('', false) => [
                                    'type' => 'channel',
                                    'operator' => 'is',
                                    'value' => $channel->getProperty(
                                        'channel_name'
                                    ),
                                ],
                            ],
                        ]
                    )
            );
        }
    }
}
