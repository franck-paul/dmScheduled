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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!dcCore::app()->newVersion(basename(__DIR__), dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version'))) {
    return;
}

try {
    // Default prefs for pending posts and comments
    dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts', false, 'boolean', 'Display scheduled posts', false, true);
    dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts_count', false, 'boolean', 'Display count of scheduled posts on posts dashboard icon', false, true);
    dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts_nb', 5, 'integer', 'Number of scheduled posts displayed', false, true);
    dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts_large', true, 'boolean', 'Large display', false, true);
    dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_monitor', false, 'boolean', 'Monitor', false, true);

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
