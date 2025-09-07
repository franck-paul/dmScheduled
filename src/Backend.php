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
use Dotclear\Helper\Process\TraitProcess;

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('Scheduled Dashboard Module');
        __('Display scheduled posts on dashboard');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Dashboard behaviours
        App::behavior()->addBehaviors([
            'adminDashboardContentsV2' => BackendBehaviors::adminDashboardContents(...),
            'adminDashboardHeaders'    => BackendBehaviors::adminDashboardHeaders(...),
            'adminDashboardFavsIconV2' => BackendBehaviors::adminDashboardFavsIcon(...),

            'adminAfterDashboardOptionsUpdate' => BackendBehaviors::adminAfterDashboardOptionsUpdate(...),
            'adminDashboardOptionsFormV2'      => BackendBehaviors::adminDashboardOptionsForm(...),
        ]);

        // Register REST methods
        App::rest()->addFunction('dmScheduledPostsCount', BackendRest::getScheduledPostsCount(...));
        App::rest()->addFunction('dmScheduledCheck', BackendRest::checkScheduled(...));
        App::rest()->addFunction('dmLastScheduledRows', BackendRest::getLastScheduledRows(...));

        return true;
    }
}
