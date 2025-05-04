<?php

/**
 * @brief dmScheduled, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
$this->registerModule(
    'Scheduled Dashboard Module',
    'Display scheduled posts on dashboard',
    'Franck Paul',
    '6.1',
    [
        'date'        => '2025-05-04T12:52:08+0200',
        'requires'    => [['core', '2.34']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'pref' => '#user-favorites.dmscheduled',
        ],

        'details'    => 'https://open-time.net/?q=dmScheduled',
        'support'    => 'https://github.com/franck-paul/dmScheduled',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dmScheduled/main/dcstore.xml',
        'license'    => 'gpl2',
    ]
);
