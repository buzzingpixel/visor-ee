<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use buzzingpixel\visor\facades\ViewFacade;
use buzzingpixel\visor\facades\CpUrlFacade;
use buzzingpixel\visor\facades\RequestFacade;
use buzzingpixel\visor\services\ChannelSelectsService;
use buzzingpixel\visor\controllers\EntryListController;

// Get addon json path
$addOnPath = realpath(__DIR__);

// Get vendor autoload
$vendorAutoloadFile = "{$addOnPath}/vendor/autoload.php";

// Require the autoload file if path exists
if (file_exists($vendorAutoloadFile)) {
    require $vendorAutoloadFile;
}

defined('VISOR_VER') || define('VISOR_VER', '0.0.5');
defined('VISOR_PATH') || define('VISOR_PATH', $addOnPath);

return [
    'author' => 'TJ Draper',
    'author_url' => 'https://buzzingpixel.com',
    'description' => '',
    'name' => 'Visor',
    'namespace' => 'buzzingpixel\visor',
    'settings_exist' => true,
    'version' => VISOR_VER,
    'services' => [
        /**
         * Services
         */
        'RequestService' => function () {
            return new RequestFacade(ee()->input);
        },
        'ViewService' => function () {
            return new ViewFacade(ee('View'));
        },
        'CpUrlService' => function () {
            return new CpUrlFacade(ee('CP/URL'));
        },
        'ChannelSelectsService' => function () {
            /** @var \EE_Session $session */
            $session = ee()->session;

            $service = $session->cache('Visor', 'ChannelSelectsService');

            if (! $service) {
                $service = new ChannelSelectsService(ee('Model'), $session);
            }

            return $service;
        },
    ],
];
