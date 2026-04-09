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
        $count = is_numeric($count = App::blog()->getPosts(['post_status' => App::status()->post()::SCHEDULED], true)->f(0)) ? (int) $count : 0;

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

        $posts_nb = is_numeric($posts_nb = $preferences->posts_nb) ? (int) $posts_nb : 0;

        $list = BackendBehaviors::getScheduledPosts(
            $posts_nb,
            (bool) $preferences->posts_large
        );

        return [
            'ret'  => true,
            'list' => $list,
        ];
    }
}
