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
declare(strict_types=1);

namespace Dotclear\Plugin\dmScheduled;

use dcBlog;
use dcCore;

class BackendRest
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
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());
        $list        = BackendBehaviors::getScheduledPosts(
            dcCore::app(),
            $preferences->posts_nb,
            $preferences->posts_large
        );

        return [
            'ret'  => true,
            'list' => $list,
        ];
    }
}
