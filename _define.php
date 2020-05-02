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

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "Scheduled Dashboard Module",           // Name
    "Display scheduled posts on dashboard", // Description
    "Franck Paul",                          // Author
    '0.6.1',                                // Version
    [
        'requires'    => [['core', '2.16']],
        'permissions' => 'admin',                                      // Permissions
        'type'        => 'plugin',                                     // Type
        'details'     => 'https://open-time.net/?q=dmScheduled',       // Details URL
        'support'     => 'https://github.com/franck-paul/dmScheduled', // Support URL
        'settings'    => [                                             // Settings
            'pref' => '#user-favorites.dmscheduled'
        ]
    ]
);
