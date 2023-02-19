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

# BEHAVIORS
class dmScheduledBehaviors
{
    public static function getScheduledPosts($core, $nb, $large)
    {
        // Get last $nb scheduled posts
        $params = [
            'post_status' => dcBlog::POST_SCHEDULED,
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
                    $dt = '<time datetime="' . dt::iso8601(strtotime($rs->post_dt), dcCore::app()->auth->getInfo('user_tz')) . '">%s</time>';
                    $ret .= ' (' .
                    __('by') . ' ' . $rs->user_id . ' ' . sprintf($dt, __('on') . ' ' .
                        dt::dt2str(dcCore::app()->blog->settings->system->date_format, $rs->post_dt) . ' ' .
                        dt::dt2str(dcCore::app()->blog->settings->system->time_format, $rs->post_dt)) .
                    ')';
                } else {
                    $ret .= ' (<time datetime="' . dt::iso8601(strtotime($rs->post_dt)) . '">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . '</time>)';
                }
                $ret .= '</li>';
            }
            $ret .= '</ul>';
            $ret .= '<p><a href="posts.php?status=' . dcBlog::POST_SCHEDULED . '">' . __('See all scheduled posts') . '</a></p>';

            return $ret;
        }

        return '<p>' . __('No scheduled post') . '</p>';
    }

    private static function countScheduledPosts()
    {
        $count = dcCore::app()->blog->getPosts(['post_status' => dcBlog::POST_SCHEDULED], true)->f(0);
        if ($count) {
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', $count), $count);

            return '</span></a> <a href="posts.php?status=' . dcBlog::POST_SCHEDULED . '"><span class="db-icon-title-dm-scheduled">' . sprintf($str, $count);
        }

        return '';
    }

    public static function adminDashboardFavsIcon($name, $icon)
    {
        if (dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_count && $name == 'posts') {
            // Hack posts title if there is at least one scheduled post
            $str = dmScheduledBehaviors::countScheduledPosts();
            if ($str != '') {
                $icon[0] .= $str;
            }
        }
    }

    public static function adminDashboardHeaders()
    {
        return
        dcPage::jsJson('dm_scheduled', [
            'dmScheduled_Monitor' => dcCore::app()->auth->user_prefs->dmscheduled->scheduled_monitor,
            'dmScheduled_Counter' => dcCore::app()->auth->user_prefs->dmscheduled->scheduled_posts_count,
        ]) .
        dcPage::jsModuleLoad('dmScheduled/js/service.js', dcCore::app()->getVersion('dmScheduled')) .
        dcPage::cssModuleLoad('dmScheduled/css/style.css', 'screen', dcCore::app()->getVersion('dmScheduled'));
    }

    public static function adminDashboardContents($contents)
    {
        // Add large modules to the contents stack
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

    public static function adminAfterDashboardOptionsUpdate()
    {
        // Get and store user's prefs for plugin options
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

    public static function adminDashboardOptionsForm()
    {
        // Add fieldset for plugin options

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

// Dashboard behaviours
dcCore::app()->addBehaviors([
    'adminDashboardContentsV2'         => [dmScheduledBehaviors::class, 'adminDashboardContents'],
    'adminDashboardHeaders'            => [dmScheduledBehaviors::class, 'adminDashboardHeaders'],
    'adminDashboardFavsIconV2'         => [dmScheduledBehaviors::class, 'adminDashboardFavsIcon'],

    'adminAfterDashboardOptionsUpdate' => [dmScheduledBehaviors::class, 'adminAfterDashboardOptionsUpdate'],
    'adminDashboardOptionsFormV2'      => [dmScheduledBehaviors::class, 'adminDashboardOptionsForm'],
]);
