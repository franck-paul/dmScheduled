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

class dmScheduledRest
{
    /**
     * Serve method to check if some entries need to be published.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function checkScheduled($core, $get)
    {
        global $core;

        $core->blog->publishScheduledEntries();

        $rsp      = new xmlTag('check');
        $rsp->ret = true;

        return $rsp;
    }

    /**
     * Serve method to get last scheduled rows for current blog.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     * @param    get        <b>array</b>    cleaned $_GET
     *
     * @return    <b>xmlTag</b>    XML representation of response
     */
    public static function getLastScheduledRows($core, $get)
    {
        $rsp      = new xmlTag('rows');
        $rsp->ret = 0;

        $core->auth->user_prefs->addWorkspace('dmscheduled');
        $ret = dmScheduledBehaviors::getScheduledPosts($core,
            $core->auth->user_prefs->dmscheduled->scheduled_posts_nb,
            $core->auth->user_prefs->dmscheduled->scheduled_posts_large);

        $rsp->list = $ret;
        $rsp->ret  = 1;

        return $rsp;
    }
}
