<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use EllisLab\ExpressionEngine\Service\CustomMenu\Menu as CustomMenuService;

/**
 * Class Visor_mcp
 */
class Visor_ext
{
    /** @var string $version */
    public $version = VISOR_VER;

    /**
     * cp_custom_menu hook
     * @param CustomMenuService $menu
     */
    public function cp_custom_menu(CustomMenuService $menu)
    {
    }
}
