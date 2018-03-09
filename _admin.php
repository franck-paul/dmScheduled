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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

// dead but useful code, in order to have translations
__('Scheduled Dashboard Module') . __('Display scheduled posts on dashboard');

// Dashboard behaviours
$core->addBehavior('adminDashboardContents', array('dmScheduledBehaviors', 'adminDashboardContents'));
$core->addBehavior('adminDashboardHeaders', array('dmScheduledBehaviors', 'adminDashboardHeaders'));
$core->addBehavior('adminDashboardFavsIcon', array('dmScheduledBehaviors', 'adminDashboardFavsIcon'));

$core->addBehavior('adminAfterDashboardOptionsUpdate', array('dmScheduledBehaviors', 'adminAfterDashboardOptionsUpdate'));
$core->addBehavior('adminDashboardOptionsForm', array('dmScheduledBehaviors', 'adminDashboardOptionsForm'));

# BEHAVIORS
class dmScheduledBehaviors
{
    public static function getScheduledPosts($core, $nb, $large)
    {
        // Get last $nb scheduled posts
        $params = array(
            'post_status' => -1,
            'order'       => 'post_dt ASC'
        );
        if ((integer) $nb > 0) {
            $params['limit'] = (integer) $nb;
        }
        $rs = $core->blog->getPosts($params, false);
        if (!$rs->isEmpty()) {
            $ret = '<ul>';
            while ($rs->fetch()) {
                $ret .= '<li>';
                $ret .= '<a href="post.php?id=' . $rs->post_id . '">' . $rs->post_title . '</a>';
                if ($large) {
                    $ret .= ' (' .
                    __('by') . ' ' . $rs->user_id . ' ' . __('on') . ' ' .
                    dt::dt2str($core->blog->settings->system->date_format, $rs->post_dt) . ' ' .
                    dt::dt2str($core->blog->settings->system->time_format, $rs->post_dt) . ')';
                } else {
                    $ret .= ' (' . dt::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . ')';
                }
                $ret .= '</li>';
            }
            $ret .= '</ul>';
            $ret .= '<p><a href="posts.php?status=-1">' . __('See all scheduled posts') . '</a></p>';
            return $ret;
        } else {
            return '<p>' . __('No scheduled post') . '</p>';
        }
    }

    private static function countScheduledPosts($core)
    {
        $count = $core->blog->getPosts(array('post_status' => -1), true)->f(0);
        if ($count) {
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', $count), $count);
            return '</span></a> <br /><a href="posts.php?status=-1"><span>' . sprintf($str, $count);
        } else {
            return '';
        }
    }

    public static function adminDashboardFavsIcon($core, $name, $icon)
    {
        $core->auth->user_prefs->addWorkspace('dmscheduled');
        if ($core->auth->user_prefs->dmscheduled->scheduled_posts_count && $name == 'posts') {
            // Hack posts title if there is at least one scheduled post
            $str = dmScheduledBehaviors::countScheduledPosts($core);
            if ($str != '') {
                $icon[0] .= $str;
            }
        }
    }

    public static function adminDashboardHeaders()
    {
        global $core;

        $core->auth->user_prefs->addWorkspace('dmscheduled');

        return
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.dmScheduled_Monitor', $core->auth->user_prefs->dmscheduled->scheduled_monitor) .
        "</script>\n" .
        dcPage::jsLoad(urldecode(dcPage::getPF('dmScheduled/js/service.js')), $core->getVersion('dmScheduled')) .
        dcPage::cssLoad(urldecode(dcPage::getPF('dmScheduled/css/style.css')), 'screen', $core->getVersion('dmScheduled'));
    }

    public static function adminDashboardContents($core, $contents)
    {
        // Add large modules to the contents stack
        $core->auth->user_prefs->addWorkspace('dmscheduled');
        if ($core->auth->user_prefs->dmscheduled->scheduled_posts) {
            $class = ($core->auth->user_prefs->dmscheduled->scheduled_posts_large ? 'medium' : 'small');
            $ret   = '<div id="scheduled-posts" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF('dmScheduled/icon.png')) . '" alt="" />' . ' ' . __('Scheduled posts') . '</h3>';
            $ret .= dmScheduledBehaviors::getScheduledPosts($core,
                $core->auth->user_prefs->dmscheduled->scheduled_posts_nb,
                $core->auth->user_prefs->dmscheduled->scheduled_posts_large);
            $ret .= '</div>';
            $contents[] = new ArrayObject(array($ret));
        }
    }

    public static function adminAfterDashboardOptionsUpdate($userID)
    {
        global $core;

        // Get and store user's prefs for plugin options
        $core->auth->user_prefs->addWorkspace('dmscheduled');
        try {
            // Scheduled posts
            $core->auth->user_prefs->dmscheduled->put('scheduled_posts', !empty($_POST['dmscheduled_posts']), 'boolean');
            $core->auth->user_prefs->dmscheduled->put('scheduled_posts_nb', (integer) $_POST['dmscheduled_posts_nb'], 'integer');
            $core->auth->user_prefs->dmscheduled->put('scheduled_posts_large', empty($_POST['dmscheduled_posts_small']), 'boolean');
            $core->auth->user_prefs->dmscheduled->put('scheduled_posts_count', !empty($_POST['dmscheduled_posts_count']), 'boolean');
            $core->auth->user_prefs->dmscheduled->put('scheduled_monitor', !empty($_POST['dmscheduled_monitor']), 'boolean');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm($core)
    {
        // Add fieldset for plugin options
        $core->auth->user_prefs->addWorkspace('dmscheduled');

        echo '<div id="dmscheduled" class="fieldset"><h4>' . __('Scheduled posts on dashboard') . '</h4>' .

        '<p>' .
        form::checkbox('dmscheduled_posts_count', 1, $core->auth->user_prefs->dmscheduled->scheduled_posts_count) . ' ' .
        '<label for="dmscheduled_posts_count" class="classic">' . __('Display count of scheduled posts on posts dashboard icon') . '</label></p>' .

        '<p>' .
        form::checkbox('dmscheduled_posts', 1, $core->auth->user_prefs->dmscheduled->scheduled_posts) . ' ' .
        '<label for="dmscheduled_posts" class="classic">' . __('Display scheduled posts') . '</label></p>' .

        '<p><label for="dmscheduled_posts_nb" class="classic">' . __('Number of scheduled posts to display:') . '</label>' .
        form::field('dmscheduled_posts_nb', 2, 3, (integer) $core->auth->user_prefs->dmscheduled->scheduled_posts_nb) .
        '</p>' .

        '<p>' .
        form::checkbox('dmscheduled_posts_small', 1, !$core->auth->user_prefs->dmscheduled->scheduled_posts_large) . ' ' .
        '<label for="dmscheduled_posts_small" class="classic">' . __('Small screen') . '</label></p>' .

        '<p>' .
        form::checkbox('dmscheduled_monitor', 1, $core->auth->user_prefs->dmscheduled->scheduled_monitor) . ' ' .
        '<label for="dmscheduled_monitor" class="classic">' . __('Monitor') . '</label></p>' .

            '</div>';
    }

}
