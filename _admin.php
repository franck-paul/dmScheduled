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

// dead but useful code, in order to have translations
__('Scheduled Dashboard Module') . __('Display scheduled posts on dashboard');

// Dashboard behaviours
dcCore::app()->addBehavior('adminDashboardContents', ['dmScheduledBehaviors', 'adminDashboardContents']);
dcCore::app()->addBehavior('adminDashboardHeaders', ['dmScheduledBehaviors', 'adminDashboardHeaders']);
dcCore::app()->addBehavior('adminDashboardFavsIcon', ['dmScheduledBehaviors', 'adminDashboardFavsIcon']);

dcCore::app()->addBehavior('adminAfterDashboardOptionsUpdate', ['dmScheduledBehaviors', 'adminAfterDashboardOptionsUpdate']);
dcCore::app()->addBehavior('adminDashboardOptionsForm', ['dmScheduledBehaviors', 'adminDashboardOptionsForm']);

# BEHAVIORS
class dmScheduledBehaviors
{
    public static function getScheduledPosts($core, $nb, $large)
    {
        // Get last $nb scheduled posts
        $params = [
            'post_status' => -1,
            'order'       => 'post_dt ASC',
        ];
        if ((int) $nb > 0) {
            $params['limit'] = (int) $nb;
        }
        $rs = dcCore::app()->blog->getPosts($params, false);
        if (!$rs->isEmpty()) {
            $ret = '<ul>';
            while ($rs->fetch()) {
                $ret .= '<li class="line" id="dmsp' . $rs->post_id . '">';
                $ret .= '<a href="post.php?id=' . $rs->post_id . '">' . $rs->post_title . '</a>';
                if ($large) {
                    $ret .= ' (' .
                    __('by') . ' ' . $rs->user_id . ' ' . __('on') . ' ' .
                    dt::dt2str(dcCore::app()->blog->settings->system->date_format, $rs->post_dt) . ' ' .
                    dt::dt2str(dcCore::app()->blog->settings->system->time_format, $rs->post_dt) . ')';
                } else {
                    $ret .= ' (' . dt::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . ')';
                }
                $ret .= '</li>';
            }
            $ret .= '</ul>';
            $ret .= '<p><a href="posts.php?status=-1">' . __('See all scheduled posts') . '</a></p>';

            return $ret;
        }

        return '<p>' . __('No scheduled post') . '</p>';
    }

    private static function countScheduledPosts($core)
    {
        $count = dcCore::app()->blog->getPosts(['post_status' => -1], true)->f(0);
        if ($count) {
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', $count), $count);

            return '</span></a> <a href="posts.php?status=-1"><span class="db-icon-title-dm-scheduled">' . sprintf($str, $count);
        }

        return '';
    }

    public static function adminDashboardFavsIcon($core, $name, $icon)
    {
        dcCore::app()->auth->user_prefs->addWorkspace('dmscheduled');
        if (dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_count && $name == 'posts') {
            // Hack posts title if there is at least one scheduled post
            $str = dmScheduledBehaviors::countScheduledPosts(dcCore::app());
            if ($str != '') {
                $icon[0] .= $str;
            }
        }
    }

    public static function adminDashboardHeaders()
    {
        dcCore::app()->auth->user_prefs->addWorkspace('dmscheduled');

        return
        dcPage::jsJson('dm_scheduled', [
            'dmScheduled_Monitor' => dcCore::app()->auth->user_prefs->dmscheduled->scheduled_monitor,
            'dmScheduled_Counter' => dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_count,
        ]) .
        dcPage::jsModuleLoad('dmScheduled/js/service.js', dcCore::app()->getVersion('dmScheduled')) .
        dcPage::cssModuleLoad('dmScheduled/css/style.css', 'screen', dcCore::app()->getVersion('dmScheduled'));
    }

    public static function adminDashboardContents($core, $contents)
    {
        // Add large modules to the contents stack
        dcCore::app()->auth->user_prefs->addWorkspace('dmscheduled');
        if (dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts) {
            $class = (dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_large ? 'medium' : 'small');
            $ret   = '<div id="scheduled-posts" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF('dmScheduled/icon.png')) . '" alt="" />' . ' ' . __('Scheduled posts') . '</h3>';
            $ret .= dmScheduledBehaviors::getScheduledPosts(
                dcCore::app(),
                dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_nb,
                dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_large
            );
            $ret .= '</div>';
            $contents[] = new ArrayObject([$ret]);
        }
    }

    public static function adminAfterDashboardOptionsUpdate($userID)
    {
        // Get and store user's prefs for plugin options
        dcCore::app()->auth->user_prefs->addWorkspace('dmscheduled');

        try {
            // Scheduled posts
            dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts', !empty($_POST['dmscheduled_posts']), 'boolean');
            dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts_nb', (int) $_POST['dmscheduled_posts_nb'], 'integer');
            dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts_large', empty($_POST['dmscheduled_posts_small']), 'boolean');
            dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_posts_count', !empty($_POST['dmscheduled_posts_count']), 'boolean');
            dcCore::app()->auth->user_prefs->dmscheduled->put('scheduled_monitor', !empty($_POST['dmscheduled_monitor']), 'boolean');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm($core)
    {
        // Add fieldset for plugin options
        dcCore::app()->auth->user_prefs->addWorkspace('dmscheduled');

        echo '<div class="fieldset" id="dmscheduled"><h4>' . __('Scheduled posts on dashboard') . '</h4>' .

        '<p>' .
        form::checkbox('dmscheduled_posts_count', 1, dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_count) . ' ' .
        '<label for="dmscheduled_posts_count" class="classic">' . __('Display count of scheduled posts on posts dashboard icon') . '</label></p>' .

        '<p>' .
        form::checkbox('dmscheduled_posts', 1, dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts) . ' ' .
        '<label for="dmscheduled_posts" class="classic">' . __('Display scheduled posts') . '</label></p>' .

        '<p><label for="dmscheduled_posts_nb" class="classic">' . __('Number of scheduled posts to display:') . '</label>' .
        form::number('dmscheduled_posts_nb', 1, 999, dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_nb) .
        '</p>' .

        '<p>' .
        form::checkbox('dmscheduled_posts_small', 1, !dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_large) . ' ' .
        '<label for="dmscheduled_posts_small" class="classic">' . __('Small screen') . '</label></p>' .

        '<p>' .
        form::checkbox('dmscheduled_monitor', 1, dcCore::app()->auth->user_prefs->dmscheduled->scheduled_monitor) . ' ' .
        '<label for="dmscheduled_monitor" class="classic">' . __('Monitor') . '</label></p>' .

            '</div>';
    }
}
