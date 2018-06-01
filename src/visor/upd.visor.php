<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license unlicensed
 */

use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;

/**
 * Class Visor_upd
 */
class Visor_upd
{
    private static $hooksMethods = [
        'cp_custom_menu' => 'cp_custom_menu',
    ];

    /** @var QueryBuilder $queryBuilder */
    private $queryBuilder;

    /**
     * Visor_upd constructor
     */
    public function __construct()
    {
        $this->queryBuilder = ee('db');
    }

    /**
     * Installs the module
     * @return bool
     */
    public function install()
    {
        foreach (self::$hooksMethods as $hook => $method) {
            $this->queryBuilder->insert('extensions', [
                'class' => 'Visor_ext',
                'method' => $method,
                'hook' => $hook,
                'settings' => '',
                'priority' => 10,
                'version' => VISOR_VER,
                'enabled' => 'y',
            ]);
        }

        $this->queryBuilder->insert('modules', [
            'module_name' => 'Visor',
            'module_version' => VISOR_VER,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n',
        ]);

        return true;
    }

    /**
     * Uninstalls the module
     * @return bool
     */
    public function uninstall()
    {
        $this->queryBuilder->delete('extensions', [
            'class' => 'Visor_ext'
        ]);

        $this->queryBuilder->delete('modules', [
            'module_name' => 'Visor',
        ]);

        return true;
    }

    /**
     * Updates the module
     * @return bool
     */
    public function update()
    {
        $this->queryBuilder->update(
            'extensions',
            [
                'version' => VISOR_VER,
            ],
            [
                'class' => 'Visor_ext'
            ]
        );

        $this->queryBuilder->update(
            'modules',
            [
                'module_version' => VISOR_VER,
            ],
            [
                'module_name' => 'Visor'
            ]
        );

        return true;
    }
}
