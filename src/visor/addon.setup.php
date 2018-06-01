<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

// Get addon json path
$addOnPath = realpath(__DIR__);

// Get vendor autoload
$vendorAutoloadFile = "{$addOnPath}/vendor/autoload.php";

// Require the autoload file if path exists
if (file_exists($vendorAutoloadFile)) {
    require $vendorAutoloadFile;
}

defined('VISOR_VER') || define('VISOR_VER', '0.0.1');

return [
    'author' => 'TJ Draper',
    'author_url' => 'https://buzzingpixel.com',
    'description' => '',
    'name' => 'Visor',
    'namespace' => 'buzzingpixel\visor',
    'settings_exist' => false,
    'version' => VISOR_VER,
    'services' => [
    ],
];
