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
use Dotclear\Interface\Core\BlogInterface;

class BackendRest
{
    /**
     * Gets the scheduled posts count.
     *
     * @return     array<string, mixed>   The payload.
     */
    public static function getScheduledPostsCount(): array
    {
        $count = App::blog()->getPosts(['post_status' => BlogInterface::POST_SCHEDULED], true)->f(0);

        return [
            'ret'   => true,
            'count' => $count ? sprintf(__('(%d scheduled post)', '(%d scheduled posts)', (int) $count), $count) : '',
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
        if (!$preferences) {
            return [
                'ret' => false,
            ];
        }

        $list = BackendBehaviors::getScheduledPosts(
            (int) $preferences->posts_nb,
            (bool) $preferences->posts_large
        );

        return [
            'ret'  => true,
            'list' => $list,
        ];
    }
}
