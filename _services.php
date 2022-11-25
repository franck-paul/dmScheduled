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

class dmScheduledRest
{
    /**
     * Gets the scheduled posts count.
     *
     * @return     array   The payload.
     */
    public static function getScheduledPostsCount(): array
    {
        $count = dcCore::app()->blog->getPosts(['post_status' => dcBlog::POST_SCHEDULED], true)->f(0);

        return [
            'ret'   => true,
            'count' => $count ? sprintf(__('(%d scheduled post)', '(%d scheduled posts)', $count), $count) : '',
        ];
    }

    /**
     * Serve method to check if some entries need to be published.
     *
     * @return     array   The payload.
     */
    public static function checkScheduled(): array
    {
        dcCore::app()->blog->publishScheduledEntries();

        return [
            'ret' => true,
        ];
    }

    /**
     * Gets the last scheduled rows.
     *
     * @return     array   The payload.
     */
    public static function getLastScheduledRows(): array
    {
        dcCore::app()->auth->user_prefs->addWorkspace('dmscheduled');
        $list = dmScheduledBehaviors::getScheduledPosts(
            dcCore::app(),
            dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_nb,
            dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_large
        );

        return [
            'ret'  => true,
            'list' => $list,
        ];
    }
}
