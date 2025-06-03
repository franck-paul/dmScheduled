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
                while ($rs->fetch()) {
                    $infos = [];
                    if ($large) {
                        $details = __('on') . ' ' .
                            Date::dt2str(App::blog()->settings()->system->date_format, $rs->post_dt) . ' ' .
                            Date::dt2str(App::blog()->settings()->system->time_format, $rs->post_dt);
                        $infos[] = (new Text(null, __('by') . ' ' . $rs->user_id));
                        $infos[] = (new Timestamp($details))
                            ->datetime(Date::iso8601((int) strtotime($rs->post_dt), App::auth()->getInfo('user_tz')));
                    } else {
                        $infos[] = (new Timestamp(Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt)))
                            ->datetime(Date::iso8601((int) strtotime($rs->post_dt), App::auth()->getInfo('user_tz')));
                    }

                    yield (new Li('dmsp' . $rs->post_id))
                        ->class('line')
                        ->separator(' ')
                        ->items([
                            (new Link())
                                ->href(App::backend()->url()->get('admin.post', ['id' => $rs->post_id]))
                                ->text($rs->post_title),
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
        $count = App::blog()->getPosts(['post_status' => App::status()->post()::SCHEDULED], true)->f(0);
        if ($count) {
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', (int) $count), (int) $count);

            return (new Link())
                ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()::SCHEDULED]))
                ->items([
                    (new Span(sprintf($str, $count)))
                        ->class('db-icon-title-dm-scheduled'),
                ])
            ->render();
        }

        return '';
    }

    /**
     * Counts the number of scheduled posts.
     *
     * @deprecated since 2.33
     *
     * @return     string  Number of scheduled posts to set in icon title.
     */
    private static function countScheduledPostsLegacy(): string
    {
        $count = App::blog()->getPosts(['post_status' => App::status()->post()::SCHEDULED], true)->f(0);
        if ($count) {
            $str = sprintf(__('(%d scheduled post)', '(%d scheduled posts)', (int) $count), (int) $count);

            return '</span></a> <a href="' . App::backend()->url()->get('admin.posts', ['status' => App::status()->post()::SCHEDULED]) . '"><span class="db-icon-title-dm-scheduled">' . sprintf($str, $count);
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
            if (version_compare(App::config()->dotclearVersion(), '2.34', '>=') || str_contains((string) App::config()->dotclearVersion(), 'dev')) {
                $str = self::countScheduledPosts();
                if ($str !== '') {
                    $icon[3] = ($icon[3] ?? '') . $str;
                }
            } else {
                $str = self::countScheduledPostsLegacy();
                if ($str !== '') {
                    $icon[0] .= $str;
                }
            }
        }

        return '';
    }

    public static function adminDashboardHeaders(): string
    {
        $preferences = My::prefs();

        return
        Page::jsJson('dm_scheduled', [
            'monitor'  => $preferences->monitor,
            'counter'  => $preferences->posts_count,
            'interval' => ($preferences->interval ?? 300),
        ]) .
        My::jsLoad('service.js') .
        My::cssLoad('style.css');
    }

    /**
     * @param      ArrayObject<int, ArrayObject<int, string>>  $contents  The contents
     */
    public static function adminDashboardContents(ArrayObject $contents): string
    {
        $preferences = My::prefs();

        // Add large modules to the contents stack
        if ($preferences->active) {
            $class = ($preferences->posts_large ? 'medium' : 'small');

            $ret = (new Div('scheduled-posts'))
                ->class(['box', $class])
                ->items([
                    (new Text(
                        'h3',
                        (new Img(urldecode(Page::getPF(My::id() . '/icon.svg'))))
                            ->class('icon-small')
                        ->render() . ' ' . __('Scheduled posts')
                    )),
                    (new Text(null, self::getScheduledPosts(
                        $preferences->posts_nb,
                        $preferences->posts_large
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
