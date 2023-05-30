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
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::BACKEND);

        // dead but useful code, in order to have translations
        __('Scheduled Dashboard Module') . __('Display scheduled posts on dashboard');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // Dashboard behaviours
        dcCore::app()->addBehaviors([
            'adminDashboardContentsV2' => [BackendBehaviors::class, 'adminDashboardContents'],
            'adminDashboardHeaders'    => [BackendBehaviors::class, 'adminDashboardHeaders'],
            'adminDashboardFavsIconV2' => [BackendBehaviors::class, 'adminDashboardFavsIcon'],

            'adminAfterDashboardOptionsUpdate' => [BackendBehaviors::class, 'adminAfterDashboardOptionsUpdate'],
            'adminDashboardOptionsFormV2'      => [BackendBehaviors::class, 'adminDashboardOptionsForm'],
        ]);

        // Register REST methods
        dcCore::app()->rest->addFunction('dmScheduledPostsCount', [BackendRest::class, 'getScheduledPostsCount']);
        dcCore::app()->rest->addFunction('dmScheduledCheck', [BackendRest::class, 'checkScheduled']);
        dcCore::app()->rest->addFunction('dmLastScheduledRows', [BackendRest::class, 'getLastScheduledRows']);

        return true;
    }
}
