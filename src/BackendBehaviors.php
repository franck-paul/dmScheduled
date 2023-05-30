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

use ArrayObject;
use dcBlog;
use dcCore;
use dcPage;
use dcWorkspace;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Exception;

class BackendBehaviors
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
                    $dt = '<time datetime="' . Date::iso8601(strtotime($rs->post_dt), dcCore::app()->auth->getInfo('user_tz')) . '">%s</time>';
                    $ret .= ' (' .
                    __('by') . ' ' . $rs->user_id . ' ' . sprintf($dt, __('on') . ' ' .
                        Date::dt2str(dcCore::app()->blog->settings->system->date_format, $rs->post_dt) . ' ' .
                        Date::dt2str(dcCore::app()->blog->settings->system->time_format, $rs->post_dt)) .
                    ')';
                } else {
                    $ret .= ' (<time datetime="' . Date::iso8601(strtotime($rs->post_dt)) . '">' . Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . '</time>)';
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
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', (int) $count), (int) $count);

            return '</span></a> <a href="posts.php?status=' . dcBlog::POST_SCHEDULED . '"><span class="db-icon-title-dm-scheduled">' . sprintf($str, $count);
        }

        return '';
    }

    public static function adminDashboardFavsIcon($name, $icon)
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());
        if ($preferences->posts_count && $name == 'posts') {
            // Hack posts title if there is at least one scheduled post
            $str = self::countScheduledPosts();
            if ($str != '') {
                $icon[0] .= $str;
            }
        }
    }

    public static function adminDashboardHeaders()
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

        return
        dcPage::jsJson('dm_scheduled', [
            'dmScheduled_Monitor' => $preferences->monitor,
            'dmScheduled_Counter' => $preferences->posts_count,
        ]) .
        dcPage::jsModuleLoad(My::id() . '/js/service.js', dcCore::app()->getVersion(My::id())) .
        dcPage::cssModuleLoad(My::id() . '/css/style.css', 'screen', dcCore::app()->getVersion(My::id()));
    }

    public static function adminDashboardContents($contents)
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

        // Add large modules to the contents stack
        if ($preferences->active) {
            $class = ($preferences->posts_large ? 'medium' : 'small');
            $ret   = '<div id="scheduled-posts" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(dcPage::getPF(My::id() . '/icon.png')) . '" alt="" />' . ' ' . __('Scheduled posts') . '</h3>';
            $ret .= self::getScheduledPosts(
                dcCore::app(),
                $preferences->posts_nb,
                $preferences->posts_large
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
            $preferences = dcCore::app()->auth->user_prefs->get(My::id());

            $preferences->put('active', !empty($_POST['dmscheduled_active']), dcWorkspace::WS_BOOL);
            $preferences->put('posts_nb', (int) $_POST['dmscheduled_posts_nb'], dcWorkspace::WS_INT);
            $preferences->put('posts_large', empty($_POST['dmscheduled_posts_small']), dcWorkspace::WS_BOOL);
            $preferences->put('posts_count', !empty($_POST['dmscheduled_posts_count']), dcWorkspace::WS_BOOL);
            $preferences->put('monitor', !empty($_POST['dmscheduled_monitor']), dcWorkspace::WS_BOOL);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminDashboardOptionsForm()
    {
        $preferences = dcCore::app()->auth->user_prefs->get(My::id());

        // Add fieldset for plugin options
        echo
        (new Fieldset('dmscheduled'))
        ->legend((new Legend(__('Scheduled posts on dashboard'))))
        ->fields([
            (new Para())->items([
                (new Checkbox('dmscheduled_posts_count', $preferences->posts_count))
                    ->value(1)
                    ->label((new Label(__('Display count of scheduled posts on posts dashboard icon'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmscheduled_active', $preferences->active))
                    ->value(1)
                    ->label((new Label(__('Display scheduled posts'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmscheduled_posts_nb', 1, 999, $preferences->posts_nb))
                    ->label((new Label(__('Number of scheduled posts to display:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->items([
                (new Checkbox('dmscheduled_posts_small', !$preferences->posts_large))
                    ->value(1)
                    ->label((new Label(__('Small screen'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmscheduled_monitor', $preferences->monitor))
                    ->value(1)
                    ->label((new Label(__('Monitor'), Label::INSIDE_TEXT_AFTER))),
            ]),
        ])
        ->render();
    }
}
