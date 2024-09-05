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
    '4.3',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'pref' => '#user-favorites.dmscheduled',
        ],

        'details'    => 'https://open-time.net/?q=dmScheduled',
        'support'    => 'https://github.com/franck-paul/dmScheduled',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dmScheduled/main/dcstore.xml',
    ]
);
