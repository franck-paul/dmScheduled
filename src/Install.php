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

use dcCore;
use dcWorkspace;
use Dotclear\Core\Process;
use Exception;

class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Update
            $old_version = dcCore::app()->getVersion(My::id());
            if (version_compare((string) $old_version, '2.0', '<')) {
                // Rename settings workspace
                if (dcCore::app()->auth->user_prefs->exists('dmscheduled')) {
                    dcCore::app()->auth->user_prefs->delWorkspace(My::id());
                    dcCore::app()->auth->user_prefs->renWorkspace('dmscheduled', My::id());
                }
                // Change settings names (remove scheduled_ prefix in them)
                $rename = function (string $name, dcWorkspace $preferences): void {
                    if ($preferences->prefExists('scheduled_' . $name, true)) {
                        $preferences->rename('scheduled_' . $name, $name);
                    }
                };

                $preferences = My::prefs();
                if ($preferences) {
                    foreach (['posts_count', 'posts_nb', 'posts_large', 'monitor'] as $pref) {
                        $rename($pref, $preferences);
                    }
                    $preferences->rename('scheduled_posts', 'active');
                }
            }

            // Default prefs for pending posts and comments
            $preferences = My::prefs();
            if ($preferences) {
                $preferences->put('active', false, dcWorkspace::WS_BOOL, 'Display scheduled posts', false, true);
                $preferences->put('posts_count', false, dcWorkspace::WS_BOOL, 'Display count of scheduled posts on posts dashboard icon', false, true);
                $preferences->put('posts_nb', 5, dcWorkspace::WS_INT, 'Number of scheduled posts displayed', false, true);
                $preferences->put('posts_large', true, dcWorkspace::WS_BOOL, 'Large display', false, true);
                $preferences->put('monitor', false, dcWorkspace::WS_BOOL, 'Monitor', false, true);
                $preferences->put('interval', 300, dcWorkspace::WS_INT, 'Interval between two refreshes', false, true);
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
