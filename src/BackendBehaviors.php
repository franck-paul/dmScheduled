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
use Dotclear\App;
use Dotclear\Core\Backend\Page;
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
    public static function getScheduledPosts(int $nb, bool $large): string
    {
        // Get last $nb scheduled posts
        $params = [
            'post_status' => App::blog()::POST_SCHEDULED,
            'order'       => 'post_dt ASC',
        ];
        if ((int) $nb > 0) {
            $params['limit'] = (int) $nb;
        }

        $rs = App::blog()->getPosts($params, false);
        if (!$rs->isEmpty()) {
            $ret = '<ul>';
            while ($rs->fetch()) {
                $ret .= '<li class="line" id="dmsp' . $rs->post_id . '">';
                $ret .= '<a href="' . App::backend()->url()->get('admin.post', ['id' => $rs->post_id]) . '">' . $rs->post_title . '</a>';
                if ($large) {
                    $dt = '<time datetime="' . Date::iso8601((int) strtotime($rs->post_dt), App::auth()->getInfo('user_tz')) . '">%s</time>';
                    $ret .= ' (' .
                    __('by') . ' ' . $rs->user_id . ' ' . sprintf($dt, __('on') . ' ' .
                        Date::dt2str(App::blog()->settings()->system->date_format, $rs->post_dt) . ' ' .
                        Date::dt2str(App::blog()->settings()->system->time_format, $rs->post_dt)) .
                    ')';
                } else {
                    $ret .= ' (<time datetime="' . Date::iso8601((int) strtotime($rs->post_dt)) . '">' . Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . '</time>)';
                }

                $ret .= '</li>';
            }

            $ret .= '</ul>';

            return $ret . ('<p><a href="' . App::backend()->url()->get('admin.posts', ['status' => App::blog()::POST_SCHEDULED]) . '">' . __('See all scheduled posts') . '</a></p>');
        }

        return '<p>' . __('No scheduled post') . '</p>';
    }

    private static function countScheduledPosts(): string
    {
        $count = App::blog()->getPosts(['post_status' => App::blog()::POST_SCHEDULED], true)->f(0);
        if ($count) {
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', (int) $count), (int) $count);

            return '</span></a> <a href="' . App::backend()->url()->get('admin.posts', ['status' => App::blog()::POST_SCHEDULED]) . '"><span class="db-icon-title-dm-scheduled">' . sprintf($str, $count);
        }

        return '';
    }

    /**
     * @param      string                       $name   The name
     * @param      ArrayObject<string, mixed>   $icon   The icon
     *
     * @return     string
     */
    public static function adminDashboardFavsIcon(string $name, ArrayObject $icon): string
    {
        $preferences = My::prefs();
        if ($preferences->posts_count && $name === 'posts') {
            // Hack posts title if there is at least one scheduled post
            $str = self::countScheduledPosts();
            if ($str != '') {
                $icon[0] .= $str;
            }
        }

        return '';
    }

    public static function adminDashboardHeaders(): string
    {
        $preferences = My::prefs();

        return
        Page::jsJson('dm_scheduled', [
            'dmScheduled_Monitor'  => $preferences->monitor,
            'dmScheduled_Counter'  => $preferences->posts_count,
            'dmScheduled_Interval' => ($preferences->interval ?? 300),
        ]) .
        My::jsLoad('service.js') .
        My::cssLoad('style.css');
    }

    /**
     * @param      ArrayObject<int, ArrayObject<int, non-falsy-string>>  $contents  The contents
     *
     * @return     string
     */
    public static function adminDashboardContents(ArrayObject $contents): string
    {
        $preferences = My::prefs();

        // Add large modules to the contents stack
        if ($preferences->active) {
            $class = ($preferences->posts_large ? 'medium' : 'small');
            $ret   = '<div id="scheduled-posts" class="box ' . $class . '">' .
            '<h3>' . '<img src="' . urldecode(Page::getPF(My::id() . '/icon.svg')) . '" alt="" class="icon-small">' . ' ' . __('Scheduled posts') . '</h3>';
            $ret .= self::getScheduledPosts(
                $preferences->posts_nb,
                $preferences->posts_large
            );
            $ret .= '</div>';
            $contents->append(new ArrayObject([$ret]));
        }

        return '';
    }

    public static function adminAfterDashboardOptionsUpdate(): string
    {
        // Get and store user's prefs for plugin options
        try {
            // Scheduled posts
            $preferences = My::prefs();
            $preferences->put('active', !empty($_POST['dmscheduled_active']), App::userWorkspace()::WS_BOOL);
            $preferences->put('posts_nb', (int) $_POST['dmscheduled_posts_nb'], App::userWorkspace()::WS_INT);
            $preferences->put('posts_large', empty($_POST['dmscheduled_posts_small']), App::userWorkspace()::WS_BOOL);
            $preferences->put('posts_count', !empty($_POST['dmscheduled_posts_count']), App::userWorkspace()::WS_BOOL);
            $preferences->put('monitor', !empty($_POST['dmscheduled_monitor']), App::userWorkspace()::WS_BOOL);
            $preferences->put('interval', (int) $_POST['dmscheduled_interval'], App::userWorkspace()::WS_INT);
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return '';
    }

    public static function adminDashboardOptionsForm(): string
    {
        $preferences = My::prefs();

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
            (new Para())->items([
                (new Number('dmscheduled_interval', 0, 9_999_999, $preferences->interval))
                    ->label((new Label(__('Interval in seconds between two refreshes:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
        ])
        ->render();

        return '';
    }
}
