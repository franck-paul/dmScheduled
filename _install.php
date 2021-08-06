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

$new_version = $core->plugins->moduleInfo('dmScheduled', 'version');
$old_version = $core->getVersion('dmScheduled');

if (version_compare($old_version, $new_version, '>=')) {
    return;
}

try {
    $core->auth->user_prefs->addWorkspace('dmscheduled');

    // Default prefs for pending posts and comments
    $core->auth->user_prefs->dmscheduled->put('scheduled_posts', false, 'boolean', 'Display scheduled posts', false, true);
    $core->auth->user_prefs->dmscheduled->put('scheduled_posts_count', false, 'boolean', 'Display count of scheduled posts on posts dashboard icon', false, true);
    $core->auth->user_prefs->dmscheduled->put('scheduled_posts_nb', 5, 'integer', 'Number of scheduled posts displayed', false, true);
    $core->auth->user_prefs->dmscheduled->put('scheduled_posts_large', true, 'boolean', 'Large display', false, true);
    $core->auth->user_prefs->dmscheduled->put('scheduled_monitor', false, 'boolean', 'Monitor', false, true);

    $core->setVersion('dmScheduled', $new_version);

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

return false;
