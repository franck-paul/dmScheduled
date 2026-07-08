<?php

/**
 * @brief dmScheduled, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dmScheduled;

use Dotclear\App;

class BackendRest
{
    /**
     * Gets the scheduled posts count.
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function getScheduledPostsCount(): array
    {
        $count = App::blog()->getPosts(['post_status' => App::status()->post()::SCHEDULED], true)->cardinal();

        return [
            'ret'   => true,
            'count' => $count > 0 ? sprintf(__('(%d scheduled post)', '(%d scheduled posts)', $count), $count) : '',
        ];
    }

    /**
     * Serve method to check if some entries need to be published.
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function checkScheduled(): array
    {
        App::blog()->publishScheduledEntries();

        return [
            'ret' => true,
        ];
    }

    /**
     * Gets the last scheduled rows.
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function getLastScheduledRows(): array
    {
        $preferences = My::prefs();

        $list = BackendBehaviors::getScheduledPosts(
            $preferences->getInt('posts_nb', false),
            $preferences->getBool('posts_large', false)
        );

        return [
            'ret'  => true,
            'list' => $list,
        ];
    }
}
