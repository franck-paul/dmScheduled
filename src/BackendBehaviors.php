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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Timestamp;
use Dotclear\Helper\Html\Form\Ul;
use Exception;

class BackendBehaviors
{
    public static function getScheduledPosts(int $nb, bool $large): string
    {
        // Get last $nb scheduled posts
        $params = [
            'post_status' => App::status()->post()::SCHEDULED,
            'order'       => 'post_dt ASC',
        ];
        if ($nb > 0) {
            $params['limit'] = $nb;
        }

        $rs = App::blog()->getPosts($params, false);
        if (!$rs->isEmpty()) {
            $lines = function (MetaRecord $rs, bool $large) {
                $date_format = is_string($date_format = App::blog()->settings()->system->date_format) ? $date_format : '%F';
                $time_format = is_string($time_format = App::blog()->settings()->system->time_format) ? $time_format : '%T';
                $user_tz     = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

                while ($rs->fetch()) {
                    $post_id    = is_numeric($post_id = $rs->post_id) ? (int) $post_id : 0;
                    $post_dt    = is_string($post_dt = $rs->post_dt) ? $post_dt : '';
                    $user_id    = is_string($user_id = $rs->user_id) ? $user_id : '';
                    $post_title = is_string($post_title = $rs->post_title) ? $post_title : '';

                    $infos = [];
                    if ($large) {
                        $details = __('on') . ' ' .
                            Date::dt2str($date_format, $post_dt) . ' ' .
                            Date::dt2str($time_format, $post_dt);
                        $infos[] = (new Text(null, __('by') . ' ' . $user_id));
                        $infos[] = (new Timestamp($details))
                            ->datetime(Date::iso8601((int) strtotime($post_dt), $user_tz));
                    } else {
                        $infos[] = (new Timestamp(Date::dt2str(__('%Y-%m-%d %H:%M'), $post_dt)))
                            ->datetime(Date::iso8601((int) strtotime($post_dt), $user_tz));
                    }

                    yield (new Li('dmsp' . $post_id))
                        ->class('line')
                        ->separator(' ')
                        ->items([
                            (new Link())
                                ->href(App::backend()->url()->get('admin.post', ['id' => $post_id]))
                                ->text($post_title),
                            ... $infos,
                        ]);
                }
            };

            return (new Set())
                ->items([
                    (new Ul())
                        ->items([
                            ... $lines($rs, $large),
                        ]),
                    (new Para())
                        ->items([
                            (new Link())
                                ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()::SCHEDULED]))
                                ->text(__('See all scheduled posts')),
                        ]),
                ])
            ->render();
        }

        return (new Note())
            ->text(__('No scheduled post'))
        ->render();
    }

    private static function countScheduledPosts(): string
    {
        $count = is_numeric($count = App::blog()->getPosts(['post_status' => App::status()->post()::SCHEDULED], true)->f(0)) ? (int) $count : 0;
        if ($count > 0) {
            return (new Link())
                ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()::SCHEDULED]))
                ->items([
                    (new Span(sprintf(__('(%d scheduled post)', '(%d scheduled posts)', $count), $count)))
                        ->class('db-icon-title-dm-scheduled'),
                ])
            ->render();
        }

        return '';
    }

    /**
     * @param      string                       $name   The name
     * @param      ArrayObject<string, mixed>   $icon   The icon
     */
    public static function adminDashboardFavsIcon(string $name, ArrayObject $icon): string
    {
        $preferences = My::prefs();
        if ($preferences->posts_count && $name === 'posts') {
            // Hack posts title if there is at least one scheduled post
            $str = self::countScheduledPosts();
            if ($str !== '') {
                $third   = is_string($third = $icon[3] ?? '') ? $third : '';
                $icon[3] = $third . $str;
            }
        }

        return '';
    }

    public static function adminDashboardHeaders(): string
    {
        $preferences = My::prefs();

        return
        App::backend()->page()->jsJson('dm_scheduled', [
            'monitor'  => $preferences->monitor,
            'counter'  => $preferences->posts_count,
            'interval' => ($preferences->interval ?? 300),
        ]) .
        My::jsLoad('service.js');
    }

    /**
     * @param      ArrayObject<int, ArrayObject<int, string>>  $contents  The contents
     */
    public static function adminDashboardContents(ArrayObject $contents): string
    {
        $preferences = My::prefs();

        $posts_nb = is_numeric($posts_nb = $preferences->posts_nb) ? (int) $posts_nb : 0;

        // Add large modules to the contents stack
        if ($preferences->active) {
            $class = ($preferences->posts_large ? 'medium' : 'small');

            $ret = (new Div('scheduled-posts'))
                ->class(['box', $class])
                ->items([
                    (new Text(
                        'h3',
                        (new Img(urldecode((string) App::backend()->page()->getPF(My::id() . '/icon.svg'))))
                            ->alt('')
                            ->class('icon-small')
                        ->render() . ' ' . __('Scheduled posts')
                    )),
                    (new Text(null, self::getScheduledPosts(
                        $posts_nb,
                        (bool) $preferences->posts_large
                    ))),
                ])
            ->render();

            $contents->append(new ArrayObject([$ret]));
        }

        return '';
    }

    public static function adminAfterDashboardOptionsUpdate(): string
    {
        // Get and store user's prefs for plugin options
        try {
            // Post data helpers
            $_Bool = fn (string $name): bool => !empty($_POST[$name]);
            $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;

            // Scheduled posts
            $preferences = My::prefs();

            $preferences->put('active', $_Bool('dmscheduled_active'), App::userWorkspace()::WS_BOOL);
            $preferences->put('posts_nb', $_Int('dmscheduled_posts_nb'), App::userWorkspace()::WS_INT);
            $preferences->put('posts_large', !$_Bool('dmscheduled_posts_small'), App::userWorkspace()::WS_BOOL);
            $preferences->put('posts_count', $_Bool('dmscheduled_posts_count'), App::userWorkspace()::WS_BOOL);
            $preferences->put('monitor', $_Bool('dmscheduled_monitor'), App::userWorkspace()::WS_BOOL);
            $preferences->put('interval', $_Int('dmscheduled_interval'), App::userWorkspace()::WS_INT);
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return '';
    }

    public static function adminDashboardOptionsForm(): string
    {
        // Variable data helpers
        $_Bool = fn (mixed $var): bool => (bool) $var;
        $_Int  = fn (mixed $var, int $default = 0): int => $var !== null && is_numeric($val = $var) ? (int) $val : $default;

        $preferences = My::prefs();

        // Add fieldset for plugin options
        echo
        (new Fieldset('dmscheduled'))
        ->legend((new Legend(__('Scheduled posts on dashboard'))))
        ->fields([
            (new Para())->items([
                (new Checkbox('dmscheduled_posts_count', $_Bool($preferences->posts_count)))
                    ->value(1)
                    ->label((new Label(__('Display count of scheduled posts on posts dashboard icon'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmscheduled_active', $_Bool($preferences->active)))
                    ->value(1)
                    ->label((new Label(__('Display scheduled posts'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmscheduled_posts_nb', 1, 999, $_Int($preferences->posts_nb, 5)))
                    ->label((new Label(__('Number of scheduled posts to display:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
            (new Para())->items([
                (new Checkbox('dmscheduled_posts_small', !$_Bool($preferences->posts_large)))
                    ->value(1)
                    ->label((new Label(__('Small screen'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Checkbox('dmscheduled_monitor', $_Bool($preferences->monitor)))
                    ->value(1)
                    ->label((new Label(__('Monitor'), Label::INSIDE_TEXT_AFTER))),
            ]),
            (new Para())->items([
                (new Number('dmscheduled_interval', 0, 9_999_999, $_Int($preferences->interval)))
                    ->label((new Label(__('Interval in seconds between two refreshes:'), Label::INSIDE_TEXT_BEFORE))),
            ]),
        ])
        ->render();

        return '';
    }
}
